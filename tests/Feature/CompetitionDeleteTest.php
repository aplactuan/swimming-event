<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_delete_a_competition(): void
    {
        $competition = Competition::factory()->create();

        $response = $this->delete(route('competitions.destroy', $competition));

        $response->assertRedirect(route('login'));
        $this->assertModelExists($competition);
    }

    public function test_authenticated_users_can_delete_a_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('competitions.destroy', $competition));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'competition-deleted');

        $this->assertModelMissing($competition);
    }
}
