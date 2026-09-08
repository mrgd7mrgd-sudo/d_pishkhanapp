<?php

declare(strict_types=1);

namespace App\Integration\Sms\Drivers;

use App\Integration\Sms\Enums\SmsDeliveryStatus;
use App\Integration\Sms\SmsGateway;
use App\Integration\Sms\SmsResult;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use Illuminate\Support\Facades\Http;
use Throwable;

final class KavenegarDriver implements SmsGateway
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $sender = '10004346'
    ) {}

    public function sendOtp(string $mobile, string $code, OtpPurpose $purpose): SmsResult
    {
        try {
            $template = 'otp-login';
            $response = Http::timeout(10)
                ->retry(2, 100)
                ->get("https://api.kavenegar.com/v1/{$this->apiKey}/verify/lookup.json", [
                    'receptor' => $mobile,
                    'token' => $code,
                    'template' => $template,
                    'sender' => $this->sender,
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $entries = $body['entries'] ?? [];
                $messageId = isset($entries[0]['messageid']) ? (string) $entries[0]['messageid'] : 'kavenegar-'.uniqid();

                return SmsResult::success('kavenegar', $messageId, $body ?? []);
            }

            return SmsResult::failure('kavenegar', 'HTTP error: '.$response->status(), $response->json() ?? []);
        } catch (Throwable $e) {
            return SmsResult::failure('kavenegar', $e->getMessage());
        }
    }

    public function sendTemplate(string $mobile, string $template, array $params): SmsResult
    {
        try {
            $query = [
                'receptor' => $mobile,
                'template' => $template,
            ];

            $tokenKeys = ['token', 'token2', 'token3', 'token10', 'token20'];
            $i = 0;
            foreach ($params as $val) {
                if (isset($tokenKeys[$i])) {
                    $query[$tokenKeys[$i]] = (string) $val;
                    $i++;
                }
            }

            $response = Http::timeout(10)
                ->retry(2, 100)
                ->get("https://api.kavenegar.com/v1/{$this->apiKey}/verify/lookup.json", $query);

            if ($response->successful()) {
                $body = $response->json();
                $entries = $body['entries'] ?? [];
                $messageId = isset($entries[0]['messageid']) ? (string) $entries[0]['messageid'] : 'kavenegar-'.uniqid();

                return SmsResult::success('kavenegar', $messageId, $body ?? []);
            }

            return SmsResult::failure('kavenegar', 'HTTP error: '.$response->status(), $response->json() ?? []);
        } catch (Throwable $e) {
            return SmsResult::failure('kavenegar', $e->getMessage());
        }
    }

    public function status(string $messageId): SmsDeliveryStatus
    {
        try {
            $response = Http::timeout(5)->get("https://api.kavenegar.com/v1/{$this->apiKey}/sms/status.json", [
                'messageid' => $messageId,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $status = $body['entries'][0]['status'] ?? 0;
                // Kavenegar statuses: 1,2,4,5 = sent/delivered
                if (in_array($status, [10, 11, 4, 5], true)) {
                    return SmsDeliveryStatus::DELIVERED;
                }
                if ($status === 100) {
                    return SmsDeliveryStatus::FAILED;
                }

                return SmsDeliveryStatus::SENT;
            }

            return SmsDeliveryStatus::UNKNOWN;
        } catch (Throwable) {
            return SmsDeliveryStatus::UNKNOWN;
        }
    }
}
