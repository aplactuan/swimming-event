<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompetitionResource;
use App\Models\Competition;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the application dashboard.
     */
    public function __invoke(): Response
    {
        $competitions = Competition::query()
            ->upcoming()
            ->get();

        return Inertia::render('Dashboard', [
            'competitions' => CompetitionResource::collection($competitions)->resolve(),
        ]);
    }
}
