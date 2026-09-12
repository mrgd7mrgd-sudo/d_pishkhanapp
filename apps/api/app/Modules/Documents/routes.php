<?php

declare(strict_types=1);

use App\Modules\Documents\Http\Controllers\DocumentUrlController;
use App\Modules\Documents\Http\Controllers\UploadController;
use App\Modules\Documents\Http\Controllers\VaultController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/documents/upload-intent', [UploadController::class, 'intent']);
    Route::post('/documents/upload-complete', [UploadController::class, 'complete']);
    Route::get('/documents/{document}/view-url', [DocumentUrlController::class, 'issueSignedUrl']);

    Route::get('/vault', [VaultController::class, 'index']);
    Route::post('/vault', [VaultController::class, 'store']);
    Route::get('/vault/{id}', [VaultController::class, 'show']);
    Route::delete('/vault/{id}', [VaultController::class, 'destroy']);
});

Route::get('/documents/view/{document}', [DocumentUrlController::class, 'view'])
    ->name('documents.proxy.view');

Route::get('/vault/view/{version}', [VaultController::class, 'view'])
    ->name('documents.vault.view');

Route::put('/documents/upload/direct', [UploadController::class, 'directPut'])
    ->name('documents.upload.direct');
