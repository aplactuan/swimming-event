<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Event;
use App\Services\CompetitionResultsPdf;
use App\Services\EventResultSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CompetitionResultController extends Controller
{
    /**
     * Send the reader to the result sheet of the first event in the program.
     */
    public function index(Competition $competition): RedirectResponse
    {
        $first = $competition->events()->first();

        if ($first === null) {
            return redirect()
                ->route('competitions.show', $competition)
                ->with('status', 'no-results-to-show');
        }

        return redirect()->route('competition-results.show', [$competition, $first]);
    }

    /**
     * Display one event's result sheet, fastest swimmer first.
     */
    public function show(
        Competition $competition,
        Event $event,
        EventResultSheet $sheet,
    ): Response {
        return Inertia::render('Results/Show', $sheet->payload($competition, $event));
    }

    /**
     * Download a Meet Manager-style results PDF for all events or one classification.
     */
    public function pdf(
        Request $request,
        Competition $competition,
        CompetitionResultsPdf $resultsPdf,
    ): SymfonyResponse {
        $classification = null;

        if ($request->filled('classification_id')) {
            $classification = $competition->classifications()
                ->whereKey($request->string('classification_id')->toString())
                ->firstOrFail();
        }

        return $resultsPdf->download($competition, $classification);
    }

    /**
     * Download a results PDF for one event, optionally filtered by age.
     */
    public function eventPdf(
        Request $request,
        Competition $competition,
        Event $event,
        CompetitionResultsPdf $resultsPdf,
    ): SymfonyResponse {
        $validated = $request->validate([
            'ages' => ['sometimes', 'array'],
            'ages.*' => ['integer', 'min:0', 'max:120'],
        ]);

        return $resultsPdf->downloadEvent(
            $competition,
            $event,
            $validated['ages'] ?? [],
        );
    }
}
