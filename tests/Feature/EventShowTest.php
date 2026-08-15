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

class EventShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_an_event(): void
    {
        [$competition, $event] = $this->competitionWithEvent();

        $this
            ->get(route('events.show', [$competition, $event]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_an_event_with_participants(): void
    {
        $user = User::factory()->create();
        [$competition, $event, $classification] = $this->competitionWithEvent();

        $participant = Participant::factory()->paid()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'gender' => ParticipantGender::Female,
        ]);
        $event->participants()->attach($participant->id);

        $this
            ->actingAs($user)
            ->get(route('events.show', [$competition, $event]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Events/Show')
                ->where('competition.id', $competition->id)
                ->where('competition.name', $competition->name)
                ->where('event.id', $event->id)
                ->where('event.name', '25m Freestyle')
                ->where('event.gender', EventGender::Female->value)
                ->has('event.participants', 1)
                ->where('event.participants.0.id', $participant->id)
                ->where('event.participants.0.first_name', 'Ada')
                ->has('competition.participants', 1));
    }

    public function test_event_is_scoped_to_its_competition(): void
    {
        $user = User::factory()->create();
        [$competition] = $this->competitionWithEvent();
        [, $otherEvent] = $this->competitionWithEvent('Other Meet', '50m Backstroke');

        $this
            ->actingAs($user)
            ->get(route('events.show', [$competition, $otherEvent]))
            ->assertNotFound();
    }

    /**
     * @return array{0: Competition, 1: Event, 2: Classification}
     */
    private function competitionWithEvent(
        string $competitionName = 'City Championships',
        string $eventName = '25m Freestyle',
    ): array {
        $competition = Competition::factory()->create([
            'name' => $competitionName,
        ]);
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Novice',
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $classification->id,
        ]);
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => $eventName,
            'gender' => EventGender::Female,
        ]);
        EventEligibility::query()->create([
            'event_id' => $event->id,
            'classification_id' => $classification->id,
            'age_bracket_id' => $bracket->id,
        ]);

        return [$competition, $event, $classification];
    }
}
