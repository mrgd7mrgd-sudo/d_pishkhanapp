<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Integration\Sms\SmsGateway;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\OtpChallenge;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use App\Shared\Crypto\EnvelopeEncryptor;
use Carbon\CarbonImmutable;
use Random\RandomException;

final class SendOtpAction
{
    public function __construct(
        private readonly SmsGateway $smsGateway,
        private readonly EnvelopeEncryptor $encryptor
    ) {}

    /**
     * Generate 5-digit OTP challenge, dispatch SMS, record audit event, and return challenge details.
     *
     * @return array{
     *     challenge_id: string,
     *     expires_at: string,
     *     resend_available_at: string,
     *     masked_mobile: string,
     *     is_new_user: bool
     * }
     *
     * @throws RandomException
     */
    public function execute(
        string $mobile,
        ?string $nationalId,
        OtpPurpose $purpose,
        ?string $ipAddress
    ): array {
        // 1. Generate cryptographically secure 5-digit code (§5.6 #1, TASK-026)
        $rawCode = (string) random_int(10000, 99999);

        // 2. Persist challenge with bcrypt code_hash and 120s TTL
        $challenge = OtpChallenge::createChallenge(
            mobile: $mobile,
            rawCode: $rawCode,
            purpose: $purpose,
            ipAddress: $ipAddress,
            ttlSeconds: 120
        );

        // 3. Dispatch SMS through gateway (Fake in test/local, Kavenegar/CircuitBreaker in staging/prod)
        $this->smsGateway->sendOtp(
            mobile: $mobile,
            code: $rawCode,
            purpose: $purpose
        );

        // 4. Check if citizen exists by blind index mobile hash
        $mobileHash = $this->encryptor->hashIndex($mobile);
        $citizenExists = Citizen::query()->where('mobile_hash', $mobileHash)->exists();

        // 5. If national_id was provided, check by national_id_hash as well
        if (! $citizenExists && $nationalId !== null && $nationalId !== '') {
            $nationalIdHash = $this->encryptor->hashIndex($nationalId);
            $citizenExists = Citizen::query()->where('national_id_hash', $nationalIdHash)->exists();
        }

        $maskedMobile = $this->maskMobile($mobile);

        // 6. Record immutable audit log without raw PII (§7.6, §7.7)
        AuditLogger::record(
            action: AuditableAction::AUTH_OTP_REQUESTED,
            subject: $challenge,
            changes: [
                'purpose' => $purpose->value,
                'masked_mobile' => $maskedMobile,
                'is_new_user' => ! $citizenExists,
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
            'is_new_user' => ! $citizenExists,
        ];
    }

    /**
     * Mask mobile number: e.g., 09123456781 -> 0912***6781 (§5.6 #1, §7.7)
     */
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
}
