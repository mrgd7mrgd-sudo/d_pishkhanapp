<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\DeviceController;
use App\Modules\Identity\Http\Controllers\LogoutController;
use App\Modules\Identity\Http\Controllers\MeController;
use App\Modules\Identity\Http\Controllers\OtpRequestController;
use App\Modules\Identity\Http\Controllers\OtpVerifyController;
use App\Modules\Identity\Http\Controllers\RefreshTokenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['rate.limit.otp'])
    ->post('/auth/otp/request', OtpRequestController::class);

Route::post('/auth/otp/verify', OtpVerifyController::class);

Route::middleware(['auth:sanctum', 'token.slide'])->group(function (): void {
    Route::post('/auth/refresh', RefreshTokenController::class);
    Route::post('/auth/logout', LogoutController::class);
    Route::get('/auth/me', MeController::class);
    Route::get('/auth/devices', [DeviceController::class, 'index']);
    Route::delete('/auth/devices/{id}', [DeviceController::class, 'destroy']);
});
