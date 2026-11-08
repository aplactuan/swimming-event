<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Enums\HeatLaneStatus;
use App\Models\Competition;
use App\Models\Event;
use App\Models\HeatLane;
use Database\Seeders\SampleInvitationalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SampleInvitationalSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_full_sample_invitational(): void
    {
        $this->seed(SampleInvitationalSeeder::class);

        $competition = Competition::query()->where('name', 'Sample Invitational')->first();

        $this->assertNotNull($competition);
        $this->assertSame(4, $competition->number_of_lane);
        $this->assertSame('2026-11-14', $competition->competition_date->toDateString());
        $this->assertSame(4, $competition->teams()->count());
        $this->assertSame(2, $competition->classifications()->count());
        $this->assertSame(8, $competition->pointRules()->count());
        $this->assertSame(128, $competition->participants()->count());
        $this->assertSame(49, $competition->events()->count());
        $this->assertSame(48, $competition->events()->where('type', EventType::Individual)->count());
        $this->assertSame(1, $competition->events()->where('type', EventType::Relay)->count());

        foreach ($competition->classifications as $classification) {
            $this->assertSame(
                ['6 and under', '7', '8', 'above 8'],
                $classification->ageBrackets()->orderBy('sort_order')->pluck('name')->all(),
            );
        }

        $individual = Event::query()
            ->where('competition_id', $competition->id)
            ->where('type', EventType::Individual)
            ->withCount('participants')
            ->first();

        $this->assertNotNull($individual);
        $this->assertSame(8, $individual->participants_count);
        $this->assertSame(2, $individual->heats()->count());

        $relay = Event::query()
            ->where('competition_id', $competition->id)
            ->where('type', EventType::Relay)
            ->first();

        $this->assertNotNull($relay);
        $this->assertSame(4, $relay->teams()->count());
        $this->assertSame(1, $relay->heats()->count());

        $this->assertSame(
            3,
            HeatLane::query()
                ->whereHas('heat.event', fn ($query) => $query->where('competition_id', $competition->id))
                ->where('status', HeatLaneStatus::Ns)
                ->count(),
        );

        $this->assertTrue(
            HeatLane::query()
                ->whereHas('heat.event', fn ($query) => $query->where('competition_id', $competition->id))
                ->where('status', HeatLaneStatus::Swam)
                ->whereNotNull('finish_time_hundredths')
                ->exists(),
        );
    }
}
