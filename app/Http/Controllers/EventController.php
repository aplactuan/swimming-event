<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * Store a newly created event.
     */
    public function store(StoreEventRequest $request, Competition $competition): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($competition, $validated): void {
            $event = $competition->events()->create([
                'name' => $validated['name'],
                'gender' => $validated['gender'],
                'sort_order' => Event::nextSortOrder($competition->id),
            ]);

            $event->syncEligibilities($validated['eligibilities']);
        });

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'event-created');
    }

    /**
     * Update the specified event.
     */
    public function update(
        UpdateEventRequest $request,
        Competition $competition,
        Event $event,
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($event, $validated): void {
            $event->update([
                'name' => $validated['name'],
                'gender' => $validated['gender'],
            ]);

            $event->syncEligibilities($validated['eligibilities']);
        });

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'event-updated');
    }

    /**
     * Remove the specified event.
     */
    public function destroy(Competition $competition, Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'event-deleted');
    }
}
