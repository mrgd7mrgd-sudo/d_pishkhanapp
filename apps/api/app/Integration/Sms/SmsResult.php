<?php

declare(strict_types=1);

namespace App\Integration\Sms;

use App\Integration\Sms\Enums\SmsDeliveryStatus;

final readonly class SmsResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public bool $isSuccess,
        public string $provider,
        public ?string $messageId = null,
        public ?string $errorMessage = null,
        public SmsDeliveryStatus $status = SmsDeliveryStatus::SENT,
        public array $rawResponse = []
    ) {}

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function success(string $provider, string $messageId, array $rawResponse = []): self
    {
        return new self(
            isSuccess: true,
            provider: $provider,
            messageId: $messageId,
            status: SmsDeliveryStatus::SENT,
            rawResponse: $rawResponse
        );
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function failure(string $provider, string $errorMessage, array $rawResponse = []): self
    {
        return new self(
            isSuccess: false,
            provider: $provider,
            errorMessage: $errorMessage,
            status: SmsDeliveryStatus::FAILED,
            rawResponse: $rawResponse
        );
    }
}
