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

class CompetitionResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_a_result_sheet(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);

        $this->get(route('competition-results.show', [$competition, $event]))
            ->assertRedirect(route('login'));
    }

    public function test_the_index_redirects_to_the_first_event_of_the_program(): void
    {
        $competition = Competition::factory()->create();

        $second = Event::factory()->create([
            'competition_id' => $competition->id,
            'sort_order' => 2,
        ]);
        $first = Event::factory()->create([
            'competition_id' => $competition->id,
            'sort_order' => 1,
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.index', $competition))
            ->assertRedirect(route('competition-results.show', [$competition, $first]));

        $this->assertNotSame($first->id, $second->id);
    }

    public function test_the_index_falls_back_to_the_competition_when_there_are_no_events(): void
    {
        $competition = Competition::factory()->create();

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.index', $competition))
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'no-results-to-show');
    }

    public function test_swimmers_are_listed_fastest_first_across_every_heat(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);

        $firstHeat = Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 1]);
        $secondHeat = Heat::factory()->create(['event_id' => $event->id, 'heat_number' => 2]);

        $slowest = $this->swimmer($firstHeat, $competition, laneNumber: 1, hundredths: 4500);
        $fastest = $this->swimmer($secondHeat, $competition, laneNumber: 3, hundredths: 2800);
        $middle = $this->swimmer($firstHeat, $competition, laneNumber: 2, hundredths: 3100);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.show', [$competition, $event]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Results/Show')
                ->has('entries', 3)
                ->where('entries.0.id', $fastest->id)
                ->where('entries.0.finish_time', '0:28.00')
                ->where('entries.0.heat_number', 2)
                ->where('entries.0.lane_number', 3)
                ->where('entries.1.id', $middle->id)
                ->where('entries.2.id', $slowest->id)
            );
    }

    public function test_swimmers_without_a_time_are_listed_last(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $heat = Heat::factory()->create(['event_id' => $event->id]);

        $untimed = $this->swimmer($heat, $competition, laneNumber: 1, hundredths: null);
        $timed = $this->swimmer($heat, $competition, laneNumber: 2, hundredths: 3000);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.show', [$competition, $event]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('entries.0.id', $timed->id)
                ->where('entries.1.id', $untimed->id)
                ->where('entries.1.finish_time', null)
            );
    }

    public function test_empty_lanes_are_left_off_the_result_sheet(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $heat = Heat::factory()->create(['event_id' => $event->id]);

        $swimmer = $this->swimmer($heat, $competition, laneNumber: 1, hundredths: 3000);
        $heat->assignLane(2);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.show', [$competition, $event]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('entries', 1)
                ->where('entries.0.id', $swimmer->id)
            );
    }

    public function test_the_ages_swimming_the_event_are_offered_once_each_in_order(): void
    {
        $competition = Competition::factory()->create(['competition_date' => '2026-06-01']);
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $heat = Heat::factory()->create(['event_id' => $event->id]);

        $this->swimmer($heat, $competition, laneNumber: 1, hundredths: 3000, birthdate: '2020-01-01');
        $this->swimmer($heat, $competition, laneNumber: 2, hundredths: 3100, birthdate: '2022-01-01');
        $this->swimmer($heat, $competition, laneNumber: 3, hundredths: 3200, birthdate: '2020-01-01');

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.show', [$competition, $event]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ages', [4, 6])
                ->where('entries.0.participant.age', 6)
            );
    }

    public function test_the_sheet_carries_its_place_in_the_program(): void
    {
        $competition = Competition::factory()->create();

        $first = Event::factory()->create(['competition_id' => $competition->id, 'sort_order' => 1]);
        $second = Event::factory()->create(['competition_id' => $competition->id, 'sort_order' => 2]);
        $third = Event::factory()->create(['competition_id' => $competition->id, 'sort_order' => 3]);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.show', [$competition, $second]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('position', 2)
                ->where('total', 3)
                ->where('previous_event_id', $first->id)
                ->where('next_event_id', $third->id)
                ->where('options.0.id', $first->id)
                ->where('options.2.id', $third->id)
                ->has('options', 3)
            );
    }

    public function test_an_event_from_another_competition_is_not_found(): void
    {
        $competition = Competition::factory()->create();
        $other = Event::factory()->create([
            'competition_id' => Competition::factory()->create()->id,
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competition-results.show', [$competition, $other]))
            ->assertNotFound();
    }

    /**
     * Seat a swimmer in the heat with the given finish time.
     */
    private function swimmer(
        Heat $heat,
        Competition $competition,
        int $laneNumber,
        ?int $hundredths,
        ?string $birthdate = null,
    ): HeatLane {
        $participant = Participant::factory()->paid()->create(array_filter([
            'competition_id' => $competition->id,
            'birthdate' => $birthdate,
        ]));

        $lane = $heat->assignLane($laneNumber, $participant);
        $lane->forceFill(['finish_time_hundredths' => $hundredths])->save();

        return $lane;
    }
}
