<?php

declare(strict_types=1);

use App\Modules\CaseWorkflow\Http\Controllers\CaseController;
use App\Modules\CaseWorkflow\Http\Controllers\CaseReadController;
use App\Modules\CaseWorkflow\Http\Controllers\DeskCaseController;
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

    // Operator Desk Cases (Architecture §5.6, §7.3, TASK-074)
    Route::get('/desk/cases', [DeskCaseController::class, 'index']);
    Route::get('/desk/cases/{id}', [DeskCaseController::class, 'show']);
    Route::post('/cases/{id}/review', [DeskCaseController::class, 'review']);
    Route::post('/cases/{id}/return', [DeskCaseController::class, 'returnCase']);
    Route::post('/cases/{id}/inquiry', [DeskCaseController::class, 'inquiry']);
    Route::post('/cases/{id}/complete', [DeskCaseController::class, 'complete']);
    Route::post('/cases/{id}/reject', [DeskCaseController::class, 'reject']);

    // Alias routes under /desk/cases
    Route::post('/desk/cases/{id}/review', [DeskCaseController::class, 'review']);
    Route::post('/desk/cases/{id}/return', [DeskCaseController::class, 'returnCase']);
    Route::post('/desk/cases/{id}/inquiry', [DeskCaseController::class, 'inquiry']);
    Route::post('/desk/cases/{id}/complete', [DeskCaseController::class, 'complete']);
    Route::post('/desk/cases/{id}/reject', [DeskCaseController::class, 'reject']);
});
