<?php

namespace Database\Seeders;

use App\Enums\EventGender;
use App\Enums\EventType;
use App\Enums\HeatLaneStatus;
use App\Enums\ParticipantGender;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\CompetitionPointRule;
use App\Models\HeatLane;
use App\Models\Participant;
use App\Models\Team;
use App\Models\User;
use App\Services\GenerateEventHeats;
use App\Services\ParticipantEventSync;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleInvitationalSeeder extends Seeder
{
    private const COMPETITION_DATE = '2026-11-14';

    /**
     * @var list<string>
     */
    private const TEAM_NAMES = [
        'Harbor Dolphins',
        'Coral Sharks',
        'Bay Barracudas',
        'Lagoon Stingrays',
    ];

    /**
     * @var list<string>
     */
    private const STROKES = [
        '25m Freestyle',
        '25m Breaststroke',
        '25m Backstroke',
    ];

    /**
     * Seed a full sample meet with teams, events, entries, heats, and times.
     */
    public function run(GenerateEventHeats $heats, ParticipantEventSync $sync): void
    {
        User::query()->updateOrCreate(
            ['email' => 'adrian@test.com'],
            [
                'name' => 'Adrian',
                'password' => Hash::make('password1234'),
                'email_verified_at' => now(),
            ],
        );

        $competitionDate = Carbon::parse(self::COMPETITION_DATE)->startOfDay();

        $competition = Competition::query()->create([
            'name' => 'Sample Invitational',
            'venue' => 'City Aquatic Centre',
            'number_of_lane' => 4,
            'competition_date' => $competitionDate,
            'warm_up_time' => '07:30:00',
            'coaches_meeting_time' => '08:00:00',
            'registration_deadline' => $competitionDate->copy()->subWeek(),
            'entry_fee' => 500,
        ]);

        CompetitionPointRule::seedDefaultsFor($competition);

        $teams = collect(self::TEAM_NAMES)
            ->map(fn (string $name): Team => $competition->teams()->create(['name' => $name]))
            ->values();

        $bracketDefs = $this->ageBracketDefinitions($competitionDate);
        $classifications = [];

        foreach (['Novice', 'Developmental'] as $index => $name) {
            $classification = $competition->classifications()->create([
                'name' => $name,
                'parent_id' => null,
                'sort_order' => $index + 1,
            ]);

            $brackets = [];

            foreach ($bracketDefs as $bracketIndex => $def) {
                $brackets[$def['name']] = $classification->ageBrackets()->create([
                    'name' => $def['name'],
                    'start_birthday' => $def['start_birthday'],
                    'end_birthday' => $def['end_birthday'],
                    'sort_order' => $bracketIndex + 1,
                ]);
            }

            $classifications[$name] = [
                'model' => $classification,
                'brackets' => $brackets,
            ];
        }

        $sortOrder = 1;
        $relayEligibilities = [];

        foreach ($classifications as $classificationData) {
            /** @var Classification $classification */
            $classification = $classificationData['model'];

            foreach ($classificationData['brackets'] as $bracket) {
                /** @var AgeBracket $bracket */
                $relayEligibilities[] = [
                    'classification_id' => $classification->id,
                    'age_bracket_id' => $bracket->id,
                ];

                foreach ([EventGender::Male, EventGender::Female] as $gender) {
                    foreach (self::STROKES as $stroke) {
                        $event = $competition->events()->create([
                            'name' => $stroke,
                            'gender' => $gender,
                            'type' => EventType::Individual,
                            'sort_order' => $sortOrder++,
                        ]);

                        $event->syncEligibilities([
                            [
                                'classification_id' => $classification->id,
                                'age_bracket_id' => $bracket->id,
                            ],
                        ]);
                    }
                }
            }
        }

        $relay = $competition->events()->create([
            'name' => '4x25m Freestyle',
            'gender' => EventGender::Mixed,
            'type' => EventType::Relay,
            'sort_order' => $sortOrder,
        ]);
        $relay->syncEligibilities($relayEligibilities);
        $relay->teams()->attach($teams->pluck('id'));

        $teamCursor = 0;

        foreach ($classifications as $classificationData) {
            /** @var Classification $classification */
            $classification = $classificationData['model'];

            foreach ($bracketDefs as $def) {
                /** @var AgeBracket $bracket */
                $bracket = $classificationData['brackets'][$def['name']];

                foreach ([ParticipantGender::Male, ParticipantGender::Female] as $gender) {
                    for ($index = 0; $index < 8; $index++) {
                        $team = $teams[$teamCursor % $teams->count()];
                        $teamCursor++;

                        $participant = Participant::factory()->paid()->create([
                            'competition_id' => $competition->id,
                            'classification_id' => $classification->id,
                            'team_id' => $team->id,
                            'gender' => $gender,
                            'birthdate' => fake()->dateTimeBetween(
                                $def['sample_start'],
                                $def['sample_end'],
                            )->format('Y-m-d'),
                        ]);

                        $sync->syncForPaid($participant);
                    }
                }
            }
        }

        $heats->generateForCompetition($competition);
        $this->seedLaneResults($competition);

        $this->command?->info(sprintf(
            'Seeded "%s" (%s): %d teams, %d participants, %d events, heats with sample times.',
            $competition->name,
            $competition->id,
            $teams->count(),
            $competition->participants()->count(),
            $competition->events()->count(),
        ));
    }

    /**
     * Birthday windows for competition day 2026-11-14.
     *
     * @return list<array{
     *     name: string,
     *     start_birthday: string|null,
     *     end_birthday: string|null,
     *     sample_start: string,
     *     sample_end: string
     * }>
     */
    private function ageBracketDefinitions(Carbon $competitionDate): array
    {
        return [
            [
                'name' => '6 and under',
                'start_birthday' => $competitionDate->copy()->subYears(7)->addDay()->toDateString(),
                'end_birthday' => $competitionDate->toDateString(),
                'sample_start' => $competitionDate->copy()->subYears(5)->toDateString(),
                'sample_end' => $competitionDate->copy()->subYears(3)->toDateString(),
            ],
            [
                'name' => '7',
                'start_birthday' => $competitionDate->copy()->subYears(8)->addDay()->toDateString(),
                'end_birthday' => $competitionDate->copy()->subYears(7)->toDateString(),
                'sample_start' => $competitionDate->copy()->subYears(8)->addMonths(2)->toDateString(),
                'sample_end' => $competitionDate->copy()->subYears(7)->subMonths(2)->toDateString(),
            ],
            [
                'name' => '8',
                'start_birthday' => $competitionDate->copy()->subYears(9)->addDay()->toDateString(),
                'end_birthday' => $competitionDate->copy()->subYears(8)->toDateString(),
                'sample_start' => $competitionDate->copy()->subYears(9)->addMonths(2)->toDateString(),
                'sample_end' => $competitionDate->copy()->subYears(8)->subMonths(2)->toDateString(),
            ],
            [
                'name' => 'above 8',
                'start_birthday' => null,
                'end_birthday' => $competitionDate->copy()->subYears(9)->toDateString(),
                'sample_start' => $competitionDate->copy()->subYears(13)->toDateString(),
                'sample_end' => $competitionDate->copy()->subYears(10)->toDateString(),
            ],
        ];
    }

    /**
     * Fill assigned lanes with finish times, marking a few as NS.
     */
    private function seedLaneResults(Competition $competition): void
    {
        $assignedLanes = HeatLane::query()
            ->whereHas('heat.event', fn ($query) => $query->where('competition_id', $competition->id))
            ->where(fn ($query) => $query->whereNotNull('participant_id')->orWhereNotNull('team_id'))
            ->with('heat.event')
            ->orderBy('id')
            ->get();

        $nsIds = $assignedLanes
            ->filter(fn (HeatLane $lane): bool => $lane->participant_id !== null)
            ->take(3)
            ->pluck('id')
            ->all();

        foreach ($assignedLanes as $index => $lane) {
            if (in_array($lane->id, $nsIds, true)) {
                $lane->forceFill([
                    'status' => HeatLaneStatus::Ns,
                    'finish_time_hundredths' => null,
                ])->save();

                continue;
            }

            $base = $lane->heat->event->type === EventType::Relay ? 5500 : 2800;
            $lane->forceFill([
                'status' => HeatLaneStatus::Swam,
                'finish_time_hundredths' => $base + ($index % 17) * 35 + (($index * 7) % 40),
            ])->save();
        }
    }
}
