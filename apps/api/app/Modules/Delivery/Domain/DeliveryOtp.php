<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Domain;

use Illuminate\Support\Carbon;

/**
 * Value object / generator for Delivery OTP codes (Architecture §6.1, §7.4, TASK-095).
 * Generates cryptographically secure 6-digit OTP with expiry.
 */
final class DeliveryOtp
{
    public const DEFAULT_EXPIRY_DAYS = 7;

    public function __construct(
        public readonly string $code,
        public readonly Carbon $expiresAt,
    ) {}

    /**
     * Generate a new 6-digit cryptographically secure OTP.
     */
    public static function generate(?int $expiryDays = self::DEFAULT_EXPIRY_DAYS): self
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = Carbon::now()->addDays($expiryDays ?? self::DEFAULT_EXPIRY_DAYS);

        return new self($code, $expiresAt);
    }
}
