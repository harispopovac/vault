<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\PromptTemplatesController;
use App\Http\Controllers\PromptsController;
use App\Http\Controllers\RepositoriesController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TimezonesController;
use App\Http\Controllers\TriggersController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WorkspacesController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-user', [AuthController::class, 'verify_user']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'requestResetPassword'])->name('password.request');
    Route::post('/password-reset', [AuthController::class, 'resetPassword'])->name('password.reset');

    // Timezones
    Route::get('timezones', [TimezonesController::class, 'index']);

    // Webhooks (public endpoints)
    Route::post('webhook/github', [WebhookController::class, 'github']);

    Route::middleware('auth:sanctum')->group(function () {
        // Authentication
        Route::post('logout', [AuthController::class, 'logout']);

        // Me
        Route::get('/me', [MeController::class, 'me']);
        Route::put('/me', [MeController::class, 'update']);
        Route::post('/me/photo', [MeController::class, 'uploadUserPhoto']);
        Route::put('set-current-timezone', [MeController::class, 'setCurrentTimezone']);

        // User Info
        Route::post('/user-info-enroll', [MeController::class, 'enroll_user_info']);
        Route::post('/user-info-verify', [MeController::class, 'verify_user_info']);

        // Passwords
        Route::put('/change-password', [MeController::class, 'update_password']);

        // Repositories
        Route::apiResource('repositories', RepositoriesController::class);

        // Prompt Templates
        Route::apiResource('prompt-templates', PromptTemplatesController::class);
        Route::post('prompt-templates/{promptTemplate}/use', [PromptTemplatesController::class, 'use']);

        // Prompts
        Route::apiResource('prompts', PromptsController::class);

        // Triggers
        Route::apiResource('triggers', TriggersController::class);
        Route::post('triggers/{trigger}/toggle', [TriggersController::class, 'toggle']);

        // Developers
        Route::get('developers', [DeveloperController::class, 'index']);
        Route::post('developers/sync/{repository?}', [DeveloperController::class, 'syncCollaborators']);
        Route::patch('developers/{collaborator}/role', [DeveloperController::class, 'updateRole']);
        Route::get('developers/roles', [DeveloperController::class, 'getRoles']);
        Route::get('developers/repositories', [DeveloperController::class, 'getRepositories']);

        // Roles
        Route::apiResource('roles', RoleController::class);

        // Webhook Debug/Testing (authenticated endpoints)
        Route::prefix('webhooks')->group(function () {
            Route::post('test', [WebhookController::class, 'test']);
            Route::get('status', [WebhookController::class, 'status']);
            Route::get('logs', [WebhookController::class, 'logs']);
            Route::delete('cache', [WebhookController::class, 'clearCache']);
        });

    });
});

Broadcast::routes(['middleware' => ['auth:sanctum']]);
