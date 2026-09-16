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

// Consultation sessions management routes (§5.3, §8.2, TASK-119)
Route::middleware(['auth:sanctum'])->prefix('consultations/sessions')->group(function (): void {
    Route::get('/', [\App\Modules\Consultation\Http\Controllers\SessionController::class, 'index']);
    Route::post('/start', [\App\Modules\Consultation\Http\Controllers\SessionController::class, 'start']);
    Route::get('/{id}', [\App\Modules\Consultation\Http\Controllers\SessionController::class, 'show']);
    Route::post('/{id}/end', [\App\Modules\Consultation\Http\Controllers\SessionController::class, 'end']);
});

// Business subscription plans & quota routes (§5.3, §6.1, TASK-120)
Route::prefix('consultations/plans')->group(function (): void {
    Route::get('/', [\App\Modules\Consultation\Http\Controllers\SubscriptionController::class, 'plans']);
});

Route::middleware(['auth:sanctum'])->prefix('consultations')->group(function (): void {
    Route::get('/my-subscription', [\App\Modules\Consultation\Http\Controllers\SubscriptionController::class, 'mySubscription']);
    Route::post('/subscribe', [\App\Modules\Consultation\Http\Controllers\SubscriptionController::class, 'subscribe']);
    Route::post('/quota/consume', [\App\Modules\Consultation\Http\Controllers\SubscriptionController::class, 'consumeQuota']);
});
