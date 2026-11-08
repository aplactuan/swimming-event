<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Services\CompetitionScoring;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionStandingController extends Controller
{
    /**
     * Display division MVPs and team champion standings for the competition.
     */
    public function index(Competition $competition, CompetitionScoring $scoring): Response
    {
        return Inertia::render('Standings/Show', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'venue' => $competition->venue,
                'competition_date' => $competition->competition_date->toDateString(),
                'is_close' => $competition->is_close,
            ],
            'mvp' => $scoring->mvpStandings($competition),
            'teams' => $scoring->teamStandings($competition),
            'public' => false,
        ]);
    }
}
