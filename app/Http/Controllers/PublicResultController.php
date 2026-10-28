<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Event;
use App\Services\EventResultSheet;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicResultController extends Controller
{
    /**
     * Display closed competitions whose results the public can read.
     */
    public function home(): Response
    {
        $competitions = Competition::query()
            ->closed()
            ->orderByDesc('competition_date')
            ->orderBy('name')
            ->get(['id', 'name', 'venue', 'competition_date']);

        return Inertia::render('Public/Home', [
            'competitions' => $competitions
                ->map(fn (Competition $competition): array => [
                    'id' => $competition->id,
                    'name' => $competition->name,
                    'venue' => $competition->venue,
                    'competition_date' => $competition->competition_date->toDateString(),
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Send the reader to the first event of a closed competition.
     */
    public function index(Competition $competition): RedirectResponse|Response
    {
        abort_unless($competition->isClosed(), 404);

        $first = $competition->events()->first();

        if ($first === null) {
            return Inertia::render('Public/Results', [
                'competition' => [
                    'id' => $competition->id,
                    'name' => $competition->name,
                    'venue' => $competition->venue,
                    'competition_date' => $competition->competition_date->toDateString(),
                    'is_close' => $competition->is_close,
                ],
                'event' => null,
                'entries' => [],
                'ages' => [],
                'options' => [],
                'position' => 0,
                'total' => 0,
                'previous_event_id' => null,
                'next_event_id' => null,
            ]);
        }

        return redirect()->route('public-results.show', [$competition, $first]);
    }

    /**
     * Display one event's public result sheet.
     */
    public function show(
        Competition $competition,
        Event $event,
        EventResultSheet $sheet,
    ): Response {
        abort_unless($competition->isClosed(), 404);

        return Inertia::render('Public/Results', $sheet->payload($competition, $event));
    }
}
