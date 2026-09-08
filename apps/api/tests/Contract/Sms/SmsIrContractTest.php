<?php

declare(strict_types=1);

use App\Integration\Sms\CircuitBreakerSmsGateway;
use App\Integration\Sms\Drivers\KavenegarDriver;
use App\Integration\Sms\Drivers\SmsIrDriver;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('sms.ir driver sends verify OTP using official response contract', function () {
    // Official SMS.ir recorded response
    Http::fake([
        'https://api.sms.ir/v1/send/verify' => Http::response([
            'status' => 1,
            'message' => 'موفق',
            'data' => [
                'messageId' => 99887766,
                'cost' => 150,
            ],
        ], 200),
    ]);

    $driver = new SmsIrDriver('test_smsir_key', '30007732');
    $result = $driver->sendOtp('09123456789', '99881', OtpPurpose::LOGIN);

    expect($result->isSuccess)->toBeTrue();
    expect($result->provider)->toBe('smsir');
    expect($result->messageId)->toBe('99887766');
});

test('circuit breaker trips after 3 consecutive failures of primary driver and fails over to SMS.ir', function () {
    Cache::flush();

    // Kavenegar fails with 500 error
    // SMS.ir succeeds
    Http::fake([
        'https://api.kavenegar.com/*' => Http::response(['error' => 'gateway timeout'], 500),
        'https://api.sms.ir/*' => Http::response([
            'status' => 1,
            'message' => 'موفق',
            'data' => ['messageId' => 55443322],
        ], 200),
    ]);

    $kavenegar = new KavenegarDriver('bad_key');
    $smsir = new SmsIrDriver('good_key');

    $cbGateway = new CircuitBreakerSmsGateway($kavenegar, $smsir);

    // Call 1: primary fails, falls back to smsir
    $r1 = $cbGateway->sendOtp('09123456789', '11111', OtpPurpose::LOGIN);
    expect($r1->isSuccess)->toBeTrue();
    expect($r1->provider)->toBe('smsir');

    // Call 2: primary fails, falls back to smsir
    $r2 = $cbGateway->sendOtp('09123456789', '22222', OtpPurpose::LOGIN);
    expect($r2->isSuccess)->toBeTrue();
    expect($r2->provider)->toBe('smsir');

    // Call 3: primary fails, trips circuit breaker
    $r3 = $cbGateway->sendOtp('09123456789', '33333', OtpPurpose::LOGIN);
    expect($r3->isSuccess)->toBeTrue();
    expect($r3->provider)->toBe('smsir');

    // Call 4: circuit is now OPEN, goes directly to smsir without touching Kavenegar
    $r4 = $cbGateway->sendOtp('09123456789', '44444', OtpPurpose::LOGIN);
    expect($r4->isSuccess)->toBeTrue();
    expect($r4->provider)->toBe('smsir');
});
