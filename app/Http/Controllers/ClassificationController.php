<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassificationRequest;
use App\Http\Requests\UpdateClassificationRequest;
use App\Models\Classification;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;

class ClassificationController extends Controller
{
    /**
     * Store a newly created classification.
     */
    public function store(StoreClassificationRequest $request, Competition $competition): RedirectResponse
    {
        $parentId = $request->validated('parent_id');

        $competition->classifications()->create([
            'name' => $request->validated('name'),
            'parent_id' => $parentId,
            'sort_order' => Classification::nextSortOrder($competition->id, $parentId),
        ]);

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'classification-created');
    }

    /**
     * Update the specified classification.
     */
    public function update(
        UpdateClassificationRequest $request,
        Competition $competition,
        Classification $classification,
    ): RedirectResponse {
        $classification->update($request->validated());

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'classification-updated');
    }

    /**
     * Remove the specified classification.
     */
    public function destroy(Competition $competition, Classification $classification): RedirectResponse
    {
        $classification->delete();

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'classification-deleted');
    }
}
