<?php

namespace Tests\Feature;

use App\Enums\EventGender;
use App\Enums\EventType;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use App\Services\GenerateEventHeats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelayEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_relay_events_can_be_created(): void
    {
        $competition = Competition::factory()->create();
        [$classification, $bracket] = $this->classificationWithBracket($competition);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('events.store', $competition), [
                'name' => '4x50 Free Relay',
                'gender' => EventGender::Mixed->value,
                'type' => EventType::Relay->value,
                'eligibilities' => [
                    [
                        'classification_id' => $classification->id,
                        'age_bracket_id' => $bracket->id,
                    ],
                ],
            ])
            ->assertRedirect(route('competitions.show', $competition));

        $event = $competition->events()->first();

        $this->assertNotNull($event);
        $this->assertSame(EventType::Relay, $event->type);
        $this->assertDatabaseCount('event_participant', 0);
    }

    public function test_teams_can_be_attached_to_a_relay_event(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->relay()->create(['competition_id' => $competition->id]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('event-teams.store', [$competition, $event]), [
                'team_id' => $team->id,
            ])
            ->assertRedirect(route('events.show', [$competition, $event]))
            ->assertSessionHas('status', 'event-team-added');

        $this->assertTrue($event->teams()->whereKey($team->id)->exists());
    }

    public function test_heat_generation_seats_teams_for_relay_events(): void
    {
        $competition = Competition::factory()->create(['number_of_lane' => 5]);
        $event = Event::factory()->relay()->create(['competition_id' => $competition->id]);
        $teams = Team::factory()->count(3)->create(['competition_id' => $competition->id]);
        $event->teams()->attach($teams->pluck('id'));

        $heats = app(GenerateEventHeats::class)->generate($event, 5);

        $this->assertSame(1, $heats);

        $lanes = $event->fresh()->heats()->first()->lanes()->orderBy('lane_number')->get();

        $this->assertCount(5, $lanes);
        $this->assertSame(3, $lanes->whereNotNull('team_id')->count());
        $this->assertSame(0, $lanes->whereNotNull('participant_id')->count());
    }

    public function test_individual_events_reject_team_entries(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'type' => EventType::Individual,
        ]);
        $team = Team::factory()->create(['competition_id' => $competition->id]);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('event-teams.store', [$competition, $event]), [
                'team_id' => $team->id,
            ])
            ->assertUnprocessable();
    }

    /**
     * @return array{0: Classification, 1: AgeBracket}
     */
    private function classificationWithBracket(Competition $competition): array
    {
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $classification->id,
        ]);

        return [$classification, $bracket];
    }
}
