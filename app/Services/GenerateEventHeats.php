<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\HeatLaneStatus;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use Illuminate\Support\Facades\DB;

class GenerateEventHeats
{
    /**
     * Rebuild the heats for every event in the competition.
     *
     * @return array{events: int, heats: int} The number of events seeded and heats created.
     */
    public function generateForCompetition(Competition $competition): array
    {
        $events = 0;
        $heats = 0;

        foreach ($competition->events()->with(['participants', 'teams'])->get() as $event) {
            $generatedCount = $this->generate($event, $competition->number_of_lane);

            if ($generatedCount === 0) {
                continue;
            }

            $events++;
            $heats += $generatedCount;
        }

        return ['events' => $events, 'heats' => $heats];
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

            foreach ($this->spreadAcrossHeats($entryIds, $laneCount) as $heatEntryIds) {
                $heat = $event->heats()->create(['heat_number' => ++$heatNumber]);

                $this->createLanes($heat, $laneCount, $fillOrder, $heatEntryIds, $isRelay);
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
     * Split entries into the fewest heats that fit, with sizes differing by at most one.
     *
     * @param  list<string>  $entryIds
     * @return list<list<string>>
     */
    private function spreadAcrossHeats(array $entryIds, int $laneCount): array
    {
        $entryCount = count($entryIds);
        $heatCount = (int) ceil($entryCount / $laneCount);
        $baseSize = intdiv($entryCount, $heatCount);
        $heatsWithExtra = $entryCount % $heatCount;

        $heats = [];
        $offset = 0;

        for ($index = 0; $index < $heatCount; $index++) {
            $size = $baseSize + ($index < $heatsWithExtra ? 1 : 0);
            $heats[] = array_slice($entryIds, $offset, $size);
            $offset += $size;
        }

        return $heats;
    }

    /**
     * Create every lane for the heat, seating entries centre-out and leaving the rest empty.
     *
     * @param  list<int>  $fillOrder
     * @param  list<string>  $entryIds
     */
    private function createLanes(
        Heat $heat,
        int $laneCount,
        array $fillOrder,
        array $entryIds,
        bool $isRelay,
    ): void {
        $entryByLane = array_combine(
            array_slice($fillOrder, 0, count($entryIds)),
            $entryIds,
        );

        foreach (range(1, $laneCount) as $laneNumber) {
            $entryId = $entryByLane[$laneNumber] ?? null;

            $heat->lanes()->create([
                'lane_number' => $laneNumber,
                'participant_id' => $isRelay ? null : $entryId,
                'team_id' => $isRelay ? $entryId : null,
                'status' => HeatLaneStatus::Swam,
            ]);
        }
    }
}
