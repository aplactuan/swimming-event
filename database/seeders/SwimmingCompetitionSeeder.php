<?php

namespace Database\Seeders;

use App\Enums\ParticipantGender;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Participant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SwimmingCompetitionSeeder extends Seeder
{
    /**
     * Seed the swimming competition demo data.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Adrian',
            'email' => 'adrian@test.com',
            'password' => Hash::make('password1234'),
        ]);

        $competitionDate = today()->addMonthNoOverflow();

        $competition = Competition::query()->create([
            'name' => 'Adrian Swimming Competition',
            'venue' => 'City Aquatic Centre',
            'competition_date' => $competitionDate,
            'warm_up_time' => '07:30:00',
            'coaches_meeting_time' => '08:00:00',
            'registration_deadline' => $competitionDate->copy()->subWeek(),
            'entry_fee' => 100,
        ]);

        $sixAndBelowStart = $competitionDate->copy()->subYears(7)->addDay();
        $sixAndBelowEnd = $competitionDate->copy();
        $sevenToTenStart = $competitionDate->copy()->subYears(11)->addDay();
        $sevenToTenEnd = $competitionDate->copy()->subYears(7);

        $classifications = [
            $competition->classifications()->create([
                'name' => 'Novice',
                'parent_id' => null,
                'sort_order' => 1,
            ]),
            $competition->classifications()->create([
                'name' => 'Developmental',
                'parent_id' => null,
                'sort_order' => 2,
            ]),
        ];

        foreach ($classifications as $classification) {
            $classification->ageBrackets()->createMany([
                [
                    'name' => '6 and below',
                    'start_birthday' => $sixAndBelowStart,
                    'end_birthday' => $sixAndBelowEnd,
                    'sort_order' => 1,
                ],
                [
                    'name' => '7 - 10',
                    'start_birthday' => $sevenToTenStart,
                    'end_birthday' => $sevenToTenEnd,
                    'sort_order' => 2,
                ],
            ]);
        }

        $participantGroups = [
            ['classification' => $classifications[0], 'gender' => ParticipantGender::Male, 'count' => 12],
            ['classification' => $classifications[0], 'gender' => ParticipantGender::Female, 'count' => 14],
            ['classification' => $classifications[1], 'gender' => ParticipantGender::Male, 'count' => 11],
            ['classification' => $classifications[1], 'gender' => ParticipantGender::Female, 'count' => 13],
        ];

        foreach ($participantGroups as $group) {
            $this->createParticipants(
                competition: $competition,
                classification: $group['classification'],
                gender: $group['gender'],
                count: $group['count'],
                birthdateStart: $sixAndBelowStart,
                birthdateEnd: $sixAndBelowEnd,
            );

            $this->createParticipants(
                competition: $competition,
                classification: $group['classification'],
                gender: $group['gender'],
                count: $group['count'],
                birthdateStart: $sevenToTenStart,
                birthdateEnd: $sevenToTenEnd,
            );
        }
    }

    private function createParticipants(
        Competition $competition,
        Classification $classification,
        ParticipantGender $gender,
        int $count,
        CarbonInterface $birthdateStart,
        CarbonInterface $birthdateEnd,
    ): void {
        Participant::factory()
            ->count($count)
            ->create([
                'competition_id' => $competition->id,
                'classification_id' => $classification->id,
                'gender' => $gender,
                'birthdate' => fn (): string => fake()
                    ->dateTimeBetween($birthdateStart, $birthdateEnd)
                    ->format('Y-m-d'),
                'paid' => true,
            ]);
    }
}
