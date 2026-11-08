<?php

namespace App\Services;

use App\Http\Resources\EventResultResource;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Support\Collection;

class EventResultSheet
{
    public function __construct(
        private CompetitionHeatSequence $sequence,
        private CompetitionScoring $scoring,
    ) {}

    /**
     * Props for one event's result sheet, fastest entry first.
     *
     * @return array{
     *     competition: array{id: string, name: string, venue: string, competition_date: string, is_close: bool},
     *     event: array{id: string, name: string, gender: string, type: string, label: string},
     *     entries: array<int, mixed>,
     *     ages: list<int|null>,
     *     options: list<array{
     *         id: string,
     *         label: string,
     *         name: string,
     *         gender: string,
     *         eligibilities: list<array{
     *             classification_id: string,
     *             classification: string,
     *             classification_sort_order: int,
     *             age_bracket_id: string,
     *             age_bracket: string,
     *             age_bracket_sort_order: int
     *         }>
     *     }>,
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
        $scored = $this->scoring->scoreEvent($event, $competition);

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
                'type' => $event->type->value,
                'label' => $this->sequence->eventLabel($event),
            ],
            'entries' => EventResultResource::collection($scored)->resolve(),
            'ages' => $scored
                ->map(fn (array $row): ?int => $row['lane']->participant?->age)
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'options' => $events
                ->map(fn (Event $candidate): array => $this->optionPayload($candidate))
                ->values()
                ->all(),
            'position' => $position + 1,
            'total' => $events->count(),
            'previous_event_id' => $position > 0 ? $events[$position - 1]->id : null,
            'next_event_id' => $events->get($position + 1)?->id,
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     name: string,
     *     gender: string,
     *     eligibilities: list<array{
     *         classification_id: string,
     *         classification: string,
     *         classification_sort_order: int,
     *         age_bracket_id: string,
     *         age_bracket: string,
     *         age_bracket_sort_order: int
     *     }>
     * }
     */
    private function optionPayload(Event $event): array
    {
        $eligibilities = $event->eligibilities
            ->map(fn ($eligibility): array => [
                'classification_id' => (string) $eligibility->classification_id,
                'classification' => $eligibility->classification?->name ?? '—',
                'classification_sort_order' => (int) ($eligibility->classification?->sort_order ?? PHP_INT_MAX),
                'age_bracket_id' => (string) $eligibility->age_bracket_id,
                'age_bracket' => $eligibility->ageBracket?->name ?? '—',
                'age_bracket_sort_order' => (int) ($eligibility->ageBracket?->sort_order ?? PHP_INT_MAX),
            ])
            ->values()
            ->all();

        if ($eligibilities === []) {
            $eligibilities = [[
                'classification_id' => '',
                'classification' => '—',
                'classification_sort_order' => PHP_INT_MAX,
                'age_bracket_id' => '',
                'age_bracket' => '—',
                'age_bracket_sort_order' => PHP_INT_MAX,
            ]];
        }

        return [
            'id' => $event->id,
            'label' => $this->sequence->eventLabel($event),
            'name' => $event->name,
            'gender' => $event->gender->value,
            'eligibilities' => $eligibilities,
        ];
    }

    /**
     * Get the event's scored lanes, fastest first.
     *
     * @return Collection<int, array{lane: \App\Models\HeatLane, place: int|null, points: float|null, status: string}>
     */
    public function rankedEntries(Event $event): Collection
    {
        return $this->scoring->scoreEvent($event);
    }
}
