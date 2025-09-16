<?php

use Illuminate\Support\Facades\Route;
use ShiftLog\ShiftLogModule\Http\Controllers\StaffRosterLogController;

/*
|--------------------------------------------------------------------------
| Shift Log API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the shift log module. These routes provide
| functionality for both administrative shift log management and individual
| staff "My Shifts" functionality.
|
*/

/*
|--------------------------------------------------------------------------
| Administrative Shift Log Routes
|--------------------------------------------------------------------------
| These routes are for administrators to view and manage all staff shift logs
*/
Route::prefix('api/v1/shift-log')->group(function () {
    // Configuration endpoint
    Route::get('/config', [StaffRosterLogController::class, 'config'])->name('shift-log.config');

    // Main shift log endpoints (combines planned shifts with actual logs)
    Route::get('/', [StaffRosterLogController::class, 'index'])->name('shift-log.index');
    Route::post('/', [StaffRosterLogController::class, 'store'])->name('shift-log.store');
    Route::get('/{staffRosterLog}', [StaffRosterLogController::class, 'show'])->name('shift-log.show');
    Route::put('/{staffRosterLog}', [StaffRosterLogController::class, 'update'])->name('shift-log.update');
    Route::delete('/{staffRosterLog}', [StaffRosterLogController::class, 'destroy'])->name('shift-log.destroy');

    // Utility endpoints
    Route::get('/open-shift/staff', [StaffRosterLogController::class, 'getOpenShift'])->name('shift-log.open-shift');

    // Optional break management routes (when breaks are enabled)
    if (config('shift-log.enable_breaks', false)) {
        Route::post('/{staffRosterLog}/breaks', [StaffRosterLogController::class, 'manageBreak'])->name('shift-log.breaks.manage');
    }
});

/*
|--------------------------------------------------------------------------
| Staff "My Shifts" Routes
|--------------------------------------------------------------------------
| These routes are for individual staff members to view and manage their own shifts
*/
Route::prefix('api/v1/my-shifts')->group(function () {
    // My shifts (filtered by authenticated staff member)
    Route::get('/', [StaffRosterLogController::class, 'myShifts'])->name('my-shifts.index');

    // Check in/out functionality
    Route::post('/check-in', [StaffRosterLogController::class, 'store'])->name('my-shifts.check-in');
    Route::post('/check-out/{staffRosterLog}', [StaffRosterLogController::class, 'update'])->name('my-shifts.check-out');

    // My open shift
    Route::get('/open-shift', [StaffRosterLogController::class, 'getOpenShift'])->name('my-shifts.open-shift');

    // Optional break management routes (when breaks are enabled)
    if (config('shift-log.enable_breaks', false)) {
        Route::post('/{staffRosterLog}/breaks', [StaffRosterLogController::class, 'manageBreak'])->name('my-shifts.breaks.manage');
    }
});

// TODO: Move this to a dedicated staff routes file later
Route::prefix('api/v1')->group(function () {
    Route::get('/staff', [StaffRosterLogController::class, 'getStaffList']);
});
