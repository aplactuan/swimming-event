<?php

namespace Tests\Unit;

use App\Models\Competition;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NameSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_can_be_searched_by_last_name(): void
    {
        $competition = Competition::factory()->create();

        $matching = Participant::factory()->create([
            'competition_id' => $competition->id,
            'first_name' => 'Ava',
            'last_name' => 'Santos',
        ]);

        Participant::factory()->create([
            'competition_id' => $competition->id,
            'first_name' => 'Noah',
            'last_name' => 'Reyes',
        ]);

        $results = Participant::query()
            ->where('competition_id', $competition->id)
            ->searchByName('sant')
            ->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($matching));
    }

    public function test_participants_can_be_searched_by_full_name(): void
    {
        $competition = Competition::factory()->create();

        $matching = Participant::factory()->create([
            'competition_id' => $competition->id,
            'first_name' => 'Mia',
            'last_name' => 'Cruz',
        ]);

        Participant::factory()->create([
            'competition_id' => $competition->id,
            'first_name' => 'Mia',
            'last_name' => 'Lopez',
        ]);

        $results = Participant::query()
            ->where('competition_id', $competition->id)
            ->searchByName('mia cruz')
            ->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($matching));
    }

    public function test_participants_can_be_searched_by_last_name_first_display_name(): void
    {
        $competition = Competition::factory()->create();

        $matching = Participant::factory()->create([
            'competition_id' => $competition->id,
            'first_name' => 'Liam',
            'last_name' => 'Garcia',
        ]);

        $results = Participant::query()
            ->where('competition_id', $competition->id)
            ->searchByName('garcia, liam')
            ->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($matching));
    }

    public function test_participant_search_ignores_blank_queries(): void
    {
        $competition = Competition::factory()->create();

        Participant::factory()->count(2)->create([
            'competition_id' => $competition->id,
        ]);

        $results = Participant::query()
            ->where('competition_id', $competition->id)
            ->searchByName('   ')
            ->get();

        $this->assertCount(2, $results);
    }

    public function test_events_can_be_searched_by_name(): void
    {
        $competition = Competition::factory()->create();

        $matching = Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '50m Freestyle',
        ]);

        Event::factory()->create([
            'competition_id' => $competition->id,
            'name' => '100m Breaststroke',
        ]);

        $results = Event::query()
            ->where('competition_id', $competition->id)
            ->searchByName('free')
            ->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($matching));
    }

    public function test_event_search_ignores_blank_queries(): void
    {
        $competition = Competition::factory()->create();

        Event::factory()->count(2)->create([
            'competition_id' => $competition->id,
        ]);

        $results = Event::query()
            ->where('competition_id', $competition->id)
            ->searchByName('')
            ->get();

        $this->assertCount(2, $results);
    }
}
