<?php

use Illuminate\Support\Facades\Route;
use FilesManagement\FilesManagementModule\Http\Controllers\TempFilesController;

Route::prefix('api/v1')->middleware('auth:sanctum')->group(function () {
    // Files
    Route::get('files/get', [TempFilesController::class, 'get_files'])->name('files.get');
    Route::get('files/all', [TempFilesController::class, 'get_all_files'])->name('files.all');
    Route::post('temp-files/upload', [TempFilesController::class, 'upload_temp_files'])->name('temp-files.upload');
    Route::delete('temp-files/remove', [TempFilesController::class, 'remove_temp_file'])->name('temp-files.remove');
    Route::post('files/upload', [TempFilesController::class, 'upload_files'])->name('files.upload');
}); 

