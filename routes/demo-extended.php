<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExtendedDemoController;

/**
 * Project-specific routes extending the demo module
 * These routes add functionality specific to this project
 * while keeping the base module routes intact
 */

Route::prefix('api/demo-extended')->group(function () {
    // Override the base users endpoint with extended functionality
    Route::get('/users', [ExtendedDemoController::class, 'getUsers']);
    
    // Project-specific: User management
    Route::post('/users', [ExtendedDemoController::class, 'createUser']);
    Route::delete('/users/{user}', [ExtendedDemoController::class, 'deleteUser']);
    
    // Project-specific: Export functionality
    Route::get('/users/export', [ExtendedDemoController::class, 'exportUsers']);
    
    // Project-specific: Email functionality
    Route::post('/users/{user}/send-email', [ExtendedDemoController::class, 'sendEmailToUser']);
    
    // Project-specific: Additional endpoints
    Route::get('/users/stats', [ExtendedDemoController::class, 'getUserStats']);
    Route::post('/users/bulk-import', [ExtendedDemoController::class, 'bulkImportUsers']);
}); 
