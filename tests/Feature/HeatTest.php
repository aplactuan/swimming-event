<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Participant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_event_has_many_heats_in_swim_order(): void
    {
        $event = Event::factory()->create();

        Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 2]);
        Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 1]);
        Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 3]);

        $this->assertSame([1, 2, 3], $event->heats->pluck('heat_number')->all());
    }

    public function test_a_heat_has_many_lanes_in_lane_order(): void
    {
        $heat = Heat::factory()->create();

        HeatLane::factory()->create(['heat_id' => $heat->id, 'lane_number' => 3]);
        HeatLane::factory()->create(['heat_id' => $heat->id, 'lane_number' => 1]);
        HeatLane::factory()->create(['heat_id' => $heat->id, 'lane_number' => 2]);

        $this->assertSame([1, 2, 3], $heat->lanes->pluck('lane_number')->all());
    }

    public function test_a_heat_has_many_participants_through_its_lanes(): void
    {
        $event = Event::factory()->create();
        $heat = Heat::factory()->create(['event_id' => $event->id]);

        $second = Participant::factory()->create(['competition_id' => $event->competition_id]);
        $first = Participant::factory()->create(['competition_id' => $event->competition_id]);

        $heat->assignLane(2, $second);
        $heat->assignLane(1, $first);

        $this->assertSame(
            [$first->id, $second->id],
            $heat->participants->pluck('id')->all(),
        );
        $this->assertSame(1, $heat->participants->first()->pivot->lane_number);
    }

    public function test_a_lane_can_be_empty(): void
    {
        $lane = HeatLane::factory()->create(['participant_id' => null]);

        $this->assertNull($lane->participant_id);
        $this->assertNull($lane->participant);
        $this->assertNull($lane->finish_time);
    }

    public function test_assigning_a_lane_replaces_the_current_swimmer(): void
    {
        $event = Event::factory()->create();
        $heat = Heat::factory()->create(['event_id' => $event->id]);

        $original = Participant::factory()->create(['competition_id' => $event->competition_id]);
        $replacement = Participant::factory()->create(['competition_id' => $event->competition_id]);

        $heat->assignLane(4, $original);
        $heat->assignLane(4, $replacement);

        $this->assertSame(1, $heat->lanes()->count());
        $this->assertSame($replacement->id, $heat->lanes()->sole()->participant_id);
    }

    public function test_deleting_a_participant_empties_the_lane(): void
    {
        $event = Event::factory()->create();
        $heat = Heat::factory()->create(['event_id' => $event->id]);
        $participant = Participant::factory()->create(['competition_id' => $event->competition_id]);

        $lane = $heat->assignLane(1, $participant);

        $participant->delete();

        $this->assertNull($lane->fresh()->participant_id);
    }

    public function test_deleting_an_event_deletes_its_heats_and_lanes(): void
    {
        $event = Event::factory()->create();
        Heat::factory()->withLanes(3)->create(['event_id' => $event->id]);

        $event->delete();

        $this->assertDatabaseCount('heats', 0);
        $this->assertDatabaseCount('heat_lanes', 0);
    }

    public function test_finish_time_is_stored_and_formatted_from_its_parts(): void
    {
        $lane = HeatLane::factory()->create();

        $lane->recordFinishTime(minutes: 1, seconds: 2, hundredths: 45);
        $lane->save();

        $this->assertSame(6245, $lane->fresh()->finish_time_hundredths);
        $this->assertSame('1:02.45', $lane->fresh()->finish_time);
    }

    public function test_finish_time_pads_seconds_and_hundredths(): void
    {
        $lane = HeatLane::factory()->finished(HeatLane::toHundredths(0, 8, 5))->create();

        $this->assertSame('0:08.05', $lane->finish_time);
    }

    public function test_ranked_scope_orders_finished_lanes_fastest_first(): void
    {
        $heat = Heat::factory()->create();

        HeatLane::factory()->finished(6245)->create(['heat_id' => $heat->id, 'lane_number' => 1]);
        HeatLane::factory()->finished(5980)->create(['heat_id' => $heat->id, 'lane_number' => 2]);
        HeatLane::factory()->create(['heat_id' => $heat->id, 'lane_number' => 3]);

        $this->assertSame(
            [5980, 6245],
            $heat->lanes()->ranked()->pluck('finish_time_hundredths')->all(),
        );
    }

    public function test_an_event_can_rank_lanes_across_every_heat(): void
    {
        $event = Event::factory()->create();
        $firstHeat = Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 1]);
        $secondHeat = Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 2]);

        HeatLane::factory()->finished(6245)->create(['heat_id' => $firstHeat->id, 'lane_number' => 1]);
        HeatLane::factory()->finished(5980)->create(['heat_id' => $secondHeat->id, 'lane_number' => 1]);

        $this->assertSame(
            [5980, 6245],
            $event->heatLanes()->ranked()->pluck('finish_time_hundredths')->all(),
        );
    }

    public function test_a_lane_cannot_be_used_twice_in_the_same_heat(): void
    {
        $heat = Heat::factory()->create();

        HeatLane::factory()->create(['heat_id' => $heat->id, 'lane_number' => 4]);

        $this->expectException(QueryException::class);

        HeatLane::factory()->create(['heat_id' => $heat->id, 'lane_number' => 4]);
    }

    public function test_the_same_lane_can_be_used_in_another_heat(): void
    {
        $event = Event::factory()->create();
        $firstHeat = Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 1]);
        $secondHeat = Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 2]);

        HeatLane::factory()->create(['heat_id' => $firstHeat->id, 'lane_number' => 4]);
        HeatLane::factory()->create(['heat_id' => $secondHeat->id, 'lane_number' => 4]);

        $this->assertSame(2, $event->heatLanes()->count());
    }

    public function test_an_event_cannot_have_two_heats_with_the_same_number(): void
    {
        $event = Event::factory()->create();

        Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 1]);

        $this->expectException(QueryException::class);

        Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 1]);
    }

    public function test_next_heat_number_follows_the_last_heat(): void
    {
        $event = Event::factory()->create();

        $this->assertSame(1, Heat::nextHeatNumber($event->id));

        Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 1]);
        Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 2]);

        $this->assertSame(3, Heat::nextHeatNumber($event->id));
    }
}
