<?php

declare(strict_types=1);

use App\Modules\CaseWorkflow\Http\Controllers\CaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'idempotent'])->group(function (): void {
    Route::post('/cases', [CaseController::class, 'store']);
});
