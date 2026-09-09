<?php

declare(strict_types=1);

use App\Modules\OfficeNetwork\Http\Controllers\OfficeController;
use Illuminate\Support\Facades\Route;

// Public Office Network endpoints (§5.6 Example 4, TASK-043)
Route::get('/offices', [OfficeController::class, 'index']);
Route::get('/offices/nearby', [OfficeController::class, 'nearby']);
Route::get('/offices/{id}', [OfficeController::class, 'show']);

// Operator desk authenticated endpoints
Route::middleware(['auth:operator,sanctum', 'role:office_operator|office_manager|system_admin', 'office.scope'])
    ->group(__DIR__.'/routes-desk.php');
