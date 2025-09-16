<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Media\SignedMediaController;
use App\Http\Controllers\PromptController;


// Prompt display routes (not domain-specific)
Route::get('/prompt/{token}', [PromptController::class, 'show'])->name('prompt.show');
Route::post('/prompt/{token}', [PromptController::class, 'store'])->name('prompt.store');

// Test page for browser tab notifications
Route::get('/test-notifications', function () {
    return view('test-notifications');
})->name('test.notifications');

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
