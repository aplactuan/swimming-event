<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Participant;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_create_a_team(): void
    {
        $competition = Competition::factory()->create();

        $this->post(route('teams.store', $competition), ['name' => 'Dolphins'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('teams', 0);
    }

    public function test_authenticated_users_can_create_a_team(): void
    {
        $competition = Competition::factory()->create();

        $this
            ->actingAs(User::factory()->create())
            ->post(route('teams.store', $competition), ['name' => 'Dolphins'])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'team-created');

        $this->assertDatabaseHas('teams', [
            'competition_id' => $competition->id,
            'name' => 'Dolphins',
        ]);
    }

    public function test_team_names_must_be_unique_within_a_competition(): void
    {
        $competition = Competition::factory()->create();
        Team::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Dolphins',
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->from(route('competitions.show', $competition))
            ->post(route('teams.store', $competition), ['name' => 'Dolphins'])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors('name');
    }

    public function test_authenticated_users_can_rename_a_team(): void
    {
        $competition = Competition::factory()->create();
        $team = Team::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Dolphins',
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->put(route('teams.update', [$competition, $team]), ['name' => 'Sharks'])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'team-updated');

        $this->assertSame('Sharks', $team->fresh()->name);
    }

    public function test_a_team_with_participants_cannot_be_deleted(): void
    {
        $competition = Competition::factory()->create();
        $team = Team::factory()->create(['competition_id' => $competition->id]);
        Participant::factory()->create([
            'competition_id' => $competition->id,
            'team_id' => $team->id,
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->delete(route('teams.destroy', [$competition, $team]))
            ->assertUnprocessable();

        $this->assertDatabaseHas('teams', ['id' => $team->id]);
    }

    public function test_an_empty_team_can_be_deleted(): void
    {
        $competition = Competition::factory()->create();
        $team = Team::factory()->create(['competition_id' => $competition->id]);

        $this
            ->actingAs(User::factory()->create())
            ->delete(route('teams.destroy', [$competition, $team]))
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'team-deleted');

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_competition_show_includes_teams(): void
    {
        $competition = Competition::factory()->create();
        Team::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Dolphins',
        ]);

        $this
            ->actingAs(User::factory()->create())
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Competitions/Show')
                ->has('teams', 1)
                ->where('teams.0.name', 'Dolphins')
                ->has('point_rules', 8));
    }
}
