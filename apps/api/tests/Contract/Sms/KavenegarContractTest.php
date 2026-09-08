<?php

declare(strict_types=1);

use App\Integration\Sms\Drivers\KavenegarDriver;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use Illuminate\Support\Facades\Http;

test('kavenegar driver sends lookup OTP using official response contract', function () {
    // Official Kavenegar recorded response
    Http::fake([
        'https://api.kavenegar.com/v1/*/verify/lookup.json*' => Http::response([
            'return' => [
                'status' => 200,
                'message' => 'تایید شد',
            ],
            'entries' => [
                [
                    'messageid' => 88991122,
                    'message' => 'کد تایید: 48291',
                    'status' => 1,
                    'statustext' => 'ارسال شده به مخابرات',
                    'sender' => '10004346',
                    'receptor' => '09123456789',
                    'date' => 1725800000,
                    'cost' => 120,
                ],
            ],
        ], 200),
    ]);

    $driver = new KavenegarDriver('test_api_key', '10004346');
    $result = $driver->sendOtp('09123456789', '48291', OtpPurpose::LOGIN);

    expect($result->isSuccess)->toBeTrue();
    expect($result->provider)->toBe('kavenegar');
    expect($result->messageId)->toBe('88991122');
});

test('kavenegar driver sends structured template message', function () {
    Http::fake([
        'https://api.kavenegar.com/v1/*/verify/lookup.json*' => Http::response([
            'return' => ['status' => 200, 'message' => 'تایید شد'],
            'entries' => [['messageid' => 77665544, 'status' => 1]],
        ], 200),
    ]);

    $driver = new KavenegarDriver('test_api_key', '10004346');
    $result = $driver->sendTemplate('09123456789', 'case-assigned', ['tracking' => 'CR-1405-01', 'office' => 'سعادت آباد']);

    expect($result->isSuccess)->toBeTrue();
    expect($result->messageId)->toBe('77665544');
});
