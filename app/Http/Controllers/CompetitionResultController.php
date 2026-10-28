<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Event;
use App\Services\EventResultSheet;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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
}
