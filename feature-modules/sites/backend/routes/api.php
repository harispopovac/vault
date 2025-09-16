<?php

use Illuminate\Support\Facades\Route;
use Sites\SitesModule\Http\Controllers\SiteController;

Route::prefix('api/v1/sites')->group(function () {
    // Basic CRUD operations
    Route::get('/', [SiteController::class, 'index']);
    Route::post('/', [SiteController::class, 'store']);
    Route::get('/{site}', [SiteController::class, 'show']);
    Route::put('/{site}', [SiteController::class, 'update']);
    Route::delete('/{site}', [SiteController::class, 'destroy']);

    // Utility endpoints
    Route::get('/list/simple', [SiteController::class, 'getSimpleList']);
});
