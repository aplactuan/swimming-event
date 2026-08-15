<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompetitionRequest;
use App\Http\Requests\UpdateCompetitionRequest;
use App\Http\Resources\CompetitionResource;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    /**
     * Display the specified competition.
     */
    public function show(Competition $competition): Response
    {
        $competition->load([
            'rootClassifications.ageBrackets',
            'rootClassifications.children.ageBrackets',
            'events.eligibilities.classification',
            'events.eligibilities.ageBracket',
        ]);

        return Inertia::render('Competitions/Show', [
            'competition' => (new CompetitionResource($competition))->resolve(),
        ]);
    }

    /**
     * Store a newly created competition.
     */
    public function store(StoreCompetitionRequest $request): RedirectResponse
    {
        Competition::create($request->validated());

        return redirect()
            ->route('dashboard')
            ->with('status', 'competition-created');
    }

    /**
     * Update the specified competition.
     */
    public function update(UpdateCompetitionRequest $request, Competition $competition): RedirectResponse
    {
        $competition->update($request->validated());

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'competition-updated');
    }

    /**
     * Remove the specified competition.
     */
    public function destroy(Competition $competition): RedirectResponse
    {
        $competition->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', 'competition-deleted');
    }
}
