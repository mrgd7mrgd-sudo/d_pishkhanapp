<?php

declare(strict_types=1);

namespace App\Integration\Sms\Drivers;

use App\Integration\Sms\Enums\SmsDeliveryStatus;
use App\Integration\Sms\SmsGateway;
use App\Integration\Sms\SmsResult;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use Illuminate\Support\Facades\Http;
use Throwable;

final class SmsIrDriver implements SmsGateway
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $lineNumber = '30007732'
    ) {}

    public function sendOtp(string $mobile, string $code, OtpPurpose $purpose): SmsResult
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 100)
                ->withHeaders([
                    'X-SMS-IR-API-KEY' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.sms.ir/v1/send/verify', [
                    'mobile' => $mobile,
                    'templateId' => 100000,
                    'lineNumber' => $this->lineNumber,
                    'parameters' => [
                        ['name' => 'Code', 'value' => $code],
                    ],
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $messageId = isset($body['data']['messageId']) ? (string) $body['data']['messageId'] : 'smsir-'.uniqid();

                return SmsResult::success('smsir', $messageId, $body ?? []);
            }

            return SmsResult::failure('smsir', 'HTTP error: '.$response->status(), $response->json() ?? []);
        } catch (Throwable $e) {
            return SmsResult::failure('smsir', $e->getMessage());
        }
    }

    public function sendTemplate(string $mobile, string $template, array $params): SmsResult
    {
        try {
            $formattedParams = [];
            foreach ($params as $k => $v) {
                $formattedParams[] = ['name' => (string) $k, 'value' => (string) $v];
            }

            $response = Http::timeout(10)
                ->retry(2, 100)
                ->withHeaders([
                    'X-SMS-IR-API-KEY' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.sms.ir/v1/send/verify', [
                    'mobile' => $mobile,
                    'templateId' => 200000,
                    'parameters' => $formattedParams,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $messageId = isset($body['data']['messageId']) ? (string) $body['data']['messageId'] : 'smsir-'.uniqid();

                return SmsResult::success('smsir', $messageId, $body ?? []);
            }

            return SmsResult::failure('smsir', 'HTTP error: '.$response->status(), $response->json() ?? []);
        } catch (Throwable $e) {
            return SmsResult::failure('smsir', $e->getMessage());
        }
    }

    public function status(string $messageId): SmsDeliveryStatus
    {
        return SmsDeliveryStatus::SENT;
    }
}
