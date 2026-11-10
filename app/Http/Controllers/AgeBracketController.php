<?php

namespace App\Http\Controllers;

use App\Http\Requests\CopyAgeBracketRequest;
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
     * Copy an age bracket onto one or more other classifications in the meet.
     */
    public function copy(
        CopyAgeBracketRequest $request,
        Competition $competition,
        Classification $classification,
        AgeBracket $ageBracket,
    ): RedirectResponse {
        /** @var list<string> $targetIds */
        $targetIds = $request->validated('classification_ids');

        DB::transaction(function () use ($ageBracket, $targetIds): void {
            $targets = Classification::query()
                ->whereKey($targetIds)
                ->get()
                ->keyBy('id');

            foreach ($targetIds as $targetId) {
                /** @var Classification $target */
                $target = $targets[$targetId];

                $target->ageBrackets()->create([
                    'name' => $ageBracket->name,
                    'start_birthday' => $ageBracket->start_birthday,
                    'end_birthday' => $ageBracket->end_birthday,
                    'sort_order' => AgeBracket::nextSortOrder($target->id),
                ]);
            }
        });

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'age-bracket-copied')
            ->with('copied_age_bracket_count', count($targetIds));
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
