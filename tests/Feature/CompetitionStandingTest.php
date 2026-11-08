<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\Participant;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompetitionStandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_mvp_excludes_relay_points_while_team_standings_include_them(): void
    {
        $competition = Competition::factory()->create();
        $teamA = Team::factory()->create(['competition_id' => $competition->id, 'name' => 'Alpha']);
        $teamB = Team::factory()->create(['competition_id' => $competition->id, 'name' => 'Beta']);

        $swimmerA = Participant::factory()->create([
            'competition_id' => $competition->id,
            'team_id' => $teamA->id,
            'first_name' => 'Ada',
            'last_name' => 'Alpha',
        ]);
        $swimmerB = Participant::factory()->create([
            'competition_id' => $competition->id,
            'team_id' => $teamB->id,
            'first_name' => 'Bea',
            'last_name' => 'Beta',
        ]);

        $individual = Event::factory()->create([
            'competition_id' => $competition->id,
            'type' => EventType::Individual,
            'sort_order' => 1,
        ]);
        $individualHeat = Heat::factory()->create(['event_id' => $individual->id]);
        $individualHeat->lanes()->create([
            'lane_number' => 1,
            'participant_id' => $swimmerA->id,
            'finish_time_hundredths' => 2800,
            'status' => 'swam',
        ]);
        $individualHeat->lanes()->create([
            'lane_number' => 2,
            'participant_id' => $swimmerB->id,
            'finish_time_hundredths' => 3000,
            'status' => 'swam',
        ]);

        $relay = Event::factory()->relay()->create([
            'competition_id' => $competition->id,
            'sort_order' => 2,
        ]);
        $relay->teams()->attach([$teamB->id, $teamA->id]);
        $relayHeat = Heat::factory()->create(['event_id' => $relay->id]);
        $relayHeat->lanes()->create([
            'lane_number' => 1,
            'team_id' => $teamB->id,
            'finish_time_hundredths' => 5000,
            'status' => 'swam',
        ]);
        $relayHeat->lanes()->create([
            'lane_number' => 2,
            'team_id' => $teamA->id,
            'finish_time_hundredths' => 5200,
            'status' => 'swam',
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-standings.index', $competition))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Standings/Show')
                ->where('mvp.0.participant_id', $swimmerA->id)
                ->where('mvp.0.points', 8)
                ->where('mvp.1.participant_id', $swimmerB->id)
                ->where('mvp.1.points', 7)
                ->where('teams.0.team_id', $teamA->id)
                ->where('teams.0.points', 15)
                ->where('teams.1.team_id', $teamB->id)
                ->where('teams.1.points', 15));
    }

    public function test_public_standings_require_a_closed_competition(): void
    {
        $open = Competition::factory()->create();
        $closed = Competition::factory()->closed()->create();

        $this->get(route('public-standings.index', $open))->assertNotFound();

        $this->get(route('public-standings.index', $closed))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Standings')
                ->where('public', true));
    }
}
