<?php

declare(strict_types=1);

use App\Modules\Delivery\Http\Controllers\DeliveryController;
use App\Modules\Delivery\Http\Controllers\WaybillController;
use Illuminate\Support\Facades\Route;

// Semi-public confirmation endpoint without auth (Architecture §7.3)
Route::post('/deliveries/{id}/confirm', [DeliveryController::class, 'confirm']);

// Temporary signed URL download for waybill PDF (Architecture §6.8)
Route::get('/deliveries/{id}/waybill/download', [WaybillController::class, 'download'])
    ->name('deliveries.waybill.download');

// Operator desk delivery management
Route::middleware(['auth:operator,sanctum', 'office.scope'])->group(function (): void {
    Route::get('/deliveries', [DeliveryController::class, 'index']);
    Route::get('/deliveries/ready-cases', [DeliveryController::class, 'readyCases']);
    Route::post('/deliveries', [DeliveryController::class, 'store']);
    Route::get('/deliveries/{id}', [DeliveryController::class, 'show']);
    Route::post('/deliveries/{id}/assign-courier', [DeliveryController::class, 'assignCourier']);
    Route::post('/deliveries/{id}/in-transit', [DeliveryController::class, 'markInTransit']);
    Route::post('/deliveries/{id}/fail', [DeliveryController::class, 'markFailed']);
    Route::get('/deliveries/{id}/waybill', [WaybillController::class, 'show']);
});
