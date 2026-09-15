<?php

declare(strict_types=1);

namespace App\Integration\Payment;

/**
 * PaymentIntentResult DTO (Architecture §8.0, §8.2, TASK-085).
 */
final class PaymentIntentResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public readonly bool $isSuccess,
        public readonly string $gatewayName,
        public readonly ?string $authority = null,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = []
    ) {}

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function success(
        string $gatewayName,
        string $authority,
        string $paymentUrl,
        array $rawResponse = []
    ): self {
        return new self(
            isSuccess: true,
            gatewayName: $gatewayName,
            authority: $authority,
            paymentUrl: $paymentUrl,
            errorMessage: null,
            rawResponse: $rawResponse
        );
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function failure(
        string $gatewayName,
        string $errorMessage,
        array $rawResponse = []
    ): self {
        return new self(
            isSuccess: false,
            gatewayName: $gatewayName,
            authority: null,
            paymentUrl: null,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse
        );
    }
}
