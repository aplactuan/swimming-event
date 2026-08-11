<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_update_a_competition(): void
    {
        $competition = Competition::factory()->create();

        $response = $this->put(
            route('competitions.update', $competition),
            $this->validPayload(),
        );

        $response->assertRedirect(route('login'));
        $this->assertSame($competition->name, $competition->fresh()->name);
    }

    public function test_authenticated_users_can_update_a_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create([
            'name' => 'Old Meet Name',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('competitions.update', $competition), $this->validPayload([
                'name' => 'Updated Sprint Meet',
                'venue' => 'National Aquatic Centre',
                'entry_fee' => 3000,
            ]));

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'competition-updated');

        $competition->refresh();

        $this->assertSame('Updated Sprint Meet', $competition->name);
        $this->assertSame('National Aquatic Centre', $competition->venue);
        $this->assertSame('2026-09-15', $competition->competition_date->toDateString());
        $this->assertSame('07:30', $competition->warm_up_time);
        $this->assertSame('08:00', $competition->coaches_meeting_time);
        $this->assertSame('2026-09-01', $competition->registration_deadline->toDateString());
        $this->assertSame(3000, $competition->entry_fee);
    }

    public function test_competition_update_requires_valid_attributes(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('dashboard'))
            ->put(route('competitions.update', $competition), [
                'name' => '',
                'venue' => '',
                'competition_date' => '',
                'warm_up_time' => 'not-a-time',
                'coaches_meeting_time' => 'not-a-time',
                'registration_deadline' => '2026-09-20',
                'entry_fee' => -1,
            ]);

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'name',
                'venue',
                'competition_date',
                'warm_up_time',
                'coaches_meeting_time',
                'registration_deadline',
                'entry_fee',
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Summer Sprint Meet',
            'venue' => 'City Aquatic Centre',
            'competition_date' => '2026-09-15',
            'warm_up_time' => '07:30',
            'coaches_meeting_time' => '08:00',
            'registration_deadline' => '2026-09-01',
            'entry_fee' => 2500,
            ...$overrides,
        ];
    }
}
