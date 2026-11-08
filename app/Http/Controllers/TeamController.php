<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Competition;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class TeamController extends Controller
{
    /**
     * Store a newly created team.
     */
    public function store(StoreTeamRequest $request, Competition $competition): RedirectResponse
    {
        $competition->teams()->create($request->validated());

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'team-created');
    }

    /**
     * Update the specified team.
     */
    public function update(
        UpdateTeamRequest $request,
        Competition $competition,
        Team $team,
    ): RedirectResponse {
        $team->update($request->validated());

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'team-updated');
    }

    /**
     * Remove the specified team.
     */
    public function destroy(Competition $competition, Team $team): RedirectResponse
    {
        abort_if($team->participants()->exists(), 422);

        $team->delete();

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'team-deleted');
    }
}
