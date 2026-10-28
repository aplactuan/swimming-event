<?php

namespace App\Services;

use App\Http\Resources\EventResultResource;
use App\Models\Competition;
use App\Models\Event;
use App\Models\HeatLane;
use Illuminate\Support\Collection;

class EventResultSheet
{
    public function __construct(private CompetitionHeatSequence $sequence) {}

    /**
     * Props for one event's result sheet, fastest swimmer first.
     *
     * @return array{
     *     competition: array{id: string, name: string, venue: string, competition_date: string, is_close: bool},
     *     event: array{id: string, name: string, gender: string, label: string},
     *     entries: array<int, mixed>,
     *     ages: list<int|null>,
     *     options: list<array{id: string, label: string}>,
     *     position: int,
     *     total: int,
     *     previous_event_id: string|null,
     *     next_event_id: string|null
     * }
     */
    public function payload(Competition $competition, Event $event): array
    {
        $events = $competition->events()
            ->with(['eligibilities.classification', 'eligibilities.ageBracket'])
            ->get();

        $position = $events->search(fn (Event $candidate): bool => $candidate->id === $event->id);

        abort_if($position === false, 404);

        $event = $events[$position];
        $entries = $this->rankedEntries($event);

        return [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'venue' => $competition->venue,
                'competition_date' => $competition->competition_date->toDateString(),
                'is_close' => $competition->is_close,
            ],
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'gender' => $event->gender->value,
                'label' => $this->sequence->eventLabel($event),
            ],
            'entries' => EventResultResource::collection($entries)->resolve(),
            'ages' => $entries
                ->pluck('participant.age')
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'options' => $events
                ->map(fn (Event $candidate): array => [
                    'id' => $candidate->id,
                    'label' => $this->sequence->eventLabel($candidate),
                ])
                ->values()
                ->all(),
            'position' => $position + 1,
            'total' => $events->count(),
            'previous_event_id' => $position > 0 ? $events[$position - 1]->id : null,
            'next_event_id' => $events->get($position + 1)?->id,
        ];
    }

    /**
     * Get the event's swum lanes, fastest first, with untimed swimmers last.
     *
     * @return Collection<int, HeatLane>
     */
    public function rankedEntries(Event $event): Collection
    {
        return $event->heatLanes()
            ->whereNotNull('participant_id')
            ->with(['heat', 'participant.classification'])
            ->get()
            ->sortBy(fn (HeatLane $lane): array => [
                $lane->finish_time_hundredths === null ? 1 : 0,
                $lane->finish_time_hundredths ?? 0,
                $lane->participant->last_name,
                $lane->participant->first_name,
            ])
            ->values();
    }
}
