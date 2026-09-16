<?php

declare(strict_types=1);

use App\Modules\Delivery\Http\Controllers\DeliveryController;
use Illuminate\Support\Facades\Route;

// Semi-public confirmation endpoint without auth (Architecture §7.3)
Route::post('/deliveries/{id}/confirm', [DeliveryController::class, 'confirm']);

// Operator desk delivery management
Route::middleware(['auth:operator,sanctum', 'office.scope'])->group(function (): void {
    Route::post('/deliveries', [DeliveryController::class, 'store']);
    Route::get('/deliveries/{id}', [DeliveryController::class, 'show']);
    Route::post('/deliveries/{id}/assign-courier', [DeliveryController::class, 'assignCourier']);
    Route::post('/deliveries/{id}/in-transit', [DeliveryController::class, 'markInTransit']);
    Route::post('/deliveries/{id}/fail', [DeliveryController::class, 'markFailed']);
});
