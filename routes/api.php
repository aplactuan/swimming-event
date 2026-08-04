<?php

use App\Http\Controllers\Api\Event\EventController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
| These routes are automatically prefixed with `/api/v1` and use the `api`
| middleware group. User-related routes belong in `routes/api-user.php`.
|
*/

Route::apiResource('events', EventController::class)
    ->only(['index', 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('events', EventController::class)
        ->only(['store', 'update', 'destroy']);
});
