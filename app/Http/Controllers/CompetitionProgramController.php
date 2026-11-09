<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Services\CompetitionMeetProgramPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompetitionProgramController extends Controller
{
    /**
     * Download a Meet Manager-style meet program PDF for all events or one classification.
     */
    public function pdf(
        Request $request,
        Competition $competition,
        CompetitionMeetProgramPdf $programPdf,
    ): Response {
        $classification = null;

        if ($request->filled('classification_id')) {
            $classification = $competition->classifications()
                ->whereKey($request->string('classification_id')->toString())
                ->firstOrFail();
        }

        return $programPdf->download($competition, $classification);
    }
}
