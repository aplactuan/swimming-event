<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompetitionPointRulesRequest;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CompetitionPointRuleController extends Controller
{
    /**
     * Replace the competition's place → points table.
     */
    public function update(
        UpdateCompetitionPointRulesRequest $request,
        Competition $competition,
    ): RedirectResponse {
        /** @var array{rules: list<array{place: int, points: int}>} $validated */
        $validated = $request->validated();

        DB::transaction(function () use ($competition, $validated): void {
            $competition->pointRules()->delete();

            foreach ($validated['rules'] as $rule) {
                $competition->pointRules()->create([
                    'place' => $rule['place'],
                    'points' => $rule['points'],
                ]);
            }
        });

        return redirect()
            ->route('competitions.show', $competition)
            ->with('status', 'point-rules-updated');
    }
}
