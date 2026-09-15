<?php

declare(strict_types=1);

namespace App\Integration\Payment\Drivers;

use App\Integration\Payment\PaymentGateway;
use App\Integration\Payment\PaymentIntentResult;
use App\Integration\Payment\PaymentVerificationResult;
use App\Integration\Payment\RefundResult;
use App\Shared\Money\Money;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * ZarinPal Payment Gateway Driver (Architecture §8.0, §8.2, TASK-085).
 */
final class ZarinPalDriver implements PaymentGateway
{
    private readonly string $baseUrl;

    private readonly string $startPayUrl;

    public function __construct(
        private readonly string $merchantId,
        private readonly bool $sandbox = false
    ) {
        if ($this->sandbox) {
            $this->baseUrl = 'https://sandbox.zarinpal.com/pg/v4/payment';
            $this->startPayUrl = 'https://sandbox.zarinpal.com/pg/StartPay';
        } else {
            $this->baseUrl = 'https://payment.zarinpal.com/pg/v4/payment';
            $this->startPayUrl = 'https://payment.zarinpal.com/pg/StartPay';
        }
    }

    public function name(): string
    {
        return 'zarinpal';
    }

    public function createIntent(
        Money $amount,
        string $description,
        string $callbackUrl,
        array $meta = []
    ): PaymentIntentResult {
        try {
            $payload = [
                'merchant_id' => $this->merchantId,
                'amount' => $amount->getAmountRials(),
                'currency' => 'IRR',
                'description' => $description,
                'callback_url' => $callbackUrl,
                'metadata' => array_filter([
                    'mobile' => $meta['mobile'] ?? null,
                    'national_id' => $meta['national_id'] ?? null,
                    'order_id' => $meta['order_id'] ?? null,
                ]),
            ];

            $response = Http::timeout(10)
                ->retry(2, 100)
                ->post("{$this->baseUrl}/request.json", $payload);

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['data'] ?? [];
                $code = (int) ($data['code'] ?? 0);

                if ($code === 100 && ! empty($data['authority'])) {
                    $authority = (string) $data['authority'];
                    $paymentUrl = "{$this->startPayUrl}/{$authority}";

                    return PaymentIntentResult::success(
                        gatewayName: $this->name(),
                        authority: $authority,
                        paymentUrl: $paymentUrl,
                        rawResponse: $body ?? []
                    );
                }

                $errors = $body['errors'] ?? [];
                $errorMsg = is_array($errors) ? json_encode($errors) : (string) $errors;

                return PaymentIntentResult::failure(
                    gatewayName: $this->name(),
                    errorMessage: "ZarinPal request failed with code [{$code}]: {$errorMsg}",
                    rawResponse: $body ?? []
                );
            }

            return PaymentIntentResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'ZarinPal HTTP error: '.$response->status(),
                rawResponse: $response->json() ?? []
            );
        } catch (Throwable $e) {
            return PaymentIntentResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'ZarinPal connection exception: '.$e->getMessage()
            );
        }
    }

    public function verify(string $authority, Money $expectedAmount): PaymentVerificationResult
    {
        try {
            $payload = [
                'merchant_id' => $this->merchantId,
                'amount' => $expectedAmount->getAmountRials(),
                'authority' => $authority,
            ];

            $response = Http::timeout(10)
                ->retry(2, 100)
                ->post("{$this->baseUrl}/verify.json", $payload);

            if ($response->successful()) {
                $body = $response->json();
                $data = $body['data'] ?? [];
                $code = (int) ($data['code'] ?? 0);

                // Code 100 = First verification, 101 = Already verified
                if (in_array($code, [100, 101], true) && ! empty($data['ref_id'])) {
                    return PaymentVerificationResult::success(
                        gatewayName: $this->name(),
                        refId: (string) $data['ref_id'],
                        amountRials: $expectedAmount->getAmountRials(),
                        cardPanMasked: isset($data['card_pan']) ? (string) $data['card_pan'] : null,
                        cardHash: isset($data['card_hash']) ? (string) $data['card_hash'] : null,
                        rawResponse: $body ?? []
                    );
                }

                $errors = $body['errors'] ?? [];
                $errorMsg = is_array($errors) ? json_encode($errors) : (string) $errors;

                return PaymentVerificationResult::failure(
                    gatewayName: $this->name(),
                    errorMessage: "ZarinPal verify failed with code [{$code}]: {$errorMsg}",
                    rawResponse: $body ?? []
                );
            }

            return PaymentVerificationResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'ZarinPal HTTP error: '.$response->status(),
                rawResponse: $response->json() ?? []
            );
        } catch (Throwable $e) {
            return PaymentVerificationResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'ZarinPal verify exception: '.$e->getMessage()
            );
        }
    }

    public function refund(string $refId, Money $amount, string $reason): RefundResult
    {
        // ZarinPal refund API endpoint (or simulated in sandbox)
        try {
            if ($this->sandbox) {
                return RefundResult::success(
                    gatewayName: $this->name(),
                    refundRefId: 'ZP-REFUND-'.uniqid(),
                    amountRials: $amount->getAmountRials(),
                    rawResponse: ['status' => 'sandbox_refund_success', 'ref_id' => $refId]
                );
            }

            $response = Http::timeout(10)
                ->retry(2, 100)
                ->post("{$this->baseUrl}/refund.json", [
                    'merchant_id' => $this->merchantId,
                    'ref_id' => $refId,
                    'amount' => $amount->getAmountRials(),
                    'reason' => $reason,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $refundRefId = (string) ($body['data']['session_id'] ?? ('ZP-REF-'.uniqid()));

                return RefundResult::success(
                    gatewayName: $this->name(),
                    refundRefId: $refundRefId,
                    amountRials: $amount->getAmountRials(),
                    rawResponse: $body ?? []
                );
            }

            return RefundResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'ZarinPal refund HTTP error: '.$response->status(),
                rawResponse: $response->json() ?? []
            );
        } catch (Throwable $e) {
            return RefundResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'ZarinPal refund exception: '.$e->getMessage()
            );
        }
    }
}
