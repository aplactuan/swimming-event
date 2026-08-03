<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User API Routes
|--------------------------------------------------------------------------
|
| User-related routes are intentionally outside the `/api/v1` prefix so they
| stay stable across API versions. They still use the `api` middleware group.
|
*/

Route::prefix('user')->name('user.')->group(function () {
    // e.g. /api/user/...
});
