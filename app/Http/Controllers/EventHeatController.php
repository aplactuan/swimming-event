<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwapHeatLanesRequest;
use App\Models\Competition;
use App\Models\Event;
use App\Models\HeatLane;
use App\Services\GenerateEventHeats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class EventHeatController extends Controller
{
    /**
     * Generate heats for every event in the competition, replacing any that already exist.
     */
    public function generateAll(
        Competition $competition,
        GenerateEventHeats $generator,
    ): RedirectResponse {
        $summary = $generator->generateForCompetition($competition);

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'heats-generated')
            ->with('generated_heats_summary', $summary);
    }

    /**
     * Generate the event's heats, replacing any that already exist.
     */
    public function generate(
        Competition $competition,
        Event $event,
        GenerateEventHeats $generator,
    ): RedirectResponse {
        $generatedCount = $generator->generate($event);

        return redirect()
            ->route('events.show', [$competition, $event])
            ->with('status', 'heats-generated')
            ->with('generated_heats_count', $generatedCount);
    }

    /**
     * Swap the swimmers in two lanes, within a heat or across heats.
     */
    public function swap(
        SwapHeatLanesRequest $request,
        Competition $competition,
        Event $event,
    ): RedirectResponse {
        /** @var array{from_lane_id: string, to_lane_id: string} $validated */
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $from = HeatLane::query()->lockForUpdate()->findOrFail($validated['from_lane_id']);
            $to = HeatLane::query()->lockForUpdate()->findOrFail($validated['to_lane_id']);

            [$from->participant_id, $to->participant_id] = [$to->participant_id, $from->participant_id];

            $from->save();
            $to->save();
        });

        return redirect()
            ->route('events.show', [$competition, $event])
            ->with('status', 'heat-lanes-swapped');
    }
}
