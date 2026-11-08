<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\CompetitionPointRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionPointRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_competition_seeds_default_point_rules(): void
    {
        $competition = Competition::factory()->create();

        $this->assertSame(8, $competition->pointRules()->count());
        $this->assertSame(8, $competition->pointRules()->where('place', 1)->value('points'));
        $this->assertSame(1, $competition->pointRules()->where('place', 8)->value('points'));
    }

    public function test_authenticated_users_can_replace_point_rules(): void
    {
        $competition = Competition::factory()->create();

        $this
            ->actingAs(User::factory()->create())
            ->put(route('competition-point-rules.update', $competition), [
                'rules' => [
                    ['place' => 1, 'points' => 10],
                    ['place' => 2, 'points' => 5],
                ],
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'point-rules-updated');

        $this->assertSame(2, $competition->pointRules()->count());
        $this->assertSame(10, CompetitionPointRule::query()
            ->where('competition_id', $competition->id)
            ->where('place', 1)
            ->value('points'));
    }

    public function test_duplicate_places_are_rejected(): void
    {
        $competition = Competition::factory()->create();

        $this
            ->actingAs(User::factory()->create())
            ->from(route('competitions.show', $competition))
            ->put(route('competition-point-rules.update', $competition), [
                'rules' => [
                    ['place' => 1, 'points' => 10],
                    ['place' => 1, 'points' => 5],
                ],
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors('rules');
    }
}
