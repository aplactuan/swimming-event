<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHeatLaneTimesRequest;
use App\Http\Resources\EventEligibilityResource;
use App\Http\Resources\HeatLaneResource;
use App\Models\Competition;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Services\CompetitionHeatSequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionHeatController extends Controller
{
    /**
     * Send the timer to the first heat of the competition's program.
     */
    public function index(Competition $competition, CompetitionHeatSequence $sequence): RedirectResponse
    {
        $first = $sequence->ordered($competition)->first();

        if ($first === null) {
            return redirect()
                ->route('competitions.show', $competition)
                ->with('status', 'no-heats-to-run');
        }

        return redirect()->route('competition-heats.show', [$competition, $first]);
    }

    /**
     * Display one heat of the program, with its place in the running order.
     */
    public function show(
        Competition $competition,
        Heat $heat,
        CompetitionHeatSequence $sequence,
    ): Response {
        $heats = $sequence->ordered($competition);
        $position = $heats->search(fn (Heat $candidate): bool => $candidate->id === $heat->id);

        abort_if($position === false, 404);

        $heat = $heats[$position];
        $heat->load('lanes.participant.classification');

        return Inertia::render('Heats/Show', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'number_of_lane' => $competition->number_of_lane,
                'is_close' => $competition->is_close,
            ],
            'heat' => [
                'id' => $heat->id,
                'heat_number' => $heat->heat_number,
                'label' => $sequence->label($heat),
                'event' => [
                    'id' => $heat->event->id,
                    'name' => $heat->event->name,
                    'gender' => $heat->event->gender->value,
                    'eligibilities' => EventEligibilityResource::collection(
                        $heat->event->eligibilities,
                    )->resolve(),
                ],
                'lanes' => HeatLaneResource::collection($heat->lanes)->resolve(),
            ],
            'options' => $heats
                ->map(fn (Heat $candidate): array => [
                    'id' => $candidate->id,
                    'label' => $sequence->label($candidate),
                ])
                ->values()
                ->all(),
            'position' => $position + 1,
            'total' => $heats->count(),
            'previous_heat_id' => $position > 0 ? $heats[$position - 1]->id : null,
            'next_heat_id' => $heats->get($position + 1)?->id,
        ]);
    }

    /**
     * Record the finish times swum in the heat.
     */
    public function update(
        UpdateHeatLaneTimesRequest $request,
        Competition $competition,
        Heat $heat,
    ): RedirectResponse {
        abort_unless($heat->event->competition_id === $competition->id, 404);

        /** @var array{lanes: list<array{id: string, finish_time: string|null}>} $validated */
        $validated = $request->validated();

        DB::transaction(function () use ($heat, $validated): void {
            $lanes = $heat->lanes()
                ->whereIn('id', array_column($validated['lanes'], 'id'))
                ->get()
                ->keyBy('id');

            foreach ($validated['lanes'] as $lane) {
                $lanes[$lane['id']]
                    ->forceFill([
                        'finish_time_hundredths' => HeatLane::parseFinishTime($lane['finish_time']),
                    ])
                    ->save();
            }
        });

        return redirect()
            ->route('competition-heats.show', [$competition, $heat])
            ->with('status', 'heat-times-saved');
    }
}
