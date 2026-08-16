<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompetitionShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_a_competition(): void
    {
        $competition = Competition::factory()->create();

        $response = $this->get(route('competitions.show', $competition));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_a_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create([
            'name' => 'City Championships',
            'venue' => 'City Aquatic Centre',
            'competition_date' => '2026-09-15',
            'warm_up_time' => '07:30:00',
            'coaches_meeting_time' => '08:00:00',
            'registration_deadline' => '2026-09-01',
            'entry_fee' => 2500,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('competitions.show', $competition));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Competitions/Show')
                ->where('competition.id', $competition->id)
                ->where('competition.name', 'City Championships')
                ->where('competition.venue', 'City Aquatic Centre')
                ->where('competition.competition_date', '2026-09-15')
                ->where('competition.warm_up_time', '07:30')
                ->where('competition.coaches_meeting_time', '08:00')
                ->where('competition.registration_deadline', '2026-09-01')
                ->where('competition.entry_fee', 2500)
                ->has('competition.classifications', 0)
                ->missing('competition.events')
                ->missing('competition.participants')
                ->has('participants.data', 0)
                ->where('participants.meta.total', 0)
                ->has('events.data', 0)
                ->where('events.meta.total', 0)
                ->where('filters.participant_search', '')
                ->where('filters.event_search', ''));
    }

    public function test_participants_and_events_are_paginated(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        Participant::factory()->count(12)->create([
            'competition_id' => $competition->id,
        ]);

        Event::factory()->count(12)->sequence(
            fn ($sequence) => [
                'competition_id' => $competition->id,
                'name' => 'Event '.$sequence->index,
                'sort_order' => $sequence->index + 1,
            ],
        )->create();

        $response = $this
            ->actingAs($user)
            ->get(route('competitions.show', $competition));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Competitions/Show')
                ->has('participants.data', 10)
                ->where('participants.meta.total', 12)
                ->where('participants.meta.current_page', 1)
                ->where('participants.meta.last_page', 2)
                ->has('events.data', 10)
                ->where('events.meta.total', 12)
                ->where('events.meta.current_page', 1)
                ->where('events.meta.last_page', 2));
    }

    public function test_participants_can_be_searched_and_paginated(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        Participant::factory()->create([
            'competition_id' => $competition->id,
            'first_name' => 'Ava',
            'last_name' => 'Santos',
        ]);

        Participant::factory()->create([
            'competition_id' => $competition->id,
            'first_name' => 'Noah',
            'last_name' => 'Reyes',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('competitions.show', [
                'competition' => $competition,
                'participant_search' => 'santos',
            ]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Competitions/Show')
                ->has('participants.data', 1)
                ->where('participants.data.0.last_name', 'Santos')
                ->where('participants.meta.total', 1)
                ->where('filters.participant_search', 'santos'));
    }

    public function test_events_can_be_searched_and_paginated(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '50m Freestyle',
            'sort_order' => 1,
        ]);

        Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '100m Breaststroke',
            'sort_order' => 2,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('competitions.show', [
                'competition' => $competition,
                'event_search' => 'free',
            ]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Competitions/Show')
                ->has('events.data', 1)
                ->where('events.data.0.name', '50m Freestyle')
                ->where('events.meta.total', 1)
                ->where('filters.event_search', 'free'));
    }

    public function test_viewing_a_missing_competition_returns_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('competitions.show', '00000000-0000-0000-0000-000000000000'));

        $response->assertNotFound();
    }
}
