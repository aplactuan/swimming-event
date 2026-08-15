<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventParticipantRequest;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;

class EventParticipantController extends Controller
{
    /**
     * Manually add a paid participant to an event.
     */
    public function store(
        StoreEventParticipantRequest $request,
        Competition $competition,
        Event $event,
    ): RedirectResponse {
        /** @var array{participant_id: string} $validated */
        $validated = $request->validated();

        $event->participants()->syncWithoutDetaching([$validated['participant_id']]);

        return redirect()
            ->route('events.show', [$competition, $event])
            ->with('status', 'event-participant-added');
    }

    /**
     * Remove a paid participant from an event.
     */
    public function destroy(
        Competition $competition,
        Event $event,
        Participant $participant,
    ): RedirectResponse {
        abort_unless($participant->paid, 422);

        $event->participants()->detach($participant->id);

        return redirect()
            ->route('events.show', [$competition, $event])
            ->with('status', 'event-participant-removed');
    }
}
