<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderAgeBracketsRequest;
use App\Http\Requests\StoreAgeBracketRequest;
use App\Http\Requests\UpdateAgeBracketRequest;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AgeBracketController extends Controller
{
    /**
     * Store a newly created age bracket.
     */
    public function store(
        StoreAgeBracketRequest $request,
        Competition $competition,
        Classification $classification,
    ): RedirectResponse {
        $classification->ageBrackets()->create([
            ...$request->validated(),
            'sort_order' => AgeBracket::nextSortOrder($classification->id),
        ]);

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'age-bracket-created');
    }

    /**
     * Update the specified age bracket.
     */
    public function update(
        UpdateAgeBracketRequest $request,
        Competition $competition,
        Classification $classification,
        AgeBracket $ageBracket,
    ): RedirectResponse {
        $ageBracket->update($request->validated());

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'age-bracket-updated');
    }

    /**
     * Remove the specified age bracket.
     */
    public function destroy(
        Competition $competition,
        Classification $classification,
        AgeBracket $ageBracket,
    ): RedirectResponse {
        $ageBracket->delete();

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'age-bracket-deleted');
    }

    /**
     * Reorder age brackets within a classification.
     */
    public function reorder(
        ReorderAgeBracketsRequest $request,
        Competition $competition,
        Classification $classification,
    ): RedirectResponse {
        /** @var list<string> $ids */
        $ids = $request->validated('ids');

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $index => $id) {
                AgeBracket::query()
                    ->whereKey($id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'age-brackets-reordered');
    }
}
