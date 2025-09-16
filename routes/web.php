<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Media\SignedMediaController;


Route::middleware('web')->group(function () {
    Route::domain(env('LANDING_PAGE_URL'))->group(function () {
        Route::get('/', function () {
            return view('landing');
        });

        Route::get('/privacy-policy', function () {
            return view('landing');
        });

        Route::get('/terms-of-service', function () {
            return view('landing');
        });

        Route::get('/refund-policy', function () {
            return view('landing');
        });
    });

    Route::domain(env('APP_BASE_URL'))->group(function () {
        Route::get('/media/{media}/{conversion?}', [SignedMediaController::class, 'media'])->name('media');

        Route::get('{any?}', function () {
            return view('application');
        })->where('any', '.*');
    });
});
