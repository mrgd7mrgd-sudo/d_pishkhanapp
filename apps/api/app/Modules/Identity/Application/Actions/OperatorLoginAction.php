<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Integration\Sms\SmsGateway;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

final class OperatorLoginAction
{
    public function __construct(
        private readonly SmsGateway $smsGateway
    ) {}

    /**
     * Authenticate operator credentials (Step 1) and dispatch OTP.
     *
     * @return array{
     *     challenge_id: string,
     *     expires_at: string,
     *     resend_available_at: string,
     *     masked_mobile: string
     * }
     */
    public function execute(
        string $officeCode,
        string $username,
        string $password,
        ?string $ipAddress
    ): array {
        /** @var Operator|null $operator */
        $operator = Operator::query()->where('username', $username)->first();

        if ($operator === null || ! $operator->is_active) {
            $this->abortInvalidCredentials();
        }

        // 1. Verify Office Code
        if ($operator->office_id === null) {
            $this->abortInvalidCredentials();
        }

        /** @var Office|null $office */
        $office = Office::query()->find($operator->office_id);
        if ($office === null || $office->code !== $officeCode) {
            $this->abortInvalidCredentials();
        }

        // 2. Verify IP Whitelist / Restrictions (§7.2)
        if (! $this->isIpAllowed($operator->allowed_ip_ranges, $ipAddress)) {
            AuditLogger::record(
                action: AuditableAction::AUTH_LOGIN_FAILED,
                subject: $operator,
                changes: ['reason' => 'ip_restricted', 'client_ip' => $ipAddress],
                context: ['username' => $username, 'office_code' => $officeCode]
            );

            $this->abortForbiddenIp();
        }

        // 3. Verify Password using Argon2id
        if (! Hash::check($password, $operator->password_hash)) {
            AuditLogger::record(
                action: AuditableAction::AUTH_LOGIN_FAILED,
                subject: $operator,
                changes: ['reason' => 'invalid_password'],
                context: ['username' => $username, 'office_code' => $officeCode]
            );

            $this->abortInvalidCredentials();
        }

        // 4. Retrieve operator mobile number
        $mobile = $operator->mobile;
        if ($mobile === null || $mobile === '') {
            $this->abortInvalidCredentials();
        }

        // 5. Generate cryptographically secure 5-digit OTP
        $rawCode = (string) random_int(10000, 99999);

        // 6. Create OTP Challenge (120s TTL)
        $challenge = OtpChallenge::createChallenge(
            mobile: $mobile,
            rawCode: $rawCode,
            purpose: OtpPurpose::LOGIN,
            ipAddress: $ipAddress,
            ttlSeconds: 120
        );

        // 7. Dispatch SMS through gateway
        $this->smsGateway->sendOtp(
            mobile: $mobile,
            code: $rawCode,
            purpose: OtpPurpose::LOGIN
        );

        $maskedMobile = $this->maskMobile($mobile);

        // 8. Record audit log
        AuditLogger::record(
            action: AuditableAction::AUTH_OTP_REQUESTED,
            subject: $challenge,
            changes: [
                'purpose' => OtpPurpose::LOGIN->value,
                'operator_id' => $operator->id,
                'masked_mobile' => $maskedMobile,
            ],
            context: [
                'challenge_id' => $challenge->id,
                'ip_address' => $ipAddress,
            ]
        );

        $now = CarbonImmutable::now();
        $resendAvailableAt = $now->addSeconds(30);

        return [
            'challenge_id' => $challenge->id,
            'expires_at' => $challenge->expires_at->toIso8601String(),
            'resend_available_at' => $resendAvailableAt->toIso8601String(),
            'masked_mobile' => $maskedMobile,
        ];
    }

    /**
     * Check if client IP is within allowed CIDR or IP list.
     */
    private function isIpAllowed(?string $allowedIpRanges, ?string $clientIp): bool
    {
        if ($allowedIpRanges === null || trim($allowedIpRanges) === '') {
            return true;
        }

        if ($clientIp === null || $clientIp === '') {
            return false;
        }

        $ranges = array_filter(array_map('trim', explode(',', $allowedIpRanges)));
        foreach ($ranges as $range) {
            if ($range === $clientIp) {
                return true;
            }

            if (str_contains($range, '/')) {
                if ($this->cidrMatch($clientIp, $range)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IPv4 matches CIDR subnet.
     */
    private function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int) $bits);
        $subnetLong &= $mask;

        return ($ipLong & $mask) === $subnetLong;
    }

    private function maskMobile(string $mobile): string
    {
        $len = strlen($mobile);
        if ($len < 7) {
            return $mobile;
        }

        $prefix = substr($mobile, 0, 4);
        $suffix = substr($mobile, -4);

        return $prefix.'***'.$suffix;
    }

    private function abortInvalidCredentials(): never
    {
        $requestId = (string) Request::header('X-Request-Id', 'req_unknown');
        $instance = Request::path();

        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/auth-invalid-credentials',
            'title' => 'اطلاعات ورود نامعتبر است',
            'status' => 401,
            'code' => 'AUTH_INVALID_CREDENTIALS',
            'detail' => 'کد دفتر، نام کاربری یا رمز عبور اشتباه است.',
            'instance' => $instance,
            'request_id' => $requestId,
            'errors' => null,
        ], 401);

        throw new HttpResponseException($response);
    }

    private function abortForbiddenIp(): never
    {
        $requestId = (string) Request::header('X-Request-Id', 'req_unknown');
        $instance = Request::path();

        $response = new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/auth-ip-restricted',
            'title' => 'دسترسی غیرمجاز از این آدرس IP',
            'status' => 403,
            'code' => 'AUTH_IP_RESTRICTED',
            'detail' => 'ورود به سامانه از آدرس IP شما مجاز نمی‌باشد.',
            'instance' => $instance,
            'request_id' => $requestId,
            'errors' => null,
        ], 403);

        throw new HttpResponseException($response);
    }
}
