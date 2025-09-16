<?php

use Illuminate\Support\Facades\Route;
use Address\AddressModule\Http\Controllers\AddressesController;
use Address\AddressModule\Http\Controllers\AddressProxyController;

Route::prefix('api/v1')->group(function () {
    // CRUD for addresses
    Route::get('/addresses', [AddressesController::class, 'index']);
    Route::post('/addresses', [AddressesController::class, 'store']);
    Route::get('/addresses/{address}', [AddressesController::class, 'show']);
    Route::put('/addresses/{address}', [AddressesController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressesController::class, 'destroy']);

    // Proxy endpoints
    Route::get('/proxy/addresses', [AddressProxyController::class, 'getAddresses']);
    Route::get('/proxy/addresses/{id}', [AddressProxyController::class, 'getAddress']);
});


