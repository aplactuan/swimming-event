<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParticipantRequest;
use App\Http\Requests\UpdateParticipantRequest;
use App\Models\Competition;
use App\Models\Participant;
use App\Services\ParticipantEventSync;
use Illuminate\Http\RedirectResponse;

class ParticipantController extends Controller
{
    /**
     * Store a newly created participant.
     */
    public function store(
        StoreParticipantRequest $request,
        Competition $competition,
        ParticipantEventSync $sync,
    ): RedirectResponse {
        $participant = $competition->participants()->create($request->validated());

        if ($participant->paid) {
            $sync->syncForPaid($participant);
        }

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'participant-created');
    }

    /**
     * Update the specified participant.
     */
    public function update(
        UpdateParticipantRequest $request,
        Competition $competition,
        Participant $participant,
        ParticipantEventSync $sync,
    ): RedirectResponse {
        $wasPaid = $participant->paid;

        $participant->update($request->validated());

        if ($participant->paid && ! $wasPaid) {
            $sync->syncForPaid($participant);
        }

        if (! $participant->paid && $wasPaid) {
            $sync->clearEvents($participant);
        }

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'participant-updated');
    }

    /**
     * Remove the specified participant.
     */
    public function destroy(Competition $competition, Participant $participant): RedirectResponse
    {
        $participant->delete();

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'participant-deleted');
    }
}
