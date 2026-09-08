<?php

declare(strict_types=1);

namespace App\Integration\Sms;

use App\Integration\Sms\Enums\SmsDeliveryStatus;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CircuitBreaker SMS Gateway Implementation (§8.0, §8.3, TASK-025)
 * Handles automatic failover to fallback driver (SMS.ir) after 3 consecutive failures.
 */
final class CircuitBreakerSmsGateway implements SmsGateway
{
    private const FAIL_COUNT_KEY = 'cb:sms:kavenegar:fail_count';

    private const CIRCUIT_OPEN_KEY = 'cb:sms:kavenegar:circuit_open';

    private const THRESHOLD = 3;

    private const RETRY_TIMEOUT_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly SmsGateway $primaryDriver,
        private readonly SmsGateway $fallbackDriver
    ) {}

    public function sendOtp(string $mobile, string $code, OtpPurpose $purpose): SmsResult
    {
        if ($this->isCircuitOpen()) {
            Log::warning('SMS Circuit Breaker OPEN: Routing sendOtp directly to fallback driver.');

            return $this->fallbackDriver->sendOtp($mobile, $code, $purpose);
        }

        $result = $this->primaryDriver->sendOtp($mobile, $code, $purpose);

        if ($result->isSuccess) {
            $this->recordSuccess();

            return $result;
        }

        $this->recordFailure();
        Log::warning('SMS primary driver failed. Attempting fallback driver.', ['error' => $result->errorMessage]);

        return $this->fallbackDriver->sendOtp($mobile, $code, $purpose);
    }

    public function sendTemplate(string $mobile, string $template, array $params): SmsResult
    {
        if ($this->isCircuitOpen()) {
            Log::warning('SMS Circuit Breaker OPEN: Routing sendTemplate directly to fallback driver.');

            return $this->fallbackDriver->sendTemplate($mobile, $template, $params);
        }

        $result = $this->primaryDriver->sendTemplate($mobile, $template, $params);

        if ($result->isSuccess) {
            $this->recordSuccess();

            return $result;
        }

        $this->recordFailure();
        Log::warning('SMS primary driver failed template. Attempting fallback driver.', ['error' => $result->errorMessage]);

        return $this->fallbackDriver->sendTemplate($mobile, $template, $params);
    }

    public function status(string $messageId): SmsDeliveryStatus
    {
        return $this->primaryDriver->status($messageId);
    }

    private function isCircuitOpen(): bool
    {
        return Cache::has(self::CIRCUIT_OPEN_KEY);
    }

    private function recordSuccess(): void
    {
        Cache::forget(self::FAIL_COUNT_KEY);
        Cache::forget(self::CIRCUIT_OPEN_KEY);
    }

    private function recordFailure(): void
    {
        $fails = (int) Cache::get(self::FAIL_COUNT_KEY, 0) + 1;
        Cache::put(self::FAIL_COUNT_KEY, $fails, self::RETRY_TIMEOUT_SECONDS);

        if ($fails >= self::THRESHOLD) {
            Cache::put(self::CIRCUIT_OPEN_KEY, true, self::RETRY_TIMEOUT_SECONDS);
            Log::error("SMS Circuit Breaker TRIPPED: Primary driver failed {$fails} times. Switched to fallback for 5 minutes.");
        }
    }
}
