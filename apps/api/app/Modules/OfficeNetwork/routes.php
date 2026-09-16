<?php

declare(strict_types=1);

use App\Modules\OfficeNetwork\Http\Controllers\AppointmentController;
use App\Modules\OfficeNetwork\Http\Controllers\OfficeController;
use Illuminate\Support\Facades\Route;

// Public Office Network endpoints (§5.6 Example 4, TASK-043)
Route::get('/offices', [OfficeController::class, 'index']);
Route::get('/offices/nearby', [OfficeController::class, 'nearby']);
Route::get('/offices/{id}/slots', [AppointmentController::class, 'slots']);
Route::get('/offices/{id}', [OfficeController::class, 'show']);

// Authenticated citizen appointment endpoints
Route::middleware(['auth:sanctum', 'token.slide'])->group(function (): void {
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
});

// Operator desk authenticated endpoints
Route::middleware(['auth:operator,sanctum', 'role:office_operator|office_manager|system_admin', 'office.scope'])
    ->group(__DIR__.'/routes-desk.php');
