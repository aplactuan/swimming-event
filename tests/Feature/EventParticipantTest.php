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
use Tests\TestCase;

class EventParticipantTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manually_add_a_participant_to_an_event(): void
    {
        [$competition, $event, $participant] = $this->setupPaidMismatch();

        $response = $this->post(
            route('event-participants.store', [$competition, $event]),
            ['participant_id' => $participant->id],
        );

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('event_participant', 0);
    }

    public function test_paid_participant_can_be_manually_added_even_if_they_do_not_match(): void
    {
        $user = User::factory()->create();
        [$competition, $event, $participant] = $this->setupPaidMismatch();

        $response = $this
            ->actingAs($user)
            ->post(route('event-participants.store', [$competition, $event]), [
                'participant_id' => $participant->id,
            ]);

        $response
            ->assertRedirect(route('events.show', [$competition, $event]))
            ->assertSessionHas('status', 'event-participant-added');

        $this->assertDatabaseHas('event_participant', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
        ]);
    }

    public function test_unpaid_participant_cannot_be_manually_added_to_an_event(): void
    {
        $user = User::factory()->create();
        [$competition, $event, $participant] = $this->setupPaidMismatch();
        $participant->update(['paid' => false]);

        $this
            ->actingAs($user)
            ->from(route('events.show', [$competition, $event]))
            ->post(route('event-participants.store', [$competition, $event]), [
                'participant_id' => $participant->id,
            ])
            ->assertRedirect(route('events.show', [$competition, $event]))
            ->assertSessionHasErrors(['participant_id']);

        $this->assertDatabaseCount('event_participant', 0);
    }

    public function test_paid_participant_can_be_removed_from_an_event(): void
    {
        $user = User::factory()->create();
        [$competition, $event, $participant] = $this->setupPaidMismatch();
        $event->participants()->attach($participant->id);

        $response = $this
            ->actingAs($user)
            ->delete(route('event-participants.destroy', [$competition, $event, $participant]));

        $response
            ->assertRedirect(route('events.show', [$competition, $event]))
            ->assertSessionHas('status', 'event-participant-removed');

        $this->assertDatabaseCount('event_participant', 0);
    }

    public function test_unpaid_participant_cannot_be_removed_from_an_event(): void
    {
        $user = User::factory()->create();
        [$competition, $event, $participant] = $this->setupPaidMismatch();
        $event->participants()->attach($participant->id);
        $participant->update(['paid' => false]);

        $this
            ->actingAs($user)
            ->delete(route('event-participants.destroy', [$competition, $event, $participant]))
            ->assertStatus(422);

        $this->assertDatabaseHas('event_participant', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
        ]);
    }

    /**
     * @return array{0: Competition, 1: Event, 2: Participant}
     */
    private function setupPaidMismatch(): array
    {
        $competition = Competition::factory()->create();
        $novice = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Novice',
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $novice->id,
            'start_birthday' => '2014-01-01',
            'end_birthday' => '2016-12-31',
        ]);

        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '25m Freestyle Male',
            'gender' => EventGender::Male,
        ]);
        EventEligibility::query()->create([
            'event_id' => $event->id,
            'classification_id' => $novice->id,
            'age_bracket_id' => $bracket->id,
        ]);

        $participant = Participant::factory()->paid()->create([
            'competition_id' => $competition->id,
            'classification_id' => $novice->id,
            'gender' => ParticipantGender::Female,
            'first_name' => 'Ada',
            'birthdate' => '2015-06-15',
        ]);

        return [$competition, $event, $participant];
    }
}
