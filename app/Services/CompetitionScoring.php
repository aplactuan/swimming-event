<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\HeatLaneStatus;
use App\Models\Competition;
use App\Models\Event;
use App\Models\HeatLane;
use Illuminate\Support\Collection;

class CompetitionScoring
{
    /**
     * Place → points map for the competition.
     *
     * @return array<int, float>
     */
    public function pointMap(Competition $competition): array
    {
        return $competition->pointRules()
            ->orderBy('place')
            ->pluck('points', 'place')
            ->map(fn ($points): float => (float) $points)
            ->all();
    }

    /**
     * Rank assigned lanes with place, points, and NS handling.
     *
     * Timed swam lanes place by time with ties sharing the place block and
     * averaging points across that block. Untimed swam lanes and NS lanes
     * receive no place or points and sort after timed results.
     *
     * @return Collection<int, array{
     *     lane: HeatLane,
     *     place: int|null,
     *     points: float|null,
     *     status: string
     * }>
     */
    public function scoreEvent(Event $event, ?Competition $competition = null): Collection
    {
        $competition ??= $event->competition;
        $pointMap = $this->pointMap($competition);

        $lanes = $event->heatLanes()
            ->where(function ($query): void {
                $query->whereNotNull('participant_id')->orWhereNotNull('team_id');
            })
            ->with([
                'heat',
                'participant.classification',
                'participant.team',
                'team',
                'relaySwimmers.participant',
            ])
            ->get()
            ->sortBy(fn (HeatLane $lane): array => $this->sortKey($lane))
            ->values();

        return $this->assignPlacesAndPoints($lanes, $pointMap);
    }

    /**
     * Individual MVP standings grouped by classification, age bracket, and gender.
     * Relay points are excluded.
     *
     * @return list<array{
     *     classification_id: string,
     *     classification: string,
     *     age_bracket_id: string,
     *     age_bracket: string,
     *     gender: string,
     *     standings: list<array{participant_id: string, first_name: string, last_name: string, team: string|null, points: float}>
     * }>
     */
    public function mvpStandings(Competition $competition): array
    {
        $groups = [];

        $events = $competition->events()
            ->where('type', EventType::Individual)
            ->get();

        foreach ($events as $event) {
            foreach ($this->scoreEvent($event, $competition) as $row) {
                if ($row['points'] === null || $row['lane']->participant_id === null) {
                    continue;
                }

                $participant = $row['lane']->participant;
                $participant->loadMissing(['classification.ageBrackets', 'team']);

                $ageBracket = $participant->matchingAgeBracket();

                if ($ageBracket === null || $participant->classification === null) {
                    continue;
                }

                $groupKey = implode('|', [
                    $participant->classification_id,
                    $ageBracket->id,
                    $participant->gender->value,
                ]);

                if (! isset($groups[$groupKey])) {
                    $groups[$groupKey] = [
                        'classification_id' => $participant->classification_id,
                        'classification' => $participant->classification->name,
                        'classification_sort_order' => $participant->classification->sort_order,
                        'age_bracket_id' => $ageBracket->id,
                        'age_bracket' => $ageBracket->name,
                        'age_bracket_sort_order' => $ageBracket->sort_order,
                        'gender' => $participant->gender->value,
                        'rows' => [],
                    ];
                }

                $id = $participant->id;

                if (! isset($groups[$groupKey]['rows'][$id])) {
                    $groups[$groupKey]['rows'][$id] = [
                        'participant_id' => $id,
                        'first_name' => $participant->first_name,
                        'last_name' => $participant->last_name,
                        'team' => $participant->team?->name,
                        'points' => 0.0,
                    ];
                }

                $groups[$groupKey]['rows'][$id]['points'] += $row['points'];
            }
        }

        $sorted = collect($groups)
            ->sortBy([
                ['classification_sort_order', 'asc'],
                ['classification', 'asc'],
                ['age_bracket_sort_order', 'asc'],
                ['age_bracket', 'asc'],
                ['gender', 'asc'],
            ])
            ->map(function (array $group): array {
                return [
                    'classification_id' => $group['classification_id'],
                    'classification' => $group['classification'],
                    'age_bracket_id' => $group['age_bracket_id'],
                    'age_bracket' => $group['age_bracket'],
                    'gender' => $group['gender'],
                    'standings' => $this->sortStandings(
                        array_values($group['rows']),
                        'last_name',
                        'first_name',
                    ),
                ];
            })
            ->values()
            ->all();

        return $sorted;
    }

    /**
     * Team champion standings (individual + relay points).
     *
     * @return list<array{team_id: string, name: string, points: float}>
     */
    public function teamStandings(Competition $competition): array
    {
        $totals = [];

        foreach ($competition->events()->get() as $event) {
            foreach ($this->scoreEvent($event, $competition) as $row) {
                if ($row['points'] === null) {
                    continue;
                }

                $teamId = $row['lane']->team_id
                    ?? $row['lane']->participant?->team_id;

                if ($teamId === null) {
                    continue;
                }

                $teamName = $row['lane']->team?->name
                    ?? $row['lane']->participant?->team?->name
                    ?? '';

                if (! isset($totals[$teamId])) {
                    $totals[$teamId] = [
                        'team_id' => $teamId,
                        'name' => $teamName,
                        'points' => 0.0,
                    ];
                }

                $totals[$teamId]['points'] += $row['points'];
            }
        }

        return $this->sortStandings(array_values($totals), 'name');
    }

    /**
     * @param  Collection<int, HeatLane>  $lanes
     * @param  array<int, float>  $pointMap
     * @return Collection<int, array{lane: HeatLane, place: int|null, points: float|null, status: string}>
     */
    private function assignPlacesAndPoints(Collection $lanes, array $pointMap): Collection
    {
        $results = [];
        $index = 0;
        $count = $lanes->count();
        $nextPlace = 1;

        while ($index < $count) {
            /** @var HeatLane $lane */
            $lane = $lanes[$index];

            if ($lane->isNs() || $lane->finish_time_hundredths === null) {
                $results[] = [
                    'lane' => $lane,
                    'place' => null,
                    'points' => null,
                    'status' => $lane->status->value,
                ];
                $index++;

                continue;
            }

            $blockStart = $index;
            $time = $lane->finish_time_hundredths;

            while (
                $index < $count
                && ! $lanes[$index]->isNs()
                && $lanes[$index]->finish_time_hundredths === $time
            ) {
                $index++;
            }

            $blockSize = $index - $blockStart;
            $place = $nextPlace;
            $points = $this->averagedPoints($pointMap, $place, $blockSize);

            for ($offset = 0; $offset < $blockSize; $offset++) {
                $results[] = [
                    'lane' => $lanes[$blockStart + $offset],
                    'place' => $place,
                    'points' => $points,
                    'status' => HeatLaneStatus::Swam->value,
                ];
            }

            $nextPlace += $blockSize;
        }

        return collect($results);
    }

    /**
     * Average the point values for a contiguous place block.
     *
     * @param  array<int, float>  $pointMap
     */
    private function averagedPoints(array $pointMap, int $startPlace, int $blockSize): ?float
    {
        $sum = 0.0;
        $hasPoints = false;

        for ($place = $startPlace; $place < $startPlace + $blockSize; $place++) {
            if (! array_key_exists($place, $pointMap)) {
                continue;
            }

            $sum += $pointMap[$place];
            $hasPoints = true;
        }

        if (! $hasPoints) {
            return null;
        }

        return round($sum / $blockSize, 2);
    }

    /**
     * @return array{0: int, 1: int, 2: string, 3: string}
     */
    private function sortKey(HeatLane $lane): array
    {
        if ($lane->isNs()) {
            $bucket = 2;
            $time = PHP_INT_MAX;
        } elseif ($lane->finish_time_hundredths === null) {
            $bucket = 1;
            $time = PHP_INT_MAX;
        } else {
            $bucket = 0;
            $time = $lane->finish_time_hundredths;
        }

        $nameA = $lane->participant?->last_name
            ?? $lane->team?->name
            ?? '';
        $nameB = $lane->participant?->first_name ?? '';

        return [$bucket, $time, $nameA, $nameB];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sortStandings(array $rows, string ...$nameKeys): array
    {
        usort($rows, function (array $a, array $b) use ($nameKeys): int {
            $pointsCmp = $b['points'] <=> $a['points'];

            if ($pointsCmp !== 0) {
                return $pointsCmp;
            }

            foreach ($nameKeys as $key) {
                $cmp = strcasecmp((string) ($a[$key] ?? ''), (string) ($b[$key] ?? ''));

                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            return 0;
        });

        return $rows;
    }
}
