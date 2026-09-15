<?php

declare(strict_types=1);

namespace App\Integration\Payment;

use App\Shared\Money\Money;

/**
 * PaymentGateway Port Interface (Architecture §8.0, §8.2, TASK-085).
 * Anti-Corruption Layer Port for Payment Gateways.
 */
interface PaymentGateway
{
    /**
     * Create an online payment intent with the gateway.
     *
     * @param  array<string, mixed>  $meta
     */
    public function createIntent(
        Money $amount,
        string $description,
        string $callbackUrl,
        array $meta = []
    ): PaymentIntentResult;

    /**
     * Verify payment with the gateway directly after citizen returns.
     */
    public function verify(string $authority, Money $expectedAmount): PaymentVerificationResult;

    /**
     * Issue a refund for a previously verified transaction.
     */
    public function refund(string $refId, Money $amount, string $reason): RefundResult;

    /**
     * Unique identifier name of the gateway (e.g. 'zarinpal', 'zibal', 'fake').
     */
    public function name(): string;
}
