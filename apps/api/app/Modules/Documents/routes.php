<?php

declare(strict_types=1);

use App\Modules\Documents\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/documents/upload-intent', [UploadController::class, 'intent']);
    Route::post('/documents/upload-complete', [UploadController::class, 'complete']);
});

Route::put('/documents/upload/direct', [UploadController::class, 'directPut'])
    ->name('documents.upload.direct');
