<?php

declare(strict_types=1);

use App\Modules\Payments\Http\Controllers\DeskFinanceController;
use App\Modules\Payments\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'token.slide'])->group(function (): void {
    Route::middleware(['throttle:5,60', 'idempotent'])
        ->post('/wallet/topup', [WalletController::class, 'topup']);

    Route::post('/wallet/topup/verify', [WalletController::class, 'verify']);

    Route::get('/wallet/balance', [WalletController::class, 'balance']);
});

Route::middleware(['auth:operator,sanctum', 'office.scope'])->group(function (): void {
    Route::get('/desk/finance', [DeskFinanceController::class, 'index']);
});
