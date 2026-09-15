<?php

declare(strict_types=1);

namespace Tests\Contract\Payment;

use App\Integration\Payment\Drivers\FakeDriver;
use App\Integration\Payment\Drivers\ZarinPalDriver;
use App\Shared\Money\Money;
use Illuminate\Support\Facades\Http;

test('zarinpal driver creates payment intent matching official v4 response fixture', function (): void {
    // Official ZarinPal REST v4 request.json fixture
    Http::fake([
        'https://payment.zarinpal.com/pg/v4/payment/request.json' => Http::response([
            'data' => [
                'code' => 100,
                'message' => 'Operation was successful',
                'authority' => 'A00000000000000000000000000012345678',
                'fee_type' => 'Merchant',
                'fee' => 10000,
            ],
            'errors' => [],
        ], 200),
    ]);

    $driver = new ZarinPalDriver(merchantId: 'test-merchant-uuid', sandbox: false);
    $result = $driver->createIntent(
        amount: Money::fromRials(1500000),
        description: 'شارژ کیف پول پیشخوان',
        callbackUrl: 'https://pishkhan.ir/wallet/verify',
        meta: ['mobile' => '09123456789']
    );

    expect($result->isSuccess)->toBeTrue()
        ->and($result->gatewayName)->toBe('zarinpal')
        ->and($result->authority)->toBe('A00000000000000000000000000012345678')
        ->and($result->paymentUrl)->toBe('https://payment.zarinpal.com/pg/StartPay/A00000000000000000000000000012345678');
});

test('zarinpal driver verifies transaction matching official v4 verify fixture', function (): void {
    // Official ZarinPal REST v4 verify.json fixture
    Http::fake([
        'https://payment.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
            'data' => [
                'code' => 100,
                'message' => 'Verified',
                'card_hash' => '1E2B3C4D5E6F7G8H',
                'card_pan' => '603799******1234',
                'ref_id' => 987654321,
                'fee_type' => 'Merchant',
                'fee' => 10000,
            ],
            'errors' => [],
        ], 200),
    ]);

    $driver = new ZarinPalDriver(merchantId: 'test-merchant-uuid', sandbox: false);
    $result = $driver->verify(
        authority: 'A00000000000000000000000000012345678',
        expectedAmount: Money::fromRials(1500000)
    );

    expect($result->isSuccess)->toBeTrue()
        ->and($result->gatewayName)->toBe('zarinpal')
        ->and($result->refId)->toBe('987654321')
        ->and($result->cardPanMasked)->toBe('603799******1234')
        ->and($result->amountRials)->toBe(1500000);
});

test('zarinpal driver handles verification failure with official error code', function (): void {
    Http::fake([
        'https://payment.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
            'data' => [
                'code' => -51,
                'message' => 'Session is not active or payment failed',
            ],
            'errors' => ['Session expired'],
        ], 200),
    ]);

    $driver = new ZarinPalDriver(merchantId: 'test-merchant-uuid', sandbox: false);
    $result = $driver->verify(
        authority: 'A000000000000000000000000000FAIL0000',
        expectedAmount: Money::fromRials(500000)
    );

    expect($result->isSuccess)->toBeFalse()
        ->and($result->errorMessage)->toContain('[-51]');
});

test('fake driver provides deterministic offline payment simulation', function (): void {
    $fake = new FakeDriver;

    // 1. Success intent
    $intent = $fake->createIntent(Money::fromRials(2000000), 'شارژ آفلاین', 'https://example.com');
    expect($intent->isSuccess)->toBeTrue()
        ->and($intent->authority)->toStartWith('FAKE-AUTH-')
        ->and($intent->paymentUrl)->toContain('sandbox.pishkhan.ir');

    // 2. Success verification
    $verify = $fake->verify($intent->authority, Money::fromRials(2000000));
    expect($verify->isSuccess)->toBeTrue()
        ->and($verify->refId)->toStartWith('REF-')
        ->and($verify->cardPanMasked)->toStartWith('603799******');

    // 3. Simulated failure
    $failVerify = $fake->verify('FAIL-AUTH-999', Money::fromRials(100000));
    expect($failVerify->isSuccess)->toBeFalse();
});
