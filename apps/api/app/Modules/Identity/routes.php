<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\OtpRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['rate.limit.otp'])
    ->post('/auth/otp/request', OtpRequestController::class);
