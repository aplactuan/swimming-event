<?php

namespace Tests\Feature;

use App\Enums\HeatLaneStatus;
use App\Models\Competition;
use App\Models\CompetitionPointRule;
use App\Models\Event;
use App\Models\Heat;
use App\Models\Participant;
use App\Models\Team;
use App\Services\CompetitionScoring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_places_and_points_follow_finish_order(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $heat = Heat::factory()->create(['event_id' => $event->id]);

        $first = $this->lane($heat, $competition, 2800);
        $second = $this->lane($heat, $competition, 3000);
        $third = $this->lane($heat, $competition, 3200);

        $scored = app(CompetitionScoring::class)->scoreEvent($event, $competition);

        $this->assertSame($first->id, $scored[0]['lane']->id);
        $this->assertSame(1, $scored[0]['place']);
        $this->assertSame(8.0, $scored[0]['points']);

        $this->assertSame($second->id, $scored[1]['lane']->id);
        $this->assertSame(2, $scored[1]['place']);
        $this->assertSame(7.0, $scored[1]['points']);

        $this->assertSame($third->id, $scored[2]['lane']->id);
        $this->assertSame(3, $scored[2]['place']);
        $this->assertSame(6.0, $scored[2]['points']);
    }

    public function test_tied_finishers_share_place_and_average_points(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $heat = Heat::factory()->create(['event_id' => $event->id]);

        $this->lane($heat, $competition, 2800);
        $this->lane($heat, $competition, 3000);
        $this->lane($heat, $competition, 3000);
        $fourth = $this->lane($heat, $competition, 3200);

        $scored = app(CompetitionScoring::class)->scoreEvent($event, $competition);

        $this->assertSame(1, $scored[0]['place']);
        $this->assertSame(8.0, $scored[0]['points']);

        $this->assertSame(2, $scored[1]['place']);
        $this->assertSame(6.5, $scored[1]['points']);
        $this->assertSame(2, $scored[2]['place']);
        $this->assertSame(6.5, $scored[2]['points']);
        $this->assertEqualsCanonicalizing(
            [3000, 3000],
            [$scored[1]['lane']->finish_time_hundredths, $scored[2]['lane']->finish_time_hundredths],
        );

        $this->assertSame($fourth->id, $scored[3]['lane']->id);
        $this->assertSame(4, $scored[3]['place']);
        $this->assertSame(5.0, $scored[3]['points']);
    }

    public function test_ns_lanes_receive_no_place_or_points_and_sort_last(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $heat = Heat::factory()->create(['event_id' => $event->id]);

        $ns = $this->lane($heat, $competition, 2500, HeatLaneStatus::Ns);
        $timed = $this->lane($heat, $competition, 3000);
        $untimed = $this->lane($heat, $competition, null);

        $scored = app(CompetitionScoring::class)->scoreEvent($event, $competition);

        $this->assertSame($timed->id, $scored[0]['lane']->id);
        $this->assertSame(1, $scored[0]['place']);

        $this->assertSame($untimed->id, $scored[1]['lane']->id);
        $this->assertNull($scored[1]['place']);
        $this->assertNull($scored[1]['points']);

        $this->assertSame($ns->id, $scored[2]['lane']->id);
        $this->assertSame('ns', $scored[2]['status']);
        $this->assertNull($scored[2]['place']);
        $this->assertNull($scored[2]['points']);
    }

    public function test_point_map_uses_competition_rules(): void
    {
        $competition = Competition::factory()->create();
        $competition->pointRules()->delete();
        CompetitionPointRule::factory()->create([
            'competition_id' => $competition->id,
            'place' => 1,
            'points' => 10,
        ]);

        $map = app(CompetitionScoring::class)->pointMap($competition);

        $this->assertSame([1 => 10.0], $map);
    }

    public function test_guest_lanes_score_under_their_entry_event(): void
    {
        $competition = Competition::factory()->create();
        $host = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '25m Breaststroke',
            'gender' => 'male',
        ]);
        $donor = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '25m Breaststroke',
            'gender' => 'male',
        ]);
        $heat = Heat::factory()->create(['event_id' => $host->id]);

        $home = $this->lane($heat, $competition, 2800, HeatLaneStatus::Swam, $host->id);
        $guest = $this->lane($heat, $competition, 2700, HeatLaneStatus::Swam, $donor->id);

        $hostScored = app(CompetitionScoring::class)->scoreEvent($host, $competition);
        $donorScored = app(CompetitionScoring::class)->scoreEvent($donor, $competition);

        $this->assertCount(1, $hostScored);
        $this->assertSame($home->id, $hostScored[0]['lane']->id);
        $this->assertSame(1, $hostScored[0]['place']);

        $this->assertCount(1, $donorScored);
        $this->assertSame($guest->id, $donorScored[0]['lane']->id);
        $this->assertSame(1, $donorScored[0]['place']);
        $this->assertSame(8.0, $donorScored[0]['points']);
    }

    private function lane(
        Heat $heat,
        Competition $competition,
        ?int $hundredths,
        HeatLaneStatus $status = HeatLaneStatus::Swam,
        ?string $entryEventId = null,
    ): \App\Models\HeatLane {
        $participant = Participant::factory()->create([
            'competition_id' => $competition->id,
            'team_id' => Team::factory()->create(['competition_id' => $competition->id])->id,
        ]);

        return $heat->lanes()->create([
            'lane_number' => $heat->lanes()->count() + 1,
            'participant_id' => $participant->id,
            'entry_event_id' => $entryEventId ?? $heat->event_id,
            'finish_time_hundredths' => $hundredths,
            'status' => $status,
        ]);
    }
}
