<?php

namespace Tests\Feature\Models;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_event_can_be_created(): void
    {
        $event = Event::factory()->create([
            'name' => 'Summer Sprint Meet',
            'description' => 'A weekend swimming competition.',
            'date_start' => '2026-08-10',
            'date_end' => '2026-08-12',
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Summer Sprint Meet',
            'description' => 'A weekend swimming competition.',
        ]);

        $this->assertSame('date', $event->getCasts()['date_start']);
        $this->assertSame('date', $event->getCasts()['date_end']);
        $this->assertSame('2026-08-10', $event->date_start->toDateString());
        $this->assertSame('2026-08-12', $event->date_end->toDateString());
    }
}
