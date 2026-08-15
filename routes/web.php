<?php

use App\Http\Controllers\AgeBracketController;
use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/competitions', [CompetitionController::class, 'store'])->name('competitions.store');
    Route::get('/competitions/{competition}', [CompetitionController::class, 'show'])->name('competitions.show');
    Route::put('/competitions/{competition}', [CompetitionController::class, 'update'])->name('competitions.update');
    Route::delete('/competitions/{competition}', [CompetitionController::class, 'destroy'])->name('competitions.destroy');

    Route::scopeBindings()->group(function () {
        Route::post('/competitions/{competition}/classifications', [ClassificationController::class, 'store'])
            ->name('classifications.store');
        Route::put('/competitions/{competition}/classifications/{classification}', [ClassificationController::class, 'update'])
            ->name('classifications.update');
        Route::delete('/competitions/{competition}/classifications/{classification}', [ClassificationController::class, 'destroy'])
            ->name('classifications.destroy');

        Route::post('/competitions/{competition}/classifications/{classification}/age-brackets', [AgeBracketController::class, 'store'])
            ->name('age-brackets.store');
        Route::put('/competitions/{competition}/classifications/{classification}/age-brackets/{age_bracket}', [AgeBracketController::class, 'update'])
            ->name('age-brackets.update');
        Route::delete('/competitions/{competition}/classifications/{classification}/age-brackets/{age_bracket}', [AgeBracketController::class, 'destroy'])
            ->name('age-brackets.destroy');

        Route::post('/competitions/{competition}/events', [EventController::class, 'store'])
            ->name('events.store');
        Route::put('/competitions/{competition}/events/{event}', [EventController::class, 'update'])
            ->name('events.update');
        Route::delete('/competitions/{competition}/events/{event}', [EventController::class, 'destroy'])
            ->name('events.destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
