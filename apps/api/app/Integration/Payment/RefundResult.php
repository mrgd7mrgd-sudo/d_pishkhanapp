<?php

declare(strict_types=1);

namespace App\Integration\Payment;

/**
 * RefundResult DTO (Architecture §8.0, §8.2, TASK-085).
 */
final class RefundResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public readonly bool $isSuccess,
        public readonly string $gatewayName,
        public readonly ?string $refundRefId = null,
        public readonly ?int $amountRials = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = []
    ) {}

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public static function success(
        string $gatewayName,
        string $refundRefId,
        int $amountRials,
        array $rawResponse = []
    ): self {
        return new self(
            isSuccess: true,
            gatewayName: $gatewayName,
            refundRefId: $refundRefId,
            amountRials: $amountRials,
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
            refundRefId: null,
            amountRials: null,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse
        );
    }
}
