<?php

declare(strict_types=1);

use App\Modules\Consultation\Http\Controllers\AdminAdvisorController;
use App\Modules\Consultation\Http\Controllers\AdvisorController;
use Illuminate\Support\Facades\Route;

// Public routes for citizens to view and discover approved advisors (§6.1)
Route::prefix('advisors')->group(function (): void {
    Route::get('/', [AdvisorController::class, 'index']);
    Route::get('/{id}', [AdvisorController::class, 'show']);
});

// Authenticated citizen routes
Route::middleware(['auth:sanctum'])->prefix('advisors')->group(function (): void {
    Route::post('/apply', [AdvisorController::class, 'apply']);
});

// System administrator management routes (§5.3, §7.3)
Route::middleware(['auth:sanctum'])->prefix('admin/advisors')->group(function (): void {
    Route::get('/applications', [AdminAdvisorController::class, 'applications']);
    Route::post('/{id}/approve', [AdminAdvisorController::class, 'approve']);
    Route::post('/{id}/reject', [AdminAdvisorController::class, 'reject']);
});
