<?php

declare(strict_types=1);

use App\Modules\CaseWorkflow\Http\Controllers\CaseController;
use App\Modules\CaseWorkflow\Http\Controllers\CaseReadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'idempotent'])->group(function (): void {
    Route::post('/cases', [CaseController::class, 'store']);
    Route::post('/cases/{id}/cancel', [CaseReadController::class, 'cancel']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/cases', [CaseReadController::class, 'index']);
    Route::get('/cases/{trackingCode}', [CaseReadController::class, 'show']);
});
