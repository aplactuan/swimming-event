<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_create_a_competition(): void
    {
        $response = $this->post(route('competitions.store'), $this->validPayload());

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('competitions', 0);
    }

    public function test_authenticated_users_can_create_a_competition(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('competitions.store'), $this->validPayload());

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'competition-created');

        $competition = Competition::query()->first();

        $this->assertNotNull($competition);
        $this->assertSame('Summer Sprint Meet', $competition->name);
        $this->assertSame('City Aquatic Centre', $competition->venue);
        $this->assertSame('2026-09-15', $competition->competition_date->toDateString());
        $this->assertSame('07:30', $competition->warm_up_time);
        $this->assertSame('08:00', $competition->coaches_meeting_time);
        $this->assertSame('2026-09-01', $competition->registration_deadline->toDateString());
        $this->assertSame(2500, $competition->entry_fee);
    }

    public function test_warm_up_and_coaches_meeting_times_are_optional(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('competitions.store'), $this->validPayload([
                'warm_up_time' => '',
                'coaches_meeting_time' => '',
            ]));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $competition = Competition::query()->first();

        $this->assertNotNull($competition);
        $this->assertNull($competition->warm_up_time);
        $this->assertNull($competition->coaches_meeting_time);
    }

    public function test_competition_requires_valid_attributes(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('competitions.store'), [
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

        $this->assertDatabaseCount('competitions', 0);
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
