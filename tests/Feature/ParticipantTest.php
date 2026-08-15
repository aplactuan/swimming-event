<?php

namespace Tests\Feature;

use App\Enums\EventGender;
use App\Enums\ParticipantGender;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\EventEligibility;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ParticipantTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_create_a_participant(): void
    {
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);

        $response = $this->post(
            route('participants.store', $competition),
            $this->validPayload($classification),
        );

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('participants', 0);
    }

    public function test_authenticated_users_can_create_an_unpaid_participant(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Novice',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('participants.store', $competition), $this->validPayload($classification));

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'participant-created');

        $participant = Participant::query()->first();

        $this->assertNotNull($participant);
        $this->assertSame('Ada', $participant->first_name);
        $this->assertSame('Lovelace', $participant->last_name);
        $this->assertSame(ParticipantGender::Female, $participant->gender);
        $this->assertSame('City Swim Club', $participant->team);
        $this->assertSame('2015-06-15', $participant->birthdate->toDateString());
        $this->assertSame($classification->id, $participant->classification_id);
        $this->assertFalse($participant->paid);
        $this->assertDatabaseCount('event_participant', 0);
    }

    public function test_creating_a_paid_participant_auto_enters_matching_events(): void
    {
        $user = User::factory()->create();
        [$competition, $novice, $bracket] = $this->competitionWithClassificationAndBracket();

        $matchingEvent = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '25m Freestyle Female',
            'gender' => EventGender::Female,
            'sort_order' => 1,
        ]);
        EventEligibility::query()->create([
            'event_id' => $matchingEvent->id,
            'classification_id' => $novice->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $mixedEvent = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '25m Freestyle Mixed',
            'gender' => EventGender::Mixed,
            'sort_order' => 2,
        ]);
        EventEligibility::query()->create([
            'event_id' => $mixedEvent->id,
            'classification_id' => $novice->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $maleEvent = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '25m Freestyle Male',
            'gender' => EventGender::Male,
            'sort_order' => 3,
        ]);
        EventEligibility::query()->create([
            'event_id' => $maleEvent->id,
            'classification_id' => $novice->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $otherClass = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Intermediate',
            'sort_order' => 2,
        ]);
        $otherBracket = AgeBracket::factory()->create([
            'classification_id' => $otherClass->id,
        ]);
        $wrongClassEvent = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Intermediate Female',
            'gender' => EventGender::Female,
            'sort_order' => 4,
        ]);
        EventEligibility::query()->create([
            'event_id' => $wrongClassEvent->id,
            'classification_id' => $otherClass->id,
            'age_bracket_id' => $otherBracket->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('participants.store', $competition), $this->validPayload($novice, [
                'paid' => true,
            ]));

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'participant-created');

        $participant = Participant::query()->first();

        $this->assertNotNull($participant);
        $this->assertTrue($participant->paid);
        $this->assertTrue($participant->events->contains('id', $matchingEvent->id));
        $this->assertTrue($participant->events->contains('id', $mixedEvent->id));
        $this->assertFalse($participant->events->contains('id', $maleEvent->id));
        $this->assertFalse($participant->events->contains('id', $wrongClassEvent->id));
    }

    public function test_participant_requires_core_fields(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('participants.store', $competition), [
                'first_name' => '',
                'last_name' => '',
                'gender' => 'mixed',
                'team' => '',
                'birthdate' => '',
                'classification_id' => '',
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors([
                'first_name',
                'last_name',
                'gender',
                'team',
                'birthdate',
                'classification_id',
            ]);
    }

    public function test_classification_must_belong_to_the_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $otherClassification = Classification::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('participants.store', $competition), $this->validPayload($otherClassification))
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors(['classification_id']);
    }

    public function test_authenticated_users_can_update_a_participant(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $participant = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'first_name' => 'Ada',
            'paid' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('participants.update', [$competition, $participant]), $this->validPayload($classification, [
                'first_name' => 'Augusta',
                'paid' => true,
            ]));

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'participant-updated');

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'first_name' => 'Augusta',
            'paid' => true,
        ]);
    }

    public function test_marking_a_participant_unpaid_removes_all_event_entries(): void
    {
        $user = User::factory()->create();
        [$competition, $novice, $bracket] = $this->competitionWithClassificationAndBracket();

        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'gender' => EventGender::Female,
        ]);
        EventEligibility::query()->create([
            'event_id' => $event->id,
            'classification_id' => $novice->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $manualEvent = Event::factory()->create([
            'competition_id' => $competition->id,
            'gender' => EventGender::Male,
            'sort_order' => 2,
        ]);

        $participant = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $novice->id,
            'gender' => ParticipantGender::Female,
            'birthdate' => '2015-06-15',
            'paid' => true,
        ]);
        $participant->events()->attach([$event->id, $manualEvent->id]);

        $response = $this
            ->actingAs($user)
            ->put(route('participants.update', [$competition, $participant]), $this->validPayload($novice, [
                'paid' => false,
            ]));

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'participant-updated');

        $this->assertDatabaseCount('event_participant', 0);
        $this->assertFalse($participant->fresh()->paid);
    }

    public function test_authenticated_users_can_delete_a_participant(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $participant = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('participants.destroy', [$competition, $participant]));

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'participant-deleted');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_participant_is_scoped_to_its_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $otherCompetition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $otherCompetition->id,
        ]);
        $participant = Participant::factory()->create([
            'competition_id' => $otherCompetition->id,
            'classification_id' => $classification->id,
        ]);

        $this
            ->actingAs($user)
            ->put(route('participants.update', [$competition, $participant]), $this->validPayload($classification))
            ->assertNotFound();
    }

    public function test_competition_show_includes_participants(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Novice',
        ]);
        $participant = Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'paid' => false,
        ]);

        $this
            ->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Competitions/Show')
                ->has('competition.participants', 1)
                ->where('competition.participants.0.id', $participant->id)
                ->where('competition.participants.0.first_name', 'Ada')
                ->where('competition.participants.0.classification.name', 'Novice'));
    }

    /**
     * @return array{0: Competition, 1: Classification, 2: AgeBracket}
     */
    private function competitionWithClassificationAndBracket(): array
    {
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Novice',
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $classification->id,
            'name' => '8-10',
            'start_birthday' => '2014-01-01',
            'end_birthday' => '2016-12-31',
        ]);

        return [$competition, $classification, $bracket];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Classification $classification, array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'gender' => ParticipantGender::Female->value,
            'team' => 'City Swim Club',
            'birthdate' => '2015-06-15',
            'classification_id' => $classification->id,
            'paid' => false,
        ], $overrides);
    }
}
