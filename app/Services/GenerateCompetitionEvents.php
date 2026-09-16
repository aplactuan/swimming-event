<?php

namespace App\Services;

use App\Enums\EventGender;
use App\Models\AgeBracket;
use App\Models\Competition;
use App\Models\Event;
use App\Models\EventEligibility;
use App\Models\Participant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateCompetitionEvents
{
    /**
     * @param  list<string>  $genders
     * @param  list<array{classification_id: string, age_bracket_id: string}>  $eligibilities
     */
    public function generate(
        Competition $competition,
        string $name,
        array $genders,
        array $eligibilities,
    ): int {
        return DB::transaction(function () use ($competition, $name, $genders, $eligibilities): int {
            Competition::query()
                ->whereKey($competition->id)
                ->lockForUpdate()
                ->firstOrFail();

            $nextSortOrder = (int) Event::query()
                ->whereBelongsTo($competition)
                ->max('sort_order') + 1;
            $timestamp = now();
            $eventRows = [];
            $eligibilityRows = [];
            $generatedEvents = [];

            foreach ($eligibilities as $eligibility) {
                foreach ($genders as $gender) {
                    $eventId = (string) Str::uuid();
                    $eventGender = EventGender::from($gender);

                    $eventRows[] = [
                        'id' => $eventId,
                        'competition_id' => $competition->id,
                        'name' => Str::squish($name),
                        'gender' => $eventGender->value,
                        'sort_order' => $nextSortOrder++,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                    $eligibilityRows[] = [
                        'id' => (string) Str::uuid(),
                        'event_id' => $eventId,
                        'classification_id' => $eligibility['classification_id'],
                        'age_bracket_id' => $eligibility['age_bracket_id'],
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                    $generatedEvents[] = [
                        'id' => $eventId,
                        'gender' => $eventGender,
                        'classification_id' => $eligibility['classification_id'],
                        'age_bracket_id' => $eligibility['age_bracket_id'],
                    ];
                }
            }

            Event::query()->insert($eventRows);
            EventEligibility::query()->insert($eligibilityRows);
            $this->attachMatchingPaidParticipants($competition, $generatedEvents, $timestamp);

            return count($eventRows);
        });
    }

    /**
     * @param  list<array{id: string, gender: EventGender, classification_id: string, age_bracket_id: string}>  $generatedEvents
     */
    private function attachMatchingPaidParticipants(
        Competition $competition,
        array $generatedEvents,
        CarbonInterface $timestamp,
    ): void {
        $classificationIds = collect($generatedEvents)
            ->pluck('classification_id')
            ->unique()
            ->values()
            ->all();
        $ageBrackets = AgeBracket::query()
            ->whereKey(collect($generatedEvents)->pluck('age_bracket_id')->unique()->values())
            ->get()
            ->keyBy('id');
        $participantsByClassification = Participant::query()
            ->whereBelongsTo($competition)
            ->where('paid', true)
            ->whereIn('classification_id', $classificationIds)
            ->get(['id', 'classification_id', 'gender', 'birthdate'])
            ->groupBy('classification_id');
        $pivotTable = (new Event)->participants()->getTable();
        $pivotRows = [];

        foreach ($generatedEvents as $generatedEvent) {
            $ageBracket = $ageBrackets->get($generatedEvent['age_bracket_id']);

            if ($ageBracket === null) {
                continue;
            }

            /** @var EloquentCollection<int, Participant> $participants */
            $participants = $participantsByClassification->get(
                $generatedEvent['classification_id'],
                new EloquentCollection,
            );

            foreach ($participants as $participant) {
                $matchesGender = $generatedEvent['gender'] === EventGender::Mixed
                    || $generatedEvent['gender']->value === $participant->gender->value;

                if (! $matchesGender || ! $ageBracket->matchesBirthdate($participant->birthdate)) {
                    continue;
                }

                $pivotRows[] = [
                    'event_id' => $generatedEvent['id'],
                    'participant_id' => $participant->id,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                if (count($pivotRows) === 1000) {
                    DB::table($pivotTable)->insertOrIgnore($pivotRows);
                    $pivotRows = [];
                }
            }
        }

        if ($pivotRows !== []) {
            DB::table($pivotTable)->insertOrIgnore($pivotRows);
        }
    }
}
