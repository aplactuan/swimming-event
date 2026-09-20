<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantAgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_age_is_computed_on_creation(): void
    {
        $participant = $this->participantFor('2026-06-01', '2015-06-15');

        $this->assertSame(10, $participant->age);
        $this->assertSame(10, $participant->fresh()->age);
    }

    public function test_age_counts_a_birthday_falling_on_competition_day(): void
    {
        $participant = $this->participantFor('2026-06-01', '2015-06-01');

        $this->assertSame(11, $participant->age);
    }

    public function test_age_is_not_incremented_by_a_birthday_after_competition_day(): void
    {
        $participant = $this->participantFor('2026-06-01', '2015-06-02');

        $this->assertSame(10, $participant->age);
    }

    public function test_age_is_recomputed_when_the_birthdate_changes(): void
    {
        $participant = $this->participantFor('2026-06-01', '2015-06-15');

        $participant->update(['birthdate' => '2012-01-10']);

        $this->assertSame(14, $participant->fresh()->age);
    }

    public function test_age_is_zero_for_a_participant_born_after_competition_day(): void
    {
        $participant = $this->participantFor('2026-06-01', '2027-01-01');

        $this->assertSame(0, $participant->age);
    }

    public function test_imported_participants_get_their_age(): void
    {
        $competition = Competition::factory()->create([
            'competition_date' => '2026-06-01',
        ]);

        $competition->participants()->save(
            Participant::factory()->make([
                'competition_id' => $competition->id,
                'birthdate' => '2015-06-15',
            ])
        );

        $this->assertSame(10, $competition->participants()->firstOrFail()->age);
    }

    private function participantFor(string $competitionDate, string $birthdate): Participant
    {
        $competition = Competition::factory()->create([
            'competition_date' => $competitionDate,
        ]);

        return Participant::factory()->create([
            'competition_id' => $competition->id,
            'birthdate' => $birthdate,
        ]);
    }
}
