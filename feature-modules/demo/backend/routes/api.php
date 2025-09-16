<?php

use Illuminate\Support\Facades\Route;
use Demo\DemoModule\Http\Controllers\DemoController;

Route::prefix('api/demo')->group(function () {
    Route::get('/users', [DemoController::class, 'getUsers']);
}); 
