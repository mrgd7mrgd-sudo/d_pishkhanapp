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
 * Zibal Payment Gateway Driver (Architecture §8.0, §8.2, TASK-085).
 */
final class ZibalDriver implements PaymentGateway
{
    private const BASE_URL = 'https://gateway.zibal.ir/v1';

    private const START_PAY_URL = 'https://gateway.zibal.ir/start';

    public function __construct(
        private readonly string $merchant = 'zibal',
        private readonly bool $sandbox = false
    ) {}

    public function name(): string
    {
        return 'zibal';
    }

    public function createIntent(
        Money $amount,
        string $description,
        string $callbackUrl,
        array $meta = []
    ): PaymentIntentResult {
        try {
            $payload = [
                'merchant' => $this->merchant,
                'amount' => $amount->getAmountRials(),
                'callbackUrl' => $callbackUrl,
                'description' => $description,
                'orderId' => $meta['order_id'] ?? null,
                'mobile' => $meta['mobile'] ?? null,
                'nationalCode' => $meta['national_id'] ?? null,
            ];

            $response = Http::timeout(10)
                ->retry(2, 100)
                ->post(self::BASE_URL.'/request', array_filter($payload));

            if ($response->successful()) {
                $body = $response->json();
                $result = (int) ($body['result'] ?? 0);

                if ($result === 100 && ! empty($body['trackId'])) {
                    $trackId = (string) $body['trackId'];
                    $paymentUrl = self::START_PAY_URL.'/'.$trackId;

                    return PaymentIntentResult::success(
                        gatewayName: $this->name(),
                        authority: $trackId,
                        paymentUrl: $paymentUrl,
                        rawResponse: (array) $body
                    );
                }

                $msg = (string) ($body['message'] ?? 'Unknown Zibal error');

                return PaymentIntentResult::failure(
                    gatewayName: $this->name(),
                    errorMessage: "Zibal request failed with code [{$result}]: {$msg}",
                    rawResponse: $body ?? []
                );
            }

            return PaymentIntentResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'Zibal HTTP error: '.$response->status(),
                rawResponse: $response->json() ?? []
            );
        } catch (Throwable $e) {
            return PaymentIntentResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'Zibal connection exception: '.$e->getMessage()
            );
        }
    }

    public function verify(string $authority, Money $expectedAmount): PaymentVerificationResult
    {
        try {
            $payload = [
                'merchant' => $this->merchant,
                'trackId' => $authority,
            ];

            $response = Http::timeout(10)
                ->retry(2, 100)
                ->post(self::BASE_URL.'/verify', $payload);

            if ($response->successful()) {
                $body = $response->json();
                $result = (int) ($body['result'] ?? 0);

                // 100 = success, 201 = already verified
                if (in_array($result, [100, 201], true)) {
                    $refNumber = isset($body['refNumber']) ? (string) $body['refNumber'] : $authority;
                    $paidAmount = isset($body['amount']) ? (int) $body['amount'] : $expectedAmount->getAmountRials();
                    $cardNumber = isset($body['cardNumber']) ? (string) $body['cardNumber'] : null;

                    return PaymentVerificationResult::success(
                        gatewayName: $this->name(),
                        refId: $refNumber,
                        amountRials: $paidAmount,
                        cardPanMasked: $cardNumber,
                        rawResponse: $body ?? []
                    );
                }

                $msg = (string) ($body['message'] ?? 'Zibal verification failed');

                return PaymentVerificationResult::failure(
                    gatewayName: $this->name(),
                    errorMessage: "Zibal verify failed with code [{$result}]: {$msg}",
                    rawResponse: $body ?? []
                );
            }

            return PaymentVerificationResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'Zibal verify HTTP error: '.$response->status(),
                rawResponse: $response->json() ?? []
            );
        } catch (Throwable $e) {
            return PaymentVerificationResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'Zibal verify exception: '.$e->getMessage()
            );
        }
    }

    public function refund(string $refId, Money $amount, string $reason): RefundResult
    {
        try {
            if ($this->sandbox) {
                return RefundResult::success(
                    gatewayName: $this->name(),
                    refundRefId: 'ZB-REFUND-'.uniqid(),
                    amountRials: $amount->getAmountRials(),
                    rawResponse: ['status' => 'sandbox_refund_success', 'ref_id' => $refId]
                );
            }

            $response = Http::timeout(10)
                ->retry(2, 100)
                ->post(self::BASE_URL.'/refund', [
                    'merchant' => $this->merchant,
                    'refNumber' => $refId,
                    'amount' => $amount->getAmountRials(),
                    'reason' => $reason,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $refundRefId = (string) ($body['id'] ?? ('ZB-REF-'.uniqid()));

                return RefundResult::success(
                    gatewayName: $this->name(),
                    refundRefId: $refundRefId,
                    amountRials: $amount->getAmountRials(),
                    rawResponse: $body ?? []
                );
            }

            return RefundResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'Zibal refund HTTP error: '.$response->status(),
                rawResponse: $response->json() ?? []
            );
        } catch (Throwable $e) {
            return RefundResult::failure(
                gatewayName: $this->name(),
                errorMessage: 'Zibal refund exception: '.$e->getMessage()
            );
        }
    }
}
