<?php

namespace Tests\Feature\Api\Event;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_list_events(): void
    {
        Event::factory()->count(3)->create();

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'date_start', 'date_end'],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_events_are_ordered_by_date_start_ascending(): void
    {
        $later = Event::factory()->create([
            'name' => 'Later Meet',
            'date_start' => '2026-09-01',
            'date_end' => '2026-09-02',
        ]);
        $earlier = Event::factory()->create([
            'name' => 'Earlier Meet',
            'date_start' => '2026-08-01',
            'date_end' => '2026-08-02',
        ]);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonPath('data.0.id', $earlier->id)
            ->assertJsonPath('data.1.id', $later->id);
    }

    public function test_guests_can_view_an_event(): void
    {
        $event = Event::factory()->create([
            'name' => 'Summer Sprint Meet',
            'description' => 'A weekend swimming competition.',
            'date_start' => '2026-08-10',
            'date_end' => '2026-08-12',
        ]);

        $this->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $event->id)
            ->assertJsonPath('data.name', 'Summer Sprint Meet')
            ->assertJsonPath('data.description', 'A weekend swimming competition.')
            ->assertJsonPath('data.date_start', '2026-08-10')
            ->assertJsonPath('data.date_end', '2026-08-12');
    }

    public function test_viewing_a_missing_event_returns_not_found(): void
    {
        $this->getJson('/api/v1/events/999')
            ->assertNotFound();
    }

    public function test_guests_cannot_create_events(): void
    {
        $this->postJson('/api/v1/events', [
            'name' => 'Summer Sprint Meet',
            'date_start' => '2026-08-10',
            'date_end' => '2026-08-12',
        ])->assertUnauthorized();
    }

    public function test_an_authenticated_user_can_create_an_event(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/events', [
                'name' => 'Summer Sprint Meet',
                'description' => 'A weekend swimming competition.',
                'date_start' => '2026-08-10',
                'date_end' => '2026-08-12',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Summer Sprint Meet')
            ->assertJsonPath('data.date_start', '2026-08-10')
            ->assertJsonPath('data.date_end', '2026-08-12');

        $this->assertDatabaseHas('events', [
            'name' => 'Summer Sprint Meet',
            'description' => 'A weekend swimming competition.',
        ]);
    }

    public function test_creating_an_event_requires_valid_data(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/events', [
                'name' => '',
                'date_start' => '2026-08-12',
                'date_end' => '2026-08-10',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'date_end']);
    }

    public function test_guests_cannot_update_events(): void
    {
        $event = Event::factory()->create();

        $this->putJson("/api/v1/events/{$event->id}", [
            'name' => 'Updated Meet',
        ])->assertUnauthorized();
    }

    public function test_an_authenticated_user_can_update_an_event(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;
        $event = Event::factory()->create([
            'name' => 'Original Meet',
            'date_start' => '2026-08-10',
            'date_end' => '2026-08-12',
        ]);

        $this->withToken($token)
            ->putJson("/api/v1/events/{$event->id}", [
                'name' => 'Updated Meet',
                'date_end' => '2026-08-14',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Meet')
            ->assertJsonPath('data.date_end', '2026-08-14');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Updated Meet',
        ]);
    }

    public function test_updating_an_event_rejects_an_end_date_before_start_date(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;
        $event = Event::factory()->create([
            'date_start' => '2026-08-10',
            'date_end' => '2026-08-12',
        ]);

        $this->withToken($token)
            ->putJson("/api/v1/events/{$event->id}", [
                'date_end' => '2026-08-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_end']);
    }

    public function test_guests_cannot_delete_events(): void
    {
        $event = Event::factory()->create();

        $this->deleteJson("/api/v1/events/{$event->id}")
            ->assertUnauthorized();
    }

    public function test_an_authenticated_user_can_delete_an_event(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;
        $event = Event::factory()->create();

        $this->withToken($token)
            ->deleteJson("/api/v1/events/{$event->id}")
            ->assertNoContent();

        $this->assertModelMissing($event);
    }
}
