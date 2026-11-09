<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\HeatLaneStatus;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GenerateEventHeats
{
    /**
     * Rebuild the heats for every event in the competition.
     *
     * Compatible individual events (same name, gender, and classification)
     * pack full heats and fill leftover lanes with guests from later events
     * whose age bracket is adjacent. Classifications never mix — a Novice
     * swimmer cannot be seated in a Developmental heat. Guests keep scoring
     * under their home event via entry_event_id.
     *
     * @return array{events: int, heats: int} The number of events seeded and heats created.
     */
    public function generateForCompetition(Competition $competition): array
    {
        $laneCount = $competition->number_of_lane;

        /** @var Collection<int, Event> $events */
        $events = $competition->events()
            ->with([
                'participants.classification',
                'teams',
                'eligibilities.ageBracket',
                'eligibilities.classification',
            ])
            ->orderBy('sort_order')
            ->get();

        return DB::transaction(function () use ($events, $laneCount): array {
            $seededEvents = 0;
            $heats = 0;

            $individuals = $events->filter(
                fn (Event $event): bool => $event->type !== EventType::Relay,
            );
            $relays = $events->filter(
                fn (Event $event): bool => $event->type === EventType::Relay,
            );

            $groups = $individuals->groupBy(
                fn (Event $event): string => $this->compatibilityGroupKey($event),
            );

            foreach ($groups as $group) {
                /** @var Collection<int, Event> $group */
                if ($group->count() === 1) {
                    $generatedCount = $this->generate($group->first(), $laneCount);

                    if ($generatedCount === 0) {
                        continue;
                    }

                    $seededEvents++;
                    $heats += $generatedCount;

                    continue;
                }

                $summary = $this->generateCompatibleGroup($group->values()->all(), $laneCount);
                $seededEvents += $summary['events'];
                $heats += $summary['heats'];
            }

            foreach ($relays as $event) {
                $generatedCount = $this->generate($event, $laneCount);

                if ($generatedCount === 0) {
                    continue;
                }

                $seededEvents++;
                $heats += $generatedCount;
            }

            return ['events' => $seededEvents, 'heats' => $heats];
        });
    }

    /**
     * Rebuild the event's heats from its current entries.
     *
     * Individual events seat participants; relay events seat teams. Existing
     * heats and lanes are discarded. Every heat gets every lane of the
     * competition, entries are spread as evenly as possible across the heats,
     * and each heat is filled from the centre lane outwards so the edge lanes
     * are the ones left empty.
     *
     * Pass the lane count when it is already known to avoid a lookup per event.
     *
     * @return int The number of heats generated.
     */
    public function generate(Event $event, ?int $laneCount = null): int
    {
        $laneCount ??= $event->competition()->value('number_of_lane');

        $entryIds = $event->type === EventType::Relay
            ? $event->teams()->pluck('teams.id')->all()
            : $event->participants()->pluck('participants.id')->all();

        return DB::transaction(function () use ($event, $laneCount, $entryIds): int {
            $event->heats()->delete();

            if ($entryIds === []) {
                return 0;
            }

            $fillOrder = self::laneFillOrder($laneCount);
            $heatNumber = 0;
            $isRelay = $event->type === EventType::Relay;
            $entries = array_map(
                fn (string $id): array => [
                    'id' => $id,
                    'entry_event_id' => $event->id,
                ],
                $entryIds,
            );

            foreach ($this->spreadAcrossHeats($entries, $laneCount) as $heatEntries) {
                $heat = $event->heats()->create(['heat_number' => ++$heatNumber]);

                $this->createLanes($heat, $laneCount, $fillOrder, $heatEntries, $isRelay);
            }

            return $heatNumber;
        });
    }

    /**
     * The order lanes are filled in: centre first, edges last, favouring the higher lane on ties.
     *
     * @return list<int>
     */
    public static function laneFillOrder(int $laneCount): array
    {
        $centre = ($laneCount + 1) / 2;
        $lanes = range(1, $laneCount);

        usort($lanes, function (int $a, int $b) use ($centre): int {
            return abs($a - $centre) <=> abs($b - $centre) ?: $b <=> $a;
        });

        return $lanes;
    }

    /**
     * Pack compatible individual events: full heats first, then fill the last
     * heat's empty lanes with guests from later age-adjacent events.
     *
     * @param  list<Event>  $events
     * @return array{events: int, heats: int}
     */
    private function generateCompatibleGroup(array $events, int $laneCount): array
    {
        /** @var array<string, list<string>> $queues */
        $queues = [];

        foreach ($events as $event) {
            $queues[$event->id] = $event->participants->pluck('id')->map(
                fn (mixed $id): string => (string) $id,
            )->all();
        }

        $fillOrder = self::laneFillOrder($laneCount);
        $seededEvents = 0;
        $heats = 0;

        foreach ($events as $index => $event) {
            $event->heats()->delete();

            $homeIds = $queues[$event->id];

            if ($homeIds === []) {
                continue;
            }

            $heatChunks = $this->packIntoHeats($homeIds, $laneCount);
            $lastIndex = count($heatChunks) - 1;
            $vacancies = $laneCount - count($heatChunks[$lastIndex]);

            /** @var list<array{id: string, entry_event_id: string}> $taggedHeats */
            $taggedHeats = [];

            foreach ($heatChunks as $chunkIndex => $chunkIds) {
                $tagged = array_map(
                    fn (string $id): array => [
                        'id' => $id,
                        'entry_event_id' => $event->id,
                    ],
                    $chunkIds,
                );

                if ($chunkIndex === $lastIndex && $vacancies > 0) {
                    for (
                        $donorIndex = $index + 1;
                        $donorIndex < count($events) && $vacancies > 0;
                        $donorIndex++
                    ) {
                        $donor = $events[$donorIndex];

                        if (! $this->canBorrowGuestsFrom($event, $donor)) {
                            continue;
                        }

                        $available = $queues[$donor->id];

                        if ($available === []) {
                            continue;
                        }

                        $guests = $this->takeCompatibleGuests(
                            $event,
                            $donor,
                            $available,
                            $vacancies,
                        );
                        $queues[$donor->id] = $available;

                        foreach ($guests as $guestId) {
                            $tagged[] = [
                                'id' => $guestId,
                                'entry_event_id' => $donor->id,
                            ];
                        }

                        $vacancies -= count($guests);
                    }
                }

                $taggedHeats[] = $tagged;
            }

            $queues[$event->id] = [];

            $heatNumber = 0;

            foreach ($taggedHeats as $heatEntries) {
                $heat = $event->heats()->create(['heat_number' => ++$heatNumber]);
                $this->createLanes($heat, $laneCount, $fillOrder, $heatEntries, false);
            }

            $seededEvents++;
            $heats += $heatNumber;
        }

        return ['events' => $seededEvents, 'heats' => $heats];
    }

    /**
     * Group key so only same stroke, gender, and classification family combine.
     */
    private function compatibilityGroupKey(Event $event): string
    {
        $classificationKeys = $this->rootClassificationIds($event);

        if ($classificationKeys === []) {
            return $event->name.'|'.$event->gender->value.'|event:'.$event->id;
        }

        sort($classificationKeys);

        return $event->name.'|'.$event->gender->value.'|'.implode(',', $classificationKeys);
    }

    /**
     * Whether the donor event may lend swimmers into the host heat.
     *
     * Requires the same classification family and neighbouring age brackets
     * (sort order differs by exactly one).
     */
    private function canBorrowGuestsFrom(Event $host, Event $donor): bool
    {
        $hostRoots = $this->rootClassificationIds($host);
        $donorRoots = $this->rootClassificationIds($donor);

        if ($hostRoots === [] || $donorRoots === []) {
            return false;
        }

        if (array_intersect($hostRoots, $donorRoots) === []) {
            return false;
        }

        return $this->haveAdjacentAgeBrackets($host, $donor);
    }

    /**
     * Pull guest ids from the donor queue that belong to the host's classification family.
     *
     * @param  list<string>  $available  Modified in place; incompatible ids stay queued.
     * @return list<string>
     */
    private function takeCompatibleGuests(
        Event $host,
        Event $donor,
        array &$available,
        int $limit,
    ): array {
        if ($limit <= 0 || $available === []) {
            return [];
        }

        $hostRoots = $this->rootClassificationIds($host);
        $participants = $donor->participants->keyBy(
            fn ($participant): string => (string) $participant->id,
        );
        $guests = [];
        $remaining = [];

        foreach ($available as $participantId) {
            if (count($guests) >= $limit) {
                $remaining[] = $participantId;

                continue;
            }

            $participant = $participants->get($participantId);
            $participantRoot = $participant?->classification !== null
                ? (string) ($participant->classification->parent_id ?? $participant->classification->id)
                : null;

            if ($participantRoot === null || ! in_array($participantRoot, $hostRoots, true)) {
                $remaining[] = $participantId;

                continue;
            }

            $guests[] = $participantId;
        }

        $available = $remaining;

        return $guests;
    }

    /**
     * Root classification ids from the event's eligibilities.
     *
     * @return list<string>
     */
    private function rootClassificationIds(Event $event): array
    {
        return $event->eligibilities
            ->map(function ($eligibility): ?string {
                $classification = $eligibility->classification;

                if ($classification === null) {
                    return $eligibility->classification_id !== null
                        ? (string) $eligibility->classification_id
                        : null;
                }

                return (string) ($classification->parent_id ?? $classification->id);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Whether two events share a classification with neighbouring age brackets.
     *
     * Neighbouring means the age-bracket sort orders differ by exactly one, so
     * "6 and under" can fill with "7" but not with "8 and over".
     */
    private function haveAdjacentAgeBrackets(Event $host, Event $donor): bool
    {
        $hostEligibilities = $host->eligibilities;
        $donorEligibilities = $donor->eligibilities;

        if ($hostEligibilities->isEmpty() || $donorEligibilities->isEmpty()) {
            return false;
        }

        $sharedClassificationIds = $hostEligibilities
            ->pluck('classification_id')
            ->intersect($donorEligibilities->pluck('classification_id'));

        foreach ($sharedClassificationIds as $classificationId) {
            $hostOrders = $hostEligibilities
                ->where('classification_id', $classificationId)
                ->map(fn ($eligibility): ?int => $eligibility->ageBracket?->sort_order)
                ->filter(fn (?int $order): bool => $order !== null);

            $donorOrders = $donorEligibilities
                ->where('classification_id', $classificationId)
                ->map(fn ($eligibility): ?int => $eligibility->ageBracket?->sort_order)
                ->filter(fn (?int $order): bool => $order !== null);

            foreach ($hostOrders as $hostOrder) {
                foreach ($donorOrders as $donorOrder) {
                    if (abs($hostOrder - $donorOrder) === 1) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Split entries into full heats with any remainder in the last heat.
     *
     * @param  list<string>  $entryIds
     * @return list<list<string>>
     */
    private function packIntoHeats(array $entryIds, int $laneCount): array
    {
        $heats = [];

        foreach (array_chunk($entryIds, $laneCount) as $chunk) {
            $heats[] = $chunk;
        }

        return $heats;
    }

    /**
     * Split entries into the fewest heats that fit, with sizes differing by at most one.
     *
     * @param  list<array{id: string, entry_event_id: string}>  $entries
     * @return list<list<array{id: string, entry_event_id: string}>>
     */
    private function spreadAcrossHeats(array $entries, int $laneCount): array
    {
        $entryCount = count($entries);
        $heatCount = (int) ceil($entryCount / $laneCount);
        $baseSize = intdiv($entryCount, $heatCount);
        $heatsWithExtra = $entryCount % $heatCount;

        $heats = [];
        $offset = 0;

        for ($index = 0; $index < $heatCount; $index++) {
            $size = $baseSize + ($index < $heatsWithExtra ? 1 : 0);
            $heats[] = array_slice($entries, $offset, $size);
            $offset += $size;
        }

        return $heats;
    }

    /**
     * Create every lane for the heat, seating entries centre-out and leaving the rest empty.
     *
     * @param  list<int>  $fillOrder
     * @param  list<array{id: string, entry_event_id: string}>  $entries
     */
    private function createLanes(
        Heat $heat,
        int $laneCount,
        array $fillOrder,
        array $entries,
        bool $isRelay,
    ): void {
        $entryByLane = array_combine(
            array_slice($fillOrder, 0, count($entries)),
            $entries,
        );

        foreach (range(1, $laneCount) as $laneNumber) {
            $entry = $entryByLane[$laneNumber] ?? null;

            $heat->lanes()->create([
                'lane_number' => $laneNumber,
                'participant_id' => $isRelay || $entry === null ? null : $entry['id'],
                'team_id' => $isRelay && $entry !== null ? $entry['id'] : null,
                'entry_event_id' => $entry['entry_event_id'] ?? null,
                'status' => HeatLaneStatus::Swam,
            ]);
        }
    }
}
