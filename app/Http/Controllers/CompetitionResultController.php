<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResultResource;
use App\Models\Competition;
use App\Models\Event;
use App\Models\HeatLane;
use App\Services\CompetitionHeatSequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionResultController extends Controller
{
    /**
     * Send the reader to the result sheet of the first event in the program.
     */
    public function index(Competition $competition): RedirectResponse
    {
        $first = $competition->events()->first();

        if ($first === null) {
            return redirect()
                ->route('competitions.show', $competition)
                ->with('status', 'no-results-to-show');
        }

        return redirect()->route('competition-results.show', [$competition, $first]);
    }

    /**
     * Display one event's result sheet, fastest swimmer first.
     */
    public function show(
        Competition $competition,
        Event $event,
        CompetitionHeatSequence $sequence,
    ): Response {
        $events = $competition->events()
            ->with(['eligibilities.classification', 'eligibilities.ageBracket'])
            ->get();

        $position = $events->search(fn (Event $candidate): bool => $candidate->id === $event->id);

        abort_if($position === false, 404);

        $event = $events[$position];
        $entries = $this->rankedEntries($event);

        return Inertia::render('Results/Show', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'is_close' => $competition->is_close,
            ],
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'gender' => $event->gender->value,
                'label' => $sequence->eventLabel($event),
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
                    'label' => $sequence->eventLabel($candidate),
                ])
                ->values()
                ->all(),
            'position' => $position + 1,
            'total' => $events->count(),
            'previous_event_id' => $position > 0 ? $events[$position - 1]->id : null,
            'next_event_id' => $events->get($position + 1)?->id,
        ]);
    }

    /**
     * Get the event's swum lanes, fastest first, with untimed swimmers last.
     *
     * @return Collection<int, HeatLane>
     */
    private function rankedEntries(Event $event): Collection
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
