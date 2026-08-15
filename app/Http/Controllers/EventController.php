<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\ParticipantResource;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Display the specified event.
     */
    public function show(Competition $competition, Event $event): Response
    {
        $event->load([
            'eligibilities.classification',
            'eligibilities.ageBracket',
            'participants.classification',
        ]);

        $competition->load(['participants.classification']);

        return Inertia::render('Events/Show', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'participants' => ParticipantResource::collection($competition->participants)->resolve(),
            ],
            'event' => (new EventResource($event))->resolve(),
        ]);
    }

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
