<?php

declare(strict_types=1);

use App\Modules\ServiceCatalog\Http\Controllers\CategoryController;
use App\Modules\ServiceCatalog\Http\Controllers\DocumentTypeController;
use App\Modules\ServiceCatalog\Http\Controllers\ServiceController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/services/ping', function () {
    return new JsonResponse([
        'module' => 'ServiceCatalog',
        'status' => 'active',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Service Catalog public endpoints (§5.6 Example 3, TASK-041)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/document-types', [DocumentTypeController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{slug}', [ServiceController::class, 'show']);
