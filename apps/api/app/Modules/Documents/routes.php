<?php

declare(strict_types=1);

use App\Modules\Documents\Http\Controllers\DocumentUrlController;
use App\Modules\Documents\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/documents/upload-intent', [UploadController::class, 'intent']);
    Route::post('/documents/upload-complete', [UploadController::class, 'complete']);
    Route::get('/documents/{document}/view-url', [DocumentUrlController::class, 'issueSignedUrl']);
});

Route::get('/documents/view/{document}', [DocumentUrlController::class, 'view'])
    ->name('documents.proxy.view');

Route::put('/documents/upload/direct', [UploadController::class, 'directPut'])
    ->name('documents.upload.direct');
