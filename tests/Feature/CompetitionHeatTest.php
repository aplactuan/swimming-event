<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CompetitionHeatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_a_heat(): void
    {
        $competition = Competition::factory()->create();
        $heat = $this->heatFor($competition, sortOrder: 1);

        $this->get(route('competition-heats.show', [$competition, $heat]))
            ->assertRedirect(route('login'));
    }

    public function test_the_index_redirects_to_the_first_heat_of_the_program(): void
    {
        $competition = Competition::factory()->create();

        $second = $this->heatFor($competition, sortOrder: 2);
        $first = $this->heatFor($competition, sortOrder: 1);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-heats.index', $competition))
            ->assertRedirect(route('competition-heats.show', [$competition, $first]));

        $this->assertNotSame($first->id, $second->id);
    }

    public function test_the_index_falls_back_to_the_competition_when_there_are_no_heats(): void
    {
        $competition = Competition::factory()->create();

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-heats.index', $competition))
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'no-heats-to-run');
    }

    public function test_a_heat_is_shown_with_its_place_in_the_running_order(): void
    {
        $competition = Competition::factory()->create();

        $first = $this->heatFor($competition, sortOrder: 1);
        $second = $this->heatFor($competition, sortOrder: 1, heatNumber: 2, event: $first->event);
        $third = $this->heatFor($competition, sortOrder: 2);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-heats.show', [$competition, $second]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Heats/Show')
                ->where('position', 2)
                ->where('total', 3)
                ->where('previous_heat_id', $first->id)
                ->where('next_heat_id', $third->id)
                ->where('heat.id', $second->id)
                ->has('options', 3)
            );
    }

    public function test_the_first_heat_has_no_previous_and_the_last_has_no_next(): void
    {
        $competition = Competition::factory()->create();

        $first = $this->heatFor($competition, sortOrder: 1);
        $last = $this->heatFor($competition, sortOrder: 2);

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('competition-heats.show', [$competition, $first]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('previous_heat_id', null)
                ->where('next_heat_id', $last->id)
            );

        $this
            ->actingAs($user)
            ->get(route('competition-heats.show', [$competition, $last]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('previous_heat_id', $first->id)
                ->where('next_heat_id', null)
            );
    }

    public function test_the_running_order_follows_the_program_then_the_heat_number(): void
    {
        $competition = Competition::factory()->create();

        $secondEvent = Event::factory()->create([
            'competition_id' => $competition->id,
            'sort_order' => 2,
        ]);
        $firstEvent = Event::factory()->create([
            'competition_id' => $competition->id,
            'sort_order' => 1,
        ]);

        $secondEventHeat = Heat::factory()->create(['event_id' => $secondEvent->id]);
        $firstEventHeatTwo = Heat::factory()->create([
            'event_id' => $firstEvent->id,
            'heat_number' => 2,
        ]);
        $firstEventHeatOne = Heat::factory()->create([
            'event_id' => $firstEvent->id,
            'heat_number' => 1,
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-heats.show', [$competition, $firstEventHeatOne]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('options.0.id', $firstEventHeatOne->id)
                ->where('options.1.id', $firstEventHeatTwo->id)
                ->where('options.2.id', $secondEventHeat->id)
            );
    }

    public function test_a_heat_from_another_competition_is_not_found(): void
    {
        $competition = Competition::factory()->create();
        $other = $this->heatFor(Competition::factory()->create(), sortOrder: 1);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-heats.show', [$competition, $other]))
            ->assertNotFound();
    }

    public function test_finish_times_can_be_recorded_for_the_heat(): void
    {
        $competition = Competition::factory()->create();
        $heat = $this->heatFor($competition, sortOrder: 1);

        [$first, $second] = $this->swimmersIn($heat, $competition);

        $response = $this
            ->actingAs(User::factory()->create())
            ->patch(route('competition-heats.times.update', [$competition, $heat]), [
                'lanes' => [
                    ['id' => $first->id, 'finish_time' => '1:02.45'],
                    ['id' => $second->id, 'finish_time' => '38.2'],
                ],
            ]);

        $response
            ->assertRedirect(route('competition-heats.show', [$competition, $heat]))
            ->assertSessionHas('status', 'heat-times-saved');

        $this->assertSame(6245, $first->fresh()->finish_time_hundredths);
        $this->assertSame('1:02.45', $first->fresh()->finish_time);
        $this->assertSame(3820, $second->fresh()->finish_time_hundredths);
    }

    public function test_a_blank_finish_time_clears_a_recorded_one(): void
    {
        $competition = Competition::factory()->create();
        $heat = $this->heatFor($competition, sortOrder: 1);

        [$lane] = $this->swimmersIn($heat, $competition);
        $lane->forceFill(['finish_time_hundredths' => 3000])->save();

        $this
            ->actingAs(User::factory()->create())
            ->patch(route('competition-heats.times.update', [$competition, $heat]), [
                'lanes' => [['id' => $lane->id, 'finish_time' => '']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($lane->fresh()->finish_time_hundredths);
    }

    public function test_a_malformed_finish_time_is_rejected(): void
    {
        $competition = Competition::factory()->create();
        $heat = $this->heatFor($competition, sortOrder: 1);

        [$lane] = $this->swimmersIn($heat, $competition);

        $this
            ->actingAs(User::factory()->create())
            ->patch(route('competition-heats.times.update', [$competition, $heat]), [
                'lanes' => [['id' => $lane->id, 'finish_time' => 'fast']],
            ])
            ->assertSessionHasErrors('lanes.0.finish_time');

        $this->assertNull($lane->fresh()->finish_time_hundredths);
    }

    public function test_a_time_cannot_be_recorded_for_an_empty_lane(): void
    {
        $competition = Competition::factory()->create();
        $heat = $this->heatFor($competition, sortOrder: 1);

        $empty = $heat->lanes()->create(['lane_number' => 5]);

        $this
            ->actingAs(User::factory()->create())
            ->patch(route('competition-heats.times.update', [$competition, $heat]), [
                'lanes' => [['id' => $empty->id, 'finish_time' => '30.00']],
            ])
            ->assertSessionHasErrors('lanes.0.id');

        $this->assertNull($empty->fresh()->finish_time_hundredths);
    }

    public function test_a_lane_from_another_heat_cannot_be_timed(): void
    {
        $competition = Competition::factory()->create();
        $heat = $this->heatFor($competition, sortOrder: 1);
        $other = $this->heatFor($competition, sortOrder: 2);

        [$otherLane] = $this->swimmersIn($other, $competition);

        $this
            ->actingAs(User::factory()->create())
            ->patch(route('competition-heats.times.update', [$competition, $heat]), [
                'lanes' => [['id' => $otherLane->id, 'finish_time' => '30.00']],
            ])
            ->assertSessionHasErrors('lanes.0.id');

        $this->assertNull($otherLane->fresh()->finish_time_hundredths);
    }

    /**
     * Create a heat on a new event at the given place in the program.
     */
    private function heatFor(
        Competition $competition,
        int $sortOrder,
        int $heatNumber = 1,
        ?Event $event = null,
    ): Heat {
        $event ??= Event::factory()->create([
            'competition_id' => $competition->id,
            'sort_order' => $sortOrder,
        ]);

        return Heat::factory()->create([
            'event_id' => $event->id,
            'heat_number' => $heatNumber,
        ]);
    }

    /**
     * Seat two swimmers in the heat and return their lanes.
     *
     * @return list<HeatLane>
     */
    private function swimmersIn(Heat $heat, Competition $competition): array
    {
        return array_map(
            fn (int $laneNumber): HeatLane => $heat->assignLane(
                $laneNumber,
                Participant::factory()->paid()->create([
                    'competition_id' => $competition->id,
                ]),
            ),
            [1, 2],
        );
    }
}
