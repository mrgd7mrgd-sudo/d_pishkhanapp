<?php

declare(strict_types=1);

namespace App\Integration\Payment;

/**
 * PaymentVerificationResult DTO (Architecture §8.0, §8.2, TASK-085).
 */
final class PaymentVerificationResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public readonly bool $isSuccess,
        public readonly string $gatewayName,
        public readonly ?string $refId = null,
        public readonly ?int $amountRials = null,
        public readonly ?string $cardPanMasked = null,
        public readonly ?string $cardHash = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = []
    ) {}

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function success(
        string $gatewayName,
        string $refId,
        int $amountRials,
        ?string $cardPanMasked = null,
        ?string $cardHash = null,
        array $rawResponse = []
    ): self {
        return new self(
            isSuccess: true,
            gatewayName: $gatewayName,
            refId: $refId,
            amountRials: $amountRials,
            cardPanMasked: $cardPanMasked,
            cardHash: $cardHash,
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
            refId: null,
            amountRials: null,
            cardPanMasked: null,
            cardHash: null,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse
        );
    }
}
