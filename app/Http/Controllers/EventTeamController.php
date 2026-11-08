<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Http\Requests\StoreEventTeamRequest;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class EventTeamController extends Controller
{
    /**
     * Enter a team into a relay event.
     */
    public function store(
        StoreEventTeamRequest $request,
        Competition $competition,
        Event $event,
    ): RedirectResponse {
        abort_unless($event->type === EventType::Relay, 422);

        /** @var array{team_id: string} $validated */
        $validated = $request->validated();

        $event->teams()->syncWithoutDetaching([$validated['team_id']]);

        return redirect()
            ->route('events.show', [$competition, $event])
            ->with('status', 'event-team-added');
    }

    /**
     * Remove a team from a relay event.
     */
    public function destroy(
        Competition $competition,
        Event $event,
        Team $team,
    ): RedirectResponse {
        abort_unless($event->type === EventType::Relay, 422);

        $event->teams()->detach($team->id);

        return redirect()
            ->route('events.show', [$competition, $event])
            ->with('status', 'event-team-removed');
    }
}
