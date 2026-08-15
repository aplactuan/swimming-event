<?php

namespace Tests\Feature;

use App\Models\Competition;
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
                ->has('competition.events', 0));
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
