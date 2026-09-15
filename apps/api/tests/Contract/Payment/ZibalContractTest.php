<?php

declare(strict_types=1);

namespace Tests\Contract\Payment;

use App\Integration\Payment\CircuitBreakerPaymentGateway;
use App\Integration\Payment\Drivers\ZarinPalDriver;
use App\Integration\Payment\Drivers\ZibalDriver;
use App\Shared\Money\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('zibal driver creates payment intent matching official response fixture', function (): void {
    // Official Zibal /v1/request response fixture
    Http::fake([
        'https://gateway.zibal.ir/v1/request' => Http::response([
            'trackId' => 76543210,
            'result' => 100,
            'message' => 'success',
        ], 200),
    ]);

    $driver = new ZibalDriver(merchant: 'test-merchant', sandbox: false);
    $result = $driver->createIntent(
        amount: Money::fromRials(1000000),
        description: 'شارژ از طریق زیبال',
        callbackUrl: 'https://pishkhan.ir/wallet/verify'
    );

    expect($result->isSuccess)->toBeTrue()
        ->and($result->gatewayName)->toBe('zibal')
        ->and($result->authority)->toBe('76543210')
        ->and($result->paymentUrl)->toBe('https://gateway.zibal.ir/start/76543210');
});

test('zibal driver verifies payment matching official response fixture', function (): void {
    // Official Zibal /v1/verify response fixture
    Http::fake([
        'https://gateway.zibal.ir/v1/verify' => Http::response([
            'paidAt' => '2026-09-15T12:00:00Z',
            'amount' => 1000000,
            'result' => 100,
            'status' => 1,
            'refNumber' => 99887766,
            'description' => 'تأیید پرداخت زیبال',
            'cardNumber' => '502229******4321',
            'orderId' => 'order-101',
            'message' => 'success',
        ], 200),
    ]);

    $driver = new ZibalDriver(merchant: 'test-merchant', sandbox: false);
    $result = $driver->verify(
        authority: '76543210',
        expectedAmount: Money::fromRials(1000000)
    );

    expect($result->isSuccess)->toBeTrue()
        ->and($result->gatewayName)->toBe('zibal')
        ->and($result->refId)->toBe('99887766')
        ->and($result->cardPanMasked)->toBe('502229******4321')
        ->and($result->amountRials)->toBe(1000000);
});

test('circuit breaker fails over to Zibal upon ZarinPal failures and trips circuit after 3 consecutive errors (D-23, TASK-085)', function (): void {
    Cache::flush();

    // Primary (ZarinPal) fails with 500 error
    // Fallback (Zibal) succeeds with 200
    Http::fake([
        'https://payment.zarinpal.com/*' => Http::response(['errors' => 'Gateway timeout'], 500),
        'https://gateway.zibal.ir/v1/request' => Http::response([
            'trackId' => 99881122,
            'result' => 100,
            'message' => 'success',
        ], 200),
    ]);

    $zarinpal = new ZarinPalDriver('bad-merchant');
    $zibal = new ZibalDriver('good-merchant');

    $cbGateway = new CircuitBreakerPaymentGateway($zarinpal, $zibal);

    // Initial state: circuit is closed
    expect($cbGateway->isCircuitOpen())->toBeFalse();

    // Call 1: ZarinPal fails, automatic fallback to Zibal
    $r1 = $cbGateway->createIntent(Money::fromRials(500000), 'تست فیل‌اور ۱', 'https://example.com');
    expect($r1->isSuccess)->toBeTrue()
        ->and($r1->gatewayName)->toBe('zibal')
        ->and($r1->authority)->toBe('99881122');

    // Call 2: ZarinPal fails, automatic fallback to Zibal
    $r2 = $cbGateway->createIntent(Money::fromRials(500000), 'تست فیل‌اور ۲', 'https://example.com');
    expect($r2->isSuccess)->toBeTrue()
        ->and($r2->gatewayName)->toBe('zibal');

    // Call 3: 3rd failure trips the circuit breaker
    $r3 = $cbGateway->createIntent(Money::fromRials(500000), 'تست فیل‌اور ۳', 'https://example.com');
    expect($r3->isSuccess)->toBeTrue()
        ->and($r3->gatewayName)->toBe('zibal')
        ->and($cbGateway->isCircuitOpen())->toBeTrue();

    // Call 4: Circuit is open — routes directly to fallback driver without touching primary
    $r4 = $cbGateway->createIntent(Money::fromRials(500000), 'تست فیل‌اور ۴', 'https://example.com');
    expect($r4->isSuccess)->toBeTrue()
        ->and($r4->gatewayName)->toBe('zibal')
        ->and($cbGateway->name())->toBe('zibal');
});
