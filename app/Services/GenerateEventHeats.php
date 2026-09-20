<?php

namespace App\Services;

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

        foreach ($competition->events()->with('participants')->get() as $event) {
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
     * Rebuild the event's heats from its current participants.
     *
     * Existing heats and lanes are discarded. Every heat gets every lane of the
     * competition, participants are spread as evenly as possible across the heats,
     * and each heat is filled from the centre lane outwards so the edge lanes are
     * the ones left empty.
     *
     * Pass the lane count when it is already known to avoid a lookup per event.
     *
     * @return int The number of heats generated.
     */
    public function generate(Event $event, ?int $laneCount = null): int
    {
        $laneCount ??= $event->competition()->value('number_of_lane');
        $participantIds = $event->participants()->pluck('participants.id')->all();

        return DB::transaction(function () use ($event, $laneCount, $participantIds): int {
            $event->heats()->delete();

            if ($participantIds === []) {
                return 0;
            }

            $fillOrder = self::laneFillOrder($laneCount);
            $heatNumber = 0;

            foreach ($this->spreadAcrossHeats($participantIds, $laneCount) as $heatParticipantIds) {
                $heat = $event->heats()->create(['heat_number' => ++$heatNumber]);

                $this->createLanes($heat, $laneCount, $fillOrder, $heatParticipantIds);
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
     * Split participants into the fewest heats that fit, with sizes differing by at most one.
     *
     * @param  list<string>  $participantIds
     * @return list<list<string>>
     */
    private function spreadAcrossHeats(array $participantIds, int $laneCount): array
    {
        $participantCount = count($participantIds);
        $heatCount = (int) ceil($participantCount / $laneCount);
        $baseSize = intdiv($participantCount, $heatCount);
        $heatsWithExtra = $participantCount % $heatCount;

        $heats = [];
        $offset = 0;

        for ($index = 0; $index < $heatCount; $index++) {
            $size = $baseSize + ($index < $heatsWithExtra ? 1 : 0);
            $heats[] = array_slice($participantIds, $offset, $size);
            $offset += $size;
        }

        return $heats;
    }

    /**
     * Create every lane for the heat, seating participants centre-out and leaving the rest empty.
     *
     * @param  list<int>  $fillOrder
     * @param  list<string>  $participantIds
     */
    private function createLanes(Heat $heat, int $laneCount, array $fillOrder, array $participantIds): void
    {
        $participantByLane = array_combine(
            array_slice($fillOrder, 0, count($participantIds)),
            $participantIds,
        );

        foreach (range(1, $laneCount) as $laneNumber) {
            $heat->lanes()->create([
                'lane_number' => $laneNumber,
                'participant_id' => $participantByLane[$laneNumber] ?? null,
            ]);
        }
    }
}
