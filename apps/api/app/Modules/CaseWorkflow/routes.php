<?php

declare(strict_types=1);

use App\Modules\CaseWorkflow\Http\Controllers\CaseController;
use App\Modules\CaseWorkflow\Http\Controllers\CaseReadController;
use App\Modules\CaseWorkflow\Http\Controllers\OfferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'idempotent'])->group(function (): void {
    Route::post('/cases', [CaseController::class, 'store']);
    Route::post('/cases/{id}/cancel', [CaseReadController::class, 'cancel']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/cases', [CaseReadController::class, 'index']);
    Route::get('/cases/{trackingCode}', [CaseReadController::class, 'show']);
});

Route::middleware(['auth:operator,sanctum', 'office.scope'])->group(function (): void {
    Route::get('/desk/offers', [OfferController::class, 'index']);
    Route::post('/offers/{id}/accept', [OfferController::class, 'accept']);
    Route::post('/offers/{id}/decline', [OfferController::class, 'decline']);
    Route::post('/desk/offers/{id}/accept', [OfferController::class, 'accept']);
    Route::post('/desk/offers/{id}/decline', [OfferController::class, 'decline']);
});
