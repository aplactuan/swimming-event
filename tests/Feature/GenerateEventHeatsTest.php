<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Participant;
use App\Models\User;
use App\Services\GenerateEventHeats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateEventHeatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_generate_heats(): void
    {
        [$competition, $event] = $this->competitionWithEvent();

        $this
            ->post(route('event-heats.generate', [$competition, $event]))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('heats', 0);
    }

    public function test_it_spreads_seventeen_participants_over_four_heats_of_five_lanes(): void
    {
        [$competition, $event] = $this->competitionWithEvent(lanes: 5);
        $this->enterParticipants($event, 17);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('event-heats.generate', [$competition, $event]))
            ->assertRedirect(route('events.show', [$competition, $event]))
            ->assertSessionHas('status', 'heats-generated')
            ->assertSessionHas('generated_heats_count', 4);

        $this->assertSame([5, 4, 4, 4], $this->swimmersPerHeat($event));
        $this->assertSame([5, 5, 5, 5], $this->lanesPerHeat($event));
    }

    public function test_it_evens_out_sixteen_participants_across_four_heats(): void
    {
        [, $event] = $this->competitionWithEvent(lanes: 5);
        $this->enterParticipants($event, 16);

        app(GenerateEventHeats::class)->generate($event);

        $this->assertSame([4, 4, 4, 4], $this->swimmersPerHeat($event));
    }

    public function test_it_uses_a_single_heat_when_everyone_fits(): void
    {
        [, $event] = $this->competitionWithEvent(lanes: 5);
        $this->enterParticipants($event, 5);

        $this->assertSame(1, app(GenerateEventHeats::class)->generate($event));
        $this->assertSame([5], $this->swimmersPerHeat($event));
    }

    public function test_it_fills_the_centre_lanes_first_and_leaves_the_edges_empty(): void
    {
        [, $event] = $this->competitionWithEvent(lanes: 5);
        $this->enterParticipants($event, 3);

        app(GenerateEventHeats::class)->generate($event);

        $lanes = $event->heats()->sole()->lanes;

        $this->assertSame([1, 2, 3, 4, 5], $lanes->pluck('lane_number')->all());
        $this->assertNull($lanes->firstWhere('lane_number', 1)->participant_id);
        $this->assertNotNull($lanes->firstWhere('lane_number', 2)->participant_id);
        $this->assertNotNull($lanes->firstWhere('lane_number', 3)->participant_id);
        $this->assertNotNull($lanes->firstWhere('lane_number', 4)->participant_id);
        $this->assertNull($lanes->firstWhere('lane_number', 5)->participant_id);
    }

    public function test_a_lone_swimmer_takes_the_centre_lane(): void
    {
        [, $event] = $this->competitionWithEvent(lanes: 5);
        $this->enterParticipants($event, 1);

        app(GenerateEventHeats::class)->generate($event);

        $occupied = $event->heats()->sole()->lanes->whereNotNull('participant_id');

        $this->assertSame([3], $occupied->pluck('lane_number')->all());
    }

    public function test_lane_fill_order_runs_centre_out_for_odd_and_even_lane_counts(): void
    {
        $this->assertSame([3, 4, 2, 5, 1], GenerateEventHeats::laneFillOrder(5));
        $this->assertSame([3, 2, 4, 1], GenerateEventHeats::laneFillOrder(4));
        $this->assertSame([5, 4, 6, 3, 7, 2, 8, 1], GenerateEventHeats::laneFillOrder(8));
        $this->assertSame([1], GenerateEventHeats::laneFillOrder(1));
    }

    public function test_every_participant_is_seated_exactly_once(): void
    {
        [, $event] = $this->competitionWithEvent(lanes: 5);
        $this->enterParticipants($event, 17);

        app(GenerateEventHeats::class)->generate($event);

        $seated = $event->heatLanes()->whereNotNull('participant_id')->pluck('participant_id');

        $this->assertCount(17, $seated);
        $this->assertEqualsCanonicalizing(
            $event->participants()->pluck('participants.id')->all(),
            $seated->all(),
        );
    }

    public function test_regenerating_replaces_the_existing_heats_and_lanes(): void
    {
        [$competition, $event] = $this->competitionWithEvent(lanes: 5);
        $this->enterParticipants($event, 5);

        app(GenerateEventHeats::class)->generate($event);
        $originalHeatIds = $event->heats()->pluck('id')->all();
        $originalLaneIds = $event->heatLanes()->pluck('heat_lanes.id')->all();

        $this->enterParticipants($event, 1);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('event-heats.generate', [$competition, $event]))
            ->assertSessionHas('generated_heats_count', 2);

        $this->assertSame([3, 3], $this->swimmersPerHeat($event));
        $this->assertDatabaseCount('heats', 2);
        $this->assertDatabaseCount('heat_lanes', 10);
        $this->assertDatabaseMissing('heats', ['id' => $originalHeatIds[0]]);
        $this->assertDatabaseMissing('heat_lanes', ['id' => $originalLaneIds[0]]);
    }

    public function test_it_clears_heats_when_the_event_has_no_participants(): void
    {
        [$competition, $event] = $this->competitionWithEvent(lanes: 5);
        Heat::factory()->withLanes(5)->create(['event_id' => $event->id]);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('event-heats.generate', [$competition, $event]))
            ->assertSessionHas('generated_heats_count', 0);

        $this->assertDatabaseCount('heats', 0);
        $this->assertDatabaseCount('heat_lanes', 0);
    }

    public function test_it_respects_the_competition_lane_count(): void
    {
        [, $event] = $this->competitionWithEvent(lanes: 8);
        $this->enterParticipants($event, 10);

        app(GenerateEventHeats::class)->generate($event);

        $this->assertSame([5, 5], $this->swimmersPerHeat($event));
        $this->assertSame([8, 8], $this->lanesPerHeat($event));
    }

    public function test_the_event_must_belong_to_the_competition(): void
    {
        [$competition] = $this->competitionWithEvent();
        [, $otherEvent] = $this->competitionWithEvent();

        $this
            ->actingAs(User::factory()->create())
            ->post(route('event-heats.generate', [$competition, $otherEvent]))
            ->assertNotFound();
    }

    public function test_guests_cannot_generate_heats_for_every_event(): void
    {
        [$competition] = $this->competitionWithEvent();

        $this
            ->post(route('event-heats.generate-all', $competition))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('heats', 0);
    }

    public function test_it_generates_heats_for_every_event_in_the_competition(): void
    {
        $competition = Competition::factory()->create(['number_of_lane' => 5]);
        $firstEvent = Event::factory()->create(['competition_id' => $competition->id]);
        $secondEvent = Event::factory()->create(['competition_id' => $competition->id]);
        $this->enterParticipants($firstEvent, 12);
        $this->enterParticipants($secondEvent, 4);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('event-heats.generate-all', $competition))
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'heats-generated')
            ->assertSessionHas('generated_heats_summary', ['events' => 2, 'heats' => 4]);

        $this->assertSame([4, 4, 4], $this->swimmersPerHeat($firstEvent));
        $this->assertSame([4], $this->swimmersPerHeat($secondEvent));
    }

    public function test_generating_for_every_event_skips_events_without_participants(): void
    {
        $competition = Competition::factory()->create(['number_of_lane' => 5]);
        $seededEvent = Event::factory()->create(['competition_id' => $competition->id]);
        $emptyEvent = Event::factory()->create(['competition_id' => $competition->id]);
        $this->enterParticipants($seededEvent, 3);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('event-heats.generate-all', $competition))
            ->assertSessionHas('generated_heats_summary', ['events' => 1, 'heats' => 1]);

        $this->assertSame(1, $seededEvent->heats()->count());
        $this->assertSame(0, $emptyEvent->heats()->count());
    }

    public function test_generating_for_every_event_replaces_existing_heats(): void
    {
        $competition = Competition::factory()->create(['number_of_lane' => 5]);
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $this->enterParticipants($event, 3);
        $staleHeat = Heat::factory()->withLanes(5)->create([
            'event_id' => $event->id,
            'heat_number' => 7,
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('event-heats.generate-all', $competition))
            ->assertSessionHas('generated_heats_summary', ['events' => 1, 'heats' => 1]);

        $this->assertDatabaseMissing('heats', ['id' => $staleHeat->id]);
        $this->assertSame([1], $event->heats()->pluck('heat_number')->all());
    }

    public function test_generating_for_every_event_ignores_other_competitions(): void
    {
        $competition = Competition::factory()->create(['number_of_lane' => 5]);
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $this->enterParticipants($event, 3);

        [, $otherEvent] = $this->competitionWithEvent();
        $this->enterParticipants($otherEvent, 3);

        $this
            ->actingAs(User::factory()->create())
            ->post(route('event-heats.generate-all', $competition))
            ->assertSessionHas('generated_heats_summary', ['events' => 1, 'heats' => 1]);

        $this->assertSame(0, $otherEvent->heats()->count());
    }

    public function test_guests_cannot_swap_lanes(): void
    {
        [$competition, $event] = $this->competitionWithEvent();
        [$first, $second] = $this->twoSeatedLanes($event);

        $this
            ->patch(route('event-heat-lanes.swap', [$competition, $event]), [
                'from_lane_id' => $first->id,
                'to_lane_id' => $second->id,
            ])
            ->assertRedirect(route('login'));

        $this->assertSame($first->participant_id, $first->fresh()->participant_id);
    }

    public function test_swimmers_can_be_swapped_within_a_heat(): void
    {
        [$competition, $event] = $this->competitionWithEvent();
        [$first, $second] = $this->twoSeatedLanes($event);
        $firstSwimmer = $first->participant_id;
        $secondSwimmer = $second->participant_id;

        $this
            ->actingAs(User::factory()->create())
            ->patch(route('event-heat-lanes.swap', [$competition, $event]), [
                'from_lane_id' => $first->id,
                'to_lane_id' => $second->id,
            ])
            ->assertRedirect(route('events.show', [$competition, $event]))
            ->assertSessionHas('status', 'heat-lanes-swapped');

        $this->assertSame($secondSwimmer, $first->fresh()->participant_id);
        $this->assertSame($firstSwimmer, $second->fresh()->participant_id);
    }

    public function test_swimmers_can_be_exchanged_between_heats(): void
    {
        [$competition, $event] = $this->competitionWithEvent();
        $firstHeat = Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 1]);
        $secondHeat = Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 2]);
        [$alice, $bob] = $this->enterParticipants($event, 2);
        $firstLane = $firstHeat->assignLane(3, $alice);
        $secondLane = $secondHeat->assignLane(3, $bob);

        $this
            ->actingAs(User::factory()->create())
            ->patch(route('event-heat-lanes.swap', [$competition, $event]), [
                'from_lane_id' => $firstLane->id,
                'to_lane_id' => $secondLane->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($bob->id, $firstLane->fresh()->participant_id);
        $this->assertSame($alice->id, $secondLane->fresh()->participant_id);
    }

    public function test_a_swimmer_can_be_moved_into_an_empty_lane(): void
    {
        [$competition, $event] = $this->competitionWithEvent();
        $heat = Heat::factory()->create(['event_id' => $event->id]);
        [$alice] = $this->enterParticipants($event, 1);
        $occupied = $heat->assignLane(3, $alice);
        $empty = $heat->assignLane(1);

        $this
            ->actingAs(User::factory()->create())
            ->patch(route('event-heat-lanes.swap', [$competition, $event]), [
                'from_lane_id' => $occupied->id,
                'to_lane_id' => $empty->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($occupied->fresh()->participant_id);
        $this->assertSame($alice->id, $empty->fresh()->participant_id);
    }

    public function test_lanes_from_another_event_cannot_be_swapped(): void
    {
        [$competition, $event] = $this->competitionWithEvent();
        [$first] = $this->twoSeatedLanes($event);
        $foreignLane = HeatLane::factory()->create();

        $this
            ->actingAs(User::factory()->create())
            ->from(route('events.show', [$competition, $event]))
            ->patch(route('event-heat-lanes.swap', [$competition, $event]), [
                'from_lane_id' => $first->id,
                'to_lane_id' => $foreignLane->id,
            ])
            ->assertSessionHasErrors('to_lane_id');

        $this->assertSame($first->participant_id, $first->fresh()->participant_id);
    }

    public function test_a_lane_cannot_be_swapped_with_itself(): void
    {
        [$competition, $event] = $this->competitionWithEvent();
        [$first] = $this->twoSeatedLanes($event);

        $this
            ->actingAs(User::factory()->create())
            ->from(route('events.show', [$competition, $event]))
            ->patch(route('event-heat-lanes.swap', [$competition, $event]), [
                'from_lane_id' => $first->id,
                'to_lane_id' => $first->id,
            ])
            ->assertSessionHasErrors('to_lane_id');
    }

    /**
     * @return array{0: Competition, 1: Event}
     */
    private function competitionWithEvent(int $lanes = 5): array
    {
        $competition = Competition::factory()->create(['number_of_lane' => $lanes]);
        $event = Event::factory()->create(['competition_id' => $competition->id]);

        return [$competition, $event];
    }

    /**
     * Enter paid participants into the event.
     *
     * @return list<Participant>
     */
    private function enterParticipants(Event $event, int $count): array
    {
        $classification = Classification::factory()->create([
            'competition_id' => $event->competition_id,
        ]);

        $participants = Participant::factory()
            ->count($count)
            ->paid()
            ->create([
                'competition_id' => $event->competition_id,
                'classification_id' => $classification->id,
            ]);

        $event->participants()->attach($participants->pluck('id')->all());

        return $participants->all();
    }

    /**
     * Two occupied lanes in a single heat of the event.
     *
     * @return array{0: HeatLane, 1: HeatLane}
     */
    private function twoSeatedLanes(Event $event): array
    {
        $heat = Heat::factory()->create(['event_id' => $event->id]);
        [$alice, $bob] = $this->enterParticipants($event, 2);

        return [$heat->assignLane(2, $alice), $heat->assignLane(3, $bob)];
    }

    /**
     * @return list<int>
     */
    private function swimmersPerHeat(Event $event): array
    {
        return $event->heats()->get()
            ->map(fn (Heat $heat): int => $heat->lanes()->whereNotNull('participant_id')->count())
            ->all();
    }

    /**
     * @return list<int>
     */
    private function lanesPerHeat(Event $event): array
    {
        return $event->heats()->get()
            ->map(fn (Heat $heat): int => $heat->lanes()->count())
            ->all();
    }
}
