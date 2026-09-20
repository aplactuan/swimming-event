<?php

namespace Tests\Feature;

use App\Enums\ParticipantGender;
use App\Models\Competition;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\SwimmingCompetitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SwimmingCompetitionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_default_seeder_computes_participant_ages(): void
    {
        $this->seed();

        $competition = Competition::query()->sole();

        $this->assertSame(100, $competition->participants()->count());
        $this->assertGreaterThan(0, $competition->participants()->where('age', '>', 0)->count());

        $competition->participants->each(function ($participant) use ($competition): void {
            $this->assertSame(
                max(0, (int) $participant->birthdate->diffInYears($competition->competition_date)),
                $participant->age,
            );
        });
    }

    public function test_it_seeds_the_competition_structure_and_participants(): void
    {
        Carbon::setTestNow('2026-09-17 12:00:00');

        try {
            $this->seed(SwimmingCompetitionSeeder::class);

            $user = User::query()->where('email', 'adrian@test.com')->firstOrFail();
            $competition = Competition::query()
                ->with(['classifications.ageBrackets'])
                ->sole();

            $this->assertSame('Adrian', $user->name);
            $this->assertTrue(Hash::check('password1234', $user->password));
            $this->assertSame('2026-10-17', $competition->competition_date->toDateString());
            $this->assertSame(100, $competition->entry_fee);
            $this->assertCount(2, $competition->classifications);
            $this->assertSame(100, $competition->participants()->count());
            $this->assertSame(100, $competition->participants()->whereBetween('age', [0, 10])->count());
            $this->assertGreaterThan(0, $competition->participants()->where('age', '>', 0)->count());

            $competition->participants->each(function ($participant) use ($competition): void {
                $this->assertSame(
                    max(0, (int) $participant->birthdate->diffInYears($competition->competition_date)),
                    $participant->age,
                );
            });

            $expectedGroups = [
                'Novice' => [
                    ParticipantGender::Male->value => 12,
                    ParticipantGender::Female->value => 14,
                ],
                'Developmental' => [
                    ParticipantGender::Male->value => 11,
                    ParticipantGender::Female->value => 13,
                ],
            ];

            foreach ($competition->classifications as $classification) {
                $this->assertCount(2, $classification->ageBrackets);

                $sixAndBelow = $classification->ageBrackets->firstWhere('name', '6 and below');
                $sevenToTen = $classification->ageBrackets->firstWhere('name', '7 - 10');

                $this->assertNotNull($sixAndBelow);
                $this->assertNotNull($sevenToTen);
                $this->assertSame('2019-10-18', $sixAndBelow->start_birthday->toDateString());
                $this->assertSame('2026-10-17', $sixAndBelow->end_birthday->toDateString());
                $this->assertSame('2015-10-18', $sevenToTen->start_birthday->toDateString());
                $this->assertSame('2019-10-17', $sevenToTen->end_birthday->toDateString());

                foreach ($expectedGroups[$classification->name] as $gender => $expectedCount) {
                    $participants = $competition->participants()
                        ->where('classification_id', $classification->id)
                        ->where('gender', $gender);

                    $this->assertSame($expectedCount, (clone $participants)
                        ->whereBetween('birthdate', [
                            $sixAndBelow->start_birthday,
                            $sixAndBelow->end_birthday,
                        ])
                        ->count());
                    $this->assertSame($expectedCount, (clone $participants)
                        ->whereBetween('birthdate', [
                            $sevenToTen->start_birthday,
                            $sevenToTen->end_birthday,
                        ])
                        ->count());
                }
            }
        } finally {
            Carbon::setTestNow();
        }
    }
}
