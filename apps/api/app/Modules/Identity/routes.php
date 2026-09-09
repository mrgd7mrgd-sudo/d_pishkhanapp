<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\OtpRequestController;
use App\Modules\Identity\Http\Controllers\OtpVerifyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['rate.limit.otp'])
    ->post('/auth/otp/request', OtpRequestController::class);

Route::post('/auth/otp/verify', OtpVerifyController::class);
