<?php

declare(strict_types=1);

namespace App\Integration\Payment;

use App\Shared\Money\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CircuitBreaker Payment Gateway (Architecture §8.0, §8.2, D-23, TASK-085).
 * Automatically fails over from primary driver (ZarinPal) to fallback (Zibal) upon failures.
 */
final class CircuitBreakerPaymentGateway implements PaymentGateway
{
    private const FAIL_COUNT_KEY = 'cb:payment:primary:fail_count';

    private const CIRCUIT_OPEN_KEY = 'cb:payment:primary:circuit_open';

    private const THRESHOLD = 3;

    private const RETRY_TIMEOUT_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly PaymentGateway $primaryDriver,
        private readonly PaymentGateway $fallbackDriver
    ) {}

    public function name(): string
    {
        return $this->isCircuitOpen()
            ? $this->fallbackDriver->name()
            : $this->primaryDriver->name();
    }

    public function createIntent(
        Money $amount,
        string $description,
        string $callbackUrl,
        array $meta = []
    ): PaymentIntentResult {
        if ($this->isCircuitOpen()) {
            Log::warning('Payment Gateway Circuit Breaker OPEN: Routing createIntent directly to fallback driver ('.$this->fallbackDriver->name().').');

            return $this->fallbackDriver->createIntent($amount, $description, $callbackUrl, $meta);
        }

        $result = $this->primaryDriver->createIntent($amount, $description, $callbackUrl, $meta);

        if ($result->isSuccess) {
            $this->recordSuccess();

            return $result;
        }

        $this->recordFailure();
        Log::warning('Payment primary driver failed. Failing over to fallback driver ('.$this->fallbackDriver->name().').', [
            'error' => $result->errorMessage,
        ]);

        return $this->fallbackDriver->createIntent($amount, $description, $callbackUrl, $meta);
    }

    public function verify(string $authority, Money $expectedAmount): PaymentVerificationResult
    {
        // Try verifying with primary first, if circuit is not open
        if (! $this->isCircuitOpen()) {
            $result = $this->primaryDriver->verify($authority, $expectedAmount);
            if ($result->isSuccess) {
                $this->recordSuccess();

                return $result;
            }
        }

        // Try fallback driver
        return $this->fallbackDriver->verify($authority, $expectedAmount);
    }

    public function refund(string $refId, Money $amount, string $reason): RefundResult
    {
        if ($this->isCircuitOpen()) {
            return $this->fallbackDriver->refund($refId, $amount, $reason);
        }

        $result = $this->primaryDriver->refund($refId, $amount, $reason);
        if ($result->isSuccess) {
            $this->recordSuccess();

            return $result;
        }

        $this->recordFailure();

        return $this->fallbackDriver->refund($refId, $amount, $reason);
    }

    public function isCircuitOpen(): bool
    {
        return (bool) Cache::get(self::CIRCUIT_OPEN_KEY, false);
    }

    public function recordFailure(): void
    {
        $fails = (int) Cache::get(self::FAIL_COUNT_KEY, 0) + 1;
        Cache::put(self::FAIL_COUNT_KEY, $fails, self::RETRY_TIMEOUT_SECONDS);

        if ($fails >= self::THRESHOLD) {
            Cache::put(self::CIRCUIT_OPEN_KEY, true, self::RETRY_TIMEOUT_SECONDS);
            Log::critical('Payment Circuit Breaker TRIPPED: Primary gateway failed '.self::THRESHOLD.' consecutive times. Tripping circuit to fallback.');
        }
    }

    public function recordSuccess(): void
    {
        Cache::forget(self::FAIL_COUNT_KEY);
        Cache::forget(self::CIRCUIT_OPEN_KEY);
    }

    public function reset(): void
    {
        $this->recordSuccess();
    }
}
