<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Services\CompetitionScoring;
use Inertia\Inertia;
use Inertia\Response;

class PublicStandingController extends Controller
{
    /**
     * Display public division MVPs and team champion standings for a closed competition.
     */
    public function index(Competition $competition, CompetitionScoring $scoring): Response
    {
        abort_unless($competition->isClosed(), 404);

        return Inertia::render('Public/Standings', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'venue' => $competition->venue,
                'competition_date' => $competition->competition_date->toDateString(),
                'is_close' => $competition->is_close,
            ],
            'mvp' => $scoring->mvpStandings($competition),
            'teams' => $scoring->teamStandings($competition),
            'public' => true,
        ]);
    }
}
