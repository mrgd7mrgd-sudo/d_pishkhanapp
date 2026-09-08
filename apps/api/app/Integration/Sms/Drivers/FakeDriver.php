<?php

declare(strict_types=1);

namespace App\Integration\Sms\Drivers;

use App\Integration\Sms\Enums\SmsDeliveryStatus;
use App\Integration\Sms\SmsGateway;
use App\Integration\Sms\SmsResult;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FakeDriver implements SmsGateway
{
    public function sendOtp(string $mobile, string $code, OtpPurpose $purpose): SmsResult
    {
        $messageId = 'fake-otp-'.Str::uuid();
        Log::info("SMS FakeDriver: [OTP] Mobile: {$mobile}, Code: {$code}, Purpose: {$purpose->value}");

        return SmsResult::success('fake', $messageId, [
            'mobile' => $mobile,
            'code' => $code,
            'purpose' => $purpose->value,
        ]);
    }

    public function sendTemplate(string $mobile, string $template, array $params): SmsResult
    {
        $messageId = 'fake-tpl-'.Str::uuid();
        Log::info("SMS FakeDriver: [Template: {$template}] Mobile: {$mobile}, Params: ".json_encode($params, JSON_UNESCAPED_UNICODE));

        return SmsResult::success('fake', $messageId, [
            'mobile' => $mobile,
            'template' => $template,
            'params' => $params,
        ]);
    }

    public function status(string $messageId): SmsDeliveryStatus
    {
        return SmsDeliveryStatus::DELIVERED;
    }
}
