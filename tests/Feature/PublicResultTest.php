<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_public_results_home(): void
    {
        $closed = Competition::factory()->closed()->create(['name' => 'City Championships']);
        Competition::factory()->create(['name' => 'Open Meet']);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->has('competitions', 1)
                ->where('competitions.0.id', $closed->id)
                ->where('competitions.0.name', 'City Championships'));
    }

    public function test_authenticated_users_can_also_view_the_public_home(): void
    {
        $this
            ->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Home'));
    }

    public function test_guests_can_view_results_for_a_closed_competition(): void
    {
        $competition = Competition::factory()->closed()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $heat = Heat::factory()->create(['event_id' => $event->id]);
        $lane = $this->swimmer($heat, $competition, laneNumber: 1, hundredths: 2800);

        $this->get(route('public-results.show', [$competition, $event]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Results')
                ->where('competition.id', $competition->id)
                ->has('entries', 1)
                ->where('entries.0.id', $lane->id)
                ->where('entries.0.finish_time', '0:28.00'));
    }

    public function test_the_public_index_redirects_to_the_first_event(): void
    {
        $competition = Competition::factory()->closed()->create();
        Event::factory()->create([
            'competition_id' => $competition->id,
            'sort_order' => 2,
        ]);
        $first = Event::factory()->create([
            'competition_id' => $competition->id,
            'sort_order' => 1,
        ]);

        $this->get(route('public-results.index', $competition))
            ->assertRedirect(route('public-results.show', [$competition, $first]));
    }

    public function test_a_closed_competition_with_no_events_still_has_a_public_page(): void
    {
        $competition = Competition::factory()->closed()->create();

        $this->get(route('public-results.index', $competition))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Results')
                ->where('event', null)
                ->has('entries', 0));
    }

    public function test_open_competitions_are_not_listed_or_viewable_publicly(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->has('competitions', 0));

        $this->get(route('public-results.index', $competition))->assertNotFound();
        $this->get(route('public-results.show', [$competition, $event]))->assertNotFound();
    }

    public function test_public_results_list_swimmers_fastest_first(): void
    {
        $competition = Competition::factory()->closed()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);
        $heat = Heat::factory()->create(['event_id' => $event->id]);

        $slowest = $this->swimmer($heat, $competition, laneNumber: 1, hundredths: 4500);
        $fastest = $this->swimmer($heat, $competition, laneNumber: 2, hundredths: 2800);

        $this->get(route('public-results.show', [$competition, $event]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('entries.0.id', $fastest->id)
                ->where('entries.1.id', $slowest->id));
    }

    public function test_public_event_results_pdf_can_be_downloaded_with_age_filter(): void
    {
        $competition = Competition::factory()->closed()->create([
            'competition_date' => '2026-06-01',
        ]);
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '25m Freestyle',
        ]);
        $heat = Heat::factory()->create(['event_id' => $event->id]);
        $this->swimmer($heat, $competition, laneNumber: 1, hundredths: 2800, birthdate: '2022-01-01');
        $this->swimmer($heat, $competition, laneNumber: 2, hundredths: 3000, birthdate: '2020-01-01');

        $response = $this->get(route('public-results.event-pdf', [
            'competition' => $competition,
            'event' => $event,
            'ages' => [4],
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString(
            'ages-4',
            strtolower((string) $response->headers->get('content-disposition')),
        );
    }

    public function test_open_competition_event_pdf_is_not_public(): void
    {
        $competition = Competition::factory()->create();
        $event = Event::factory()->create(['competition_id' => $competition->id]);

        $this->get(route('public-results.event-pdf', [$competition, $event]))
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
