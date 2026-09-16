<?php

declare(strict_types=1);

use App\Modules\Delivery\Http\Controllers\DeliveryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:operator,sanctum', 'office.scope'])->group(function (): void {
    Route::post('/deliveries', [DeliveryController::class, 'store']);
    Route::get('/deliveries/{id}', [DeliveryController::class, 'show']);
});
