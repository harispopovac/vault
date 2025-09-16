<?php

use Illuminate\Support\Facades\Route;
use Colors\ColorsModule\Http\Controllers\ColorsController;

Route::prefix('api/v1')->group(function () {
    // Basic CRUD operations
    Route::get('/colors', [ColorsController::class, 'index']);
    Route::post('/colors', [ColorsController::class, 'store']);
    
    // UUID-based operations (must be before the generic {color} routes)
    Route::get('/colors/uuid/{uuid}', [ColorsController::class, 'getByUuid']);
    Route::put('/colors/uuid/{uuid}', [ColorsController::class, 'updateByUuid']);
    
    Route::get('/colors/{color}', [ColorsController::class, 'show']);
    Route::put('/colors/{color}', [ColorsController::class, 'update']);
    Route::delete('/colors/{color}', [ColorsController::class, 'destroy']);
});
