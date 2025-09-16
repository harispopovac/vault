<?php

use Illuminate\Support\Facades\Route;
use StaffRoster\StaffRosterModule\Http\Controllers\StaffRosterController;

Route::prefix('api/v1/staff-roster')->group(function () {
    // Basic CRUD operations
    Route::get('/', [StaffRosterController::class, 'index']);
    Route::post('/', [StaffRosterController::class, 'store']);
    Route::get('/{roster}', [StaffRosterController::class, 'show']);
    Route::put('/{roster}', [StaffRosterController::class, 'update']);
    Route::delete('/{roster}', [StaffRosterController::class, 'destroy']);

    // Advanced operations
    Route::post('/copy', [StaffRosterController::class, 'copy']);
    Route::post('/copy-day', [StaffRosterController::class, 'copyDay']);
    Route::post('/clear', [StaffRosterController::class, 'clear']);
    Route::post('/clear-day', [StaffRosterController::class, 'clearDay']);

    // Filtering and utilities
    Route::get('/filter/future', [StaffRosterController::class, 'futureRosters']);
    Route::get('/filter/upcoming', [StaffRosterController::class, 'upcomingRosters']);
    Route::get('/conflicts/check', [StaffRosterController::class, 'checkConflicts']);
});

// TODO: Move this to a dedicated staff routes file later
Route::prefix('api/v1')->group(function () {
    Route::get('/staff', [StaffRosterController::class, 'getStaffList']);
});
