<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/services/ping', function () {
    return response()->json([
        'module' => 'ServiceCatalog',
        'status' => 'active',
        'timestamp' => now()->toIso8601String(),
    ]);
});
