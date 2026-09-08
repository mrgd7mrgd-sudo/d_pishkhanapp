<?php

declare(strict_types=1);

namespace App\Integration\Sms;

use App\Integration\Sms\Enums\SmsDeliveryStatus;
use App\Modules\Identity\Domain\Enums\OtpPurpose;

/**
 * SmsGateway Port (§8.0, §8.3)
 */
interface SmsGateway
{
    /**
     * Send OTP message with highest priority.
     */
    public function sendOtp(string $mobile, string $code, OtpPurpose $purpose): SmsResult;

    /**
     * Send structured template message (§8.3).
     *
     * @param  array<string, string|int>  $params
     */
    public function sendTemplate(string $mobile, string $template, array $params): SmsResult;

    /**
     * Get delivery status of message by ID.
     */
    public function status(string $messageId): SmsDeliveryStatus;
}
