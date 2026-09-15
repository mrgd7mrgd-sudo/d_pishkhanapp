<?php

declare(strict_types=1);

namespace App\Integration\Payment\Drivers;

use App\Integration\Payment\PaymentGateway;
use App\Integration\Payment\PaymentIntentResult;
use App\Integration\Payment\PaymentVerificationResult;
use App\Integration\Payment\RefundResult;
use App\Shared\Money\Money;
use Illuminate\Support\Str;

/**
 * Fake Payment Gateway Driver for deterministic offline testing (Architecture §8.0, §8.2, TASK-085).
 */
final class FakeDriver implements PaymentGateway
{
    private ?PaymentIntentResult $intentOverride = null;

    private ?PaymentVerificationResult $verificationOverride = null;

    private ?RefundResult $refundOverride = null;

    public function name(): string
    {
        return 'fake';
    }

    public function createIntent(
        Money $amount,
        string $description,
        string $callbackUrl,
        array $meta = []
    ): PaymentIntentResult {
        if ($this->intentOverride !== null) {
            return $this->intentOverride;
        }

        if (str_contains(strtolower($description), 'fail')) {
            return PaymentIntentResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'درخواست ایجاد پرداخت در درگاه شبیه‌ساز با خطا مواجه شد.'
            );
        }

        $authority = 'FAKE-AUTH-'.strtoupper(Str::random(16));
        $paymentUrl = "https://sandbox.pishkhan.ir/pay/{$authority}";

        return PaymentIntentResult::success(
            gatewayName: $this->name(),
            authority: $authority,
            paymentUrl: $paymentUrl,
            rawResponse: [
                'status' => 'ok',
                'amount_rials' => $amount->getAmountRials(),
                'callback_url' => $callbackUrl,
            ]
        );
    }

    public function verify(string $authority, Money $expectedAmount): PaymentVerificationResult
    {
        if ($this->verificationOverride !== null) {
            return $this->verificationOverride;
        }

        if (str_starts_with($authority, 'FAIL-') || str_contains($authority, 'INVALID')) {
            return PaymentVerificationResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'تأیید تراکنش در درگاه شبیه‌ساز ناموفق بود (کد نامعتبر).'
            );
        }

        $refId = 'REF-'.strtoupper(Str::random(10));
        $cardPan = '603799******'.substr((string) abs(crc32($authority)), -4);

        return PaymentVerificationResult::success(
            gatewayName: $this->name(),
            refId: $refId,
            amountRials: $expectedAmount->getAmountRials(),
            cardPanMasked: $cardPan,
            cardHash: hash('sha256', $cardPan),
            rawResponse: [
                'status' => 'verified',
                'authority' => $authority,
                'amount' => $expectedAmount->getAmountRials(),
            ]
        );
    }

    public function refund(string $refId, Money $amount, string $reason): RefundResult
    {
        if ($this->refundOverride !== null) {
            return $this->refundOverride;
        }

        if (str_starts_with($refId, 'FAIL-')) {
            return RefundResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'استرداد وجه در درگاه شبیه‌ساز با شکست مواجه شد.'
            );
        }

        $refundRefId = 'RF-'.strtoupper(Str::random(12));

        return RefundResult::success(
            gatewayName: $this->name(),
            refundRefId: $refundRefId,
            amountRials: $amount->getAmountRials(),
            rawResponse: [
                'status' => 'refunded',
                'original_ref_id' => $refId,
                'reason' => $reason,
            ]
        );
    }

    public function overrideIntentResult(?PaymentIntentResult $result): void
    {
        $this->intentOverride = $result;
    }

    public function overrideVerificationResult(?PaymentVerificationResult $result): void
    {
        $this->verificationOverride = $result;
    }

    public function overrideRefundResult(?RefundResult $result): void
    {
        $this->refundOverride = $result;
    }
}
