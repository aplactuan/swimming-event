<?php

namespace Tests\Feature;

use App\Models\Competition;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompetitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_competition_can_be_created_with_required_attributes(): void
    {
        $competition = Competition::factory()->create([
            'name' => 'Summer Sprint Meet',
            'venue' => 'City Aquatic Centre',
            'competition_date' => '2026-09-15',
            'warm_up_time' => '07:30:00',
            'coaches_meeting_time' => '08:00:00',
            'registration_deadline' => '2026-09-01',
            'entry_fee' => 2500,
        ]);

        $this->assertModelExists($competition);
        $this->assertTrue(Str::isUuid($competition->id));
        $this->assertSame('Summer Sprint Meet', $competition->name);
        $this->assertSame('City Aquatic Centre', $competition->venue);
        $this->assertInstanceOf(CarbonInterface::class, $competition->competition_date);
        $this->assertSame('2026-09-15', $competition->competition_date->toDateString());
        $this->assertSame('07:30:00', $competition->warm_up_time);
        $this->assertSame('08:00:00', $competition->coaches_meeting_time);
        $this->assertInstanceOf(CarbonInterface::class, $competition->registration_deadline);
        $this->assertSame('2026-09-01', $competition->registration_deadline->toDateString());
        $this->assertSame(2500, $competition->entry_fee);
    }

    public function test_competition_factory_persists_a_valid_competition(): void
    {
        $competition = Competition::factory()->create();

        $this->assertModelExists($competition);
        $this->assertNotEmpty($competition->name);
        $this->assertNotEmpty($competition->venue);
        $this->assertNotNull($competition->competition_date);
        $this->assertNotNull($competition->registration_deadline);
        $this->assertIsInt($competition->entry_fee);
        $this->assertGreaterThanOrEqual(0, $competition->entry_fee);
    }

    public function test_warm_up_and_coaches_meeting_times_can_be_null(): void
    {
        $competition = Competition::factory()->create([
            'warm_up_time' => null,
            'coaches_meeting_time' => null,
        ]);

        $this->assertModelExists($competition);
        $this->assertNull($competition->warm_up_time);
        $this->assertNull($competition->coaches_meeting_time);
    }
}
