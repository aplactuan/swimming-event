<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('competitions', 0));
    }

    public function test_dashboard_lists_upcoming_competitions_only(): void
    {
        $user = User::factory()->create();

        $upcoming = Competition::factory()->create([
            'name' => 'City Championships',
            'competition_date' => now()->addDays(10)->toDateString(),
            'registration_deadline' => now()->addDays(3)->toDateString(),
        ]);

        Competition::factory()->create([
            'name' => 'Past Sprint Meet',
            'competition_date' => now()->subDays(5)->toDateString(),
            'registration_deadline' => now()->subDays(15)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('competitions', 1)
                ->where('competitions.0.id', $upcoming->id)
                ->where('competitions.0.name', 'City Championships'));
    }
}
