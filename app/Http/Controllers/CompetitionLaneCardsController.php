<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Services\CompetitionLaneCardsPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompetitionLaneCardsController extends Controller
{
    /**
     * Download timer lane cards for all events or one classification.
     */
    public function pdf(
        Request $request,
        Competition $competition,
        CompetitionLaneCardsPdf $laneCardsPdf,
    ): Response {
        $classification = null;

        if ($request->filled('classification_id')) {
            $classification = $competition->classifications()
                ->whereKey($request->string('classification_id')->toString())
                ->firstOrFail();
        }

        return $laneCardsPdf->download($competition, $classification);
    }
}
