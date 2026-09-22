<?php

namespace Tests\Feature;

use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionCloseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_competition_is_open_by_default(): void
    {
        $competition = Competition::factory()->create();

        $this->assertFalse($competition->is_close);
        $this->assertFalse($competition->fresh()->isClosed());
    }

    public function test_guests_cannot_close_a_competition(): void
    {
        $competition = $this->competitionReadyToClose();

        $response = $this->patch(route('competitions.close', $competition));

        $response->assertRedirect(route('login'));
        $this->assertFalse($competition->fresh()->is_close);
    }

    public function test_a_competition_can_be_closed_once_every_event_has_heats_and_lanes(): void
    {
        $competition = $this->competitionReadyToClose();

        $response = $this
            ->actingAs(User::factory()->create())
            ->patch(route('competitions.close', $competition));

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'competition-closed');

        $this->assertTrue($competition->fresh()->is_close);
    }

    public function test_a_competition_without_events_cannot_be_closed(): void
    {
        $competition = Competition::factory()->create();

        $response = $this
            ->actingAs(User::factory()->create())
            ->patch(route('competitions.close', $competition));

        $response->assertSessionHasErrors('is_close');
        $this->assertFalse($competition->fresh()->is_close);
    }

    public function test_a_competition_with_an_event_missing_heats_cannot_be_closed(): void
    {
        $competition = $this->competitionReadyToClose();
        Event::factory()->create(['competition_id' => $competition->id]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->patch(route('competitions.close', $competition));

        $response->assertSessionHasErrors('is_close');
        $this->assertFalse($competition->fresh()->is_close);
    }

    public function test_a_competition_with_a_heat_missing_lanes_cannot_be_closed(): void
    {
        $competition = $this->competitionReadyToClose();
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        Heat::factory()->create(['event_id' => $event->id]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->patch(route('competitions.close', $competition));

        $response->assertSessionHasErrors('is_close');
        $this->assertFalse($competition->fresh()->is_close);
    }

    public function test_an_already_closed_competition_cannot_be_closed_again(): void
    {
        $competition = $this->competitionReadyToClose();
        $competition->is_close = true;
        $competition->save();

        $response = $this
            ->actingAs(User::factory()->create())
            ->patch(route('competitions.close', $competition));

        $response->assertSessionHasErrors('is_close');
    }

    public function test_participants_cannot_be_added_to_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('participants.store', $competition), [
                'first_name' => 'Ava',
                'last_name' => 'Cruz',
                'gender' => 'female',
                'team' => 'Dolphins',
                'birthdate' => '2012-04-01',
                'classification_id' => $classification->id,
            ]);

        $response->assertForbidden();
        $this->assertSame(0, $competition->participants()->count());
    }

    public function test_participants_cannot_be_imported_into_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->create();

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('participants.import', $competition));

        $response->assertForbidden();
    }

    public function test_the_program_order_cannot_be_regenerated_for_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->create();

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('events.program', $competition), [
                'columns' => ['name'],
                'gender_order' => [],
                'name_order' => [],
            ]);

        $response->assertForbidden();
    }

    public function test_classifications_cannot_be_added_to_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->create();

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('classifications.store', $competition), [
                'name' => 'Novice',
            ]);

        $response->assertForbidden();
        $this->assertSame(0, $competition->classifications()->count());
    }

    public function test_age_brackets_cannot_be_added_to_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('age-brackets.store', [$competition, $classification]), [
                'name' => '9-10',
            ]);

        $response->assertForbidden();
        $this->assertSame(0, AgeBracket::query()->count());
    }

    public function test_events_cannot_be_added_to_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->create();

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('events.store', $competition), [
                'name' => '50m Freestyle',
                'gender' => 'male',
                'eligibilities' => [],
            ]);

        $response->assertForbidden();
        $this->assertSame(0, $competition->events()->count());
    }

    public function test_events_cannot_be_generated_for_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->create();

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('events.generate', $competition), [
                'name' => '50m Freestyle',
                'genders' => ['male'],
                'eligibilities' => [],
            ]);

        $response->assertForbidden();
        $this->assertSame(0, $competition->events()->count());
    }

    public function test_heats_cannot_be_generated_for_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('event-heats.generate-all', $competition))
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->post(route('event-heats.generate', [$competition, $event]))
            ->assertForbidden();

        $this->assertSame(0, $event->heats()->count());
    }

    public function test_participants_cannot_be_added_to_an_event_of_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $participant = Participant::factory()->paid()->create([
            'competition_id' => $competition->id,
        ]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('event-participants.store', [$competition, $event]), [
                'participant_id' => $participant->id,
            ]);

        $response->assertForbidden();
        $this->assertSame(0, $event->participants()->count());
    }

    public function test_guests_cannot_reopen_a_competition(): void
    {
        $competition = Competition::factory()->closed()->create();

        $response = $this->patch(route('competitions.open', $competition));

        $response->assertRedirect(route('login'));
        $this->assertTrue($competition->fresh()->is_close);
    }

    public function test_a_closed_competition_can_be_reopened(): void
    {
        $competition = Competition::factory()->closed()->create();

        $response = $this
            ->actingAs(User::factory()->create())
            ->patch(route('competitions.open', $competition));

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'competition-opened');

        $this->assertFalse($competition->fresh()->is_close);
    }

    public function test_an_open_competition_cannot_be_reopened(): void
    {
        $competition = Competition::factory()->create();

        $response = $this
            ->actingAs(User::factory()->create())
            ->patch(route('competitions.open', $competition));

        $response->assertSessionHasErrors('is_close');
    }

    public function test_reopening_a_competition_restores_the_blocked_actions(): void
    {
        $competition = Competition::factory()->closed()->create();
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('competitions.open', $competition))
            ->assertSessionHasNoErrors();

        $this
            ->actingAs($user)
            ->post(route('classifications.store', $competition), ['name' => 'Novice'])
            ->assertRedirect(route('competitions.show', $competition));

        $this->assertSame(1, $competition->classifications()->count());
    }

    /**
     * A competition whose only event already has a heat with lanes.
     */
    private function competitionReadyToClose(): Competition
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);

        Heat::factory()->withLanes($competition->number_of_lane)->create([
            'event_id' => $event->id,
        ]);

        return $competition;
    }
}
