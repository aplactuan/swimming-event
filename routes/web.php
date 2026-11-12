<?php

use App\Http\Controllers\AgeBracketController;
use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\CompetitionHeatController;
use App\Http\Controllers\CompetitionPointRuleController;
use App\Http\Controllers\CompetitionLaneCardsController;
use App\Http\Controllers\CompetitionProgramController;
use App\Http\Controllers\CompetitionResultController;
use App\Http\Controllers\CompetitionStandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventHeatController;
use App\Http\Controllers\EventParticipantController;
use App\Http\Controllers\EventTeamController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicResultController;
use App\Http\Controllers\PublicStandingController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicResultController::class, 'home'])->name('home');
Route::get('/results/{competition}', [PublicResultController::class, 'index'])
    ->name('public-results.index');
Route::get('/results/{competition}/standings', [PublicStandingController::class, 'index'])
    ->name('public-standings.index');
Route::get('/results/{competition}/{event}/pdf', [PublicResultController::class, 'eventPdf'])
    ->name('public-results.event-pdf');
Route::get('/results/{competition}/{event}', [PublicResultController::class, 'show'])
    ->name('public-results.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/competitions', [CompetitionController::class, 'store'])->name('competitions.store');
    Route::get('/competitions/{competition}', [CompetitionController::class, 'show'])->name('competitions.show');
    Route::put('/competitions/{competition}', [CompetitionController::class, 'update'])->name('competitions.update');
    Route::patch('/competitions/{competition}/close', [CompetitionController::class, 'close'])->name('competitions.close');
    Route::patch('/competitions/{competition}/open', [CompetitionController::class, 'open'])->name('competitions.open');

    Route::get('/competitions/{competition}/heats', [CompetitionHeatController::class, 'index'])
        ->name('competition-heats.index');
    Route::get('/competitions/{competition}/heats/{heat}', [CompetitionHeatController::class, 'show'])
        ->name('competition-heats.show');
    Route::patch('/competitions/{competition}/heats/{heat}/times', [CompetitionHeatController::class, 'update'])
        ->name('competition-heats.times.update');
    Route::get('/competitions/{competition}/results', [CompetitionResultController::class, 'index'])
        ->name('competition-results.index');
    Route::get('/competitions/{competition}/results-pdf', [CompetitionResultController::class, 'pdf'])
        ->name('competition-results.pdf');
    Route::get('/competitions/{competition}/program-pdf', [CompetitionProgramController::class, 'pdf'])
        ->name('competition-program.pdf');
    Route::get('/competitions/{competition}/lane-cards-pdf', [CompetitionLaneCardsController::class, 'pdf'])
        ->name('competition-lane-cards.pdf');
    Route::get('/competitions/{competition}/results/{event}/pdf', [CompetitionResultController::class, 'eventPdf'])
        ->name('competition-results.event-pdf');
    Route::get('/competitions/{competition}/results/{event}', [CompetitionResultController::class, 'show'])
        ->name('competition-results.show');
    Route::get('/competitions/{competition}/standings', [CompetitionStandingController::class, 'index'])
        ->name('competition-standings.index');
    Route::delete('/competitions/{competition}', [CompetitionController::class, 'destroy'])->name('competitions.destroy');

    Route::scopeBindings()->group(function () {
        Route::post('/competitions/{competition}/classifications', [ClassificationController::class, 'store'])
            ->middleware('competition.open')
            ->name('classifications.store');
        Route::patch('/competitions/{competition}/classifications/reorder', [ClassificationController::class, 'reorder'])
            ->name('classifications.reorder');
        Route::put('/competitions/{competition}/classifications/{classification}', [ClassificationController::class, 'update'])
            ->name('classifications.update');
        Route::delete('/competitions/{competition}/classifications/{classification}', [ClassificationController::class, 'destroy'])
            ->name('classifications.destroy');

        Route::post('/competitions/{competition}/classifications/{classification}/age-brackets', [AgeBracketController::class, 'store'])
            ->middleware('competition.open')
            ->name('age-brackets.store');
        Route::patch('/competitions/{competition}/classifications/{classification}/age-brackets/reorder', [AgeBracketController::class, 'reorder'])
            ->name('age-brackets.reorder');
        Route::put('/competitions/{competition}/classifications/{classification}/age-brackets/{age_bracket}', [AgeBracketController::class, 'update'])
            ->name('age-brackets.update');
        Route::post('/competitions/{competition}/classifications/{classification}/age-brackets/{age_bracket}/copy', [AgeBracketController::class, 'copy'])
            ->middleware('competition.open')
            ->name('age-brackets.copy');
        Route::delete('/competitions/{competition}/classifications/{classification}/age-brackets/{age_bracket}', [AgeBracketController::class, 'destroy'])
            ->name('age-brackets.destroy');

        Route::post('/competitions/{competition}/events', [EventController::class, 'store'])
            ->middleware('competition.open')
            ->name('events.store');
        Route::post('/competitions/{competition}/events/generate', [EventController::class, 'generate'])
            ->middleware('competition.open')
            ->name('events.generate');
        Route::post('/competitions/{competition}/events/program', [EventController::class, 'program'])
            ->middleware('competition.open')
            ->name('events.program');
        Route::get('/competitions/{competition}/events/{event}', [EventController::class, 'show'])
            ->name('events.show');
        Route::put('/competitions/{competition}/events/{event}', [EventController::class, 'update'])
            ->name('events.update');
        Route::delete('/competitions/{competition}/events/{event}', [EventController::class, 'destroy'])
            ->name('events.destroy');

        Route::post('/competitions/{competition}/participants', [ParticipantController::class, 'store'])
            ->middleware('competition.open')
            ->name('participants.store');
        Route::post('/competitions/{competition}/participants/import', [ParticipantController::class, 'import'])
            ->middleware('competition.open')
            ->name('participants.import');
        Route::put('/competitions/{competition}/participants/{participant}', [ParticipantController::class, 'update'])
            ->name('participants.update');
        Route::delete('/competitions/{competition}/participants/{participant}', [ParticipantController::class, 'destroy'])
            ->name('participants.destroy');

        Route::post('/competitions/{competition}/teams', [TeamController::class, 'store'])
            ->middleware('competition.open')
            ->name('teams.store');
        Route::put('/competitions/{competition}/teams/{team}', [TeamController::class, 'update'])
            ->name('teams.update');
        Route::delete('/competitions/{competition}/teams/{team}', [TeamController::class, 'destroy'])
            ->name('teams.destroy');

        Route::put('/competitions/{competition}/point-rules', [CompetitionPointRuleController::class, 'update'])
            ->name('competition-point-rules.update');

        Route::post('/competitions/{competition}/events/{event}/participants', [EventParticipantController::class, 'store'])
            ->middleware('competition.open')
            ->name('event-participants.store');
        Route::delete('/competitions/{competition}/events/{event}/participants/{participant}', [EventParticipantController::class, 'destroy'])
            ->name('event-participants.destroy');

        Route::post('/competitions/{competition}/events/{event}/teams', [EventTeamController::class, 'store'])
            ->middleware('competition.open')
            ->name('event-teams.store');
        Route::delete('/competitions/{competition}/events/{event}/teams/{team}', [EventTeamController::class, 'destroy'])
            ->name('event-teams.destroy');

        Route::post('/competitions/{competition}/events/heats/generate', [EventHeatController::class, 'generateAll'])
            ->middleware('competition.open')
            ->name('event-heats.generate-all');
        Route::post('/competitions/{competition}/events/{event}/heats/generate', [EventHeatController::class, 'generate'])
            ->middleware('competition.open')
            ->name('event-heats.generate');
        Route::patch('/competitions/{competition}/events/{event}/heats/lanes/swap', [EventHeatController::class, 'swap'])
            ->name('event-heat-lanes.swap');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
