<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Integration\Sms\SmsGateway;
use App\Modules\Identity\Domain\Enums\DelegationStatus;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Delegation;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use App\Shared\Crypto\EnvelopeEncryptor;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CreateDelegationAction
{
    public function __construct(
        private readonly SmsGateway $smsGateway,
        private readonly EnvelopeEncryptor $encryptor,
    ) {}

    /**
     * Create a pending legal delegation and deliver two-party OTP codes via SMS (§5.3, §7.1 T4, TASK-121).
     *
     * @param  list<string>|null  $allowedServiceIds
     * @return array{delegation: Delegation, principal_otp: string, delegate_otp: string}
     */
    public function execute(
        Citizen $principal,
        string $delegateNationalIdOrMobile,
        CarbonInterface $validUntil,
        int $maxAmountRials,
        ?array $allowedServiceIds = null,
        ?string $documentNumber = null
    ): array {
        if ($validUntil->isPast()) {
            throw new InvalidArgumentException('تاریخ پایان اعتبار نمایندگی باید در آینده باشد (§5.3).');
        }

        if ($maxAmountRials <= 0) {
            throw new InvalidArgumentException('سقف مبلغ نمایندگی باید اکیداً مثبت باشد (§5.3).');
        }

        // Find delegate citizen by peppered blind-index hashes — the same scheme
        // HashedCast/EnvelopeEncryptor uses for citizens' national_id_hash/mobile_hash.
        // A plain sha256 lookup would never match real citizen records.
        $nationalHash = $this->encryptor->hashIndex($delegateNationalIdOrMobile);
        $mobileHash = $this->encryptor->hashIndex($delegateNationalIdOrMobile);

        /** @var Citizen|null $delegate */
        $delegate = Citizen::query()
            ->where('national_id_hash', $nationalHash)
            ->orWhere('mobile_hash', $mobileHash)
            ->first();

        if ($delegate === null) {
            throw new InvalidArgumentException('شهروند وکیل/نماینده با مشخصات وارد شده در سامانه یافت نشد.');
        }

        if ($delegate->id === $principal->id) {
            throw new InvalidArgumentException('شما نمی‌توانید خود را به عنوان نماینده خود تعیین نمایید.');
        }

        return DB::transaction(function () use (
            $principal,
            $delegate,
            $validUntil,
            $maxAmountRials,
            $allowedServiceIds,
            $documentNumber
        ): array {
            // Generate two separate random 6-digit OTP codes
            $principalOtp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $delegateOtp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            $expiresAt = CarbonImmutable::now()->addMinutes(15);

            /** @var Delegation $delegation */
            $delegation = Delegation::query()->create([
                'id' => (string) Str::uuid(),
                'principal_citizen_id' => $principal->id,
                'delegate_citizen_id' => $delegate->id,
                'document_number' => $documentNumber,
                'status' => DelegationStatus::PendingOtp,
                'max_amount_rials' => $maxAmountRials,
                'allowed_service_ids' => $allowedServiceIds,
                'principal_otp_hash' => Hash::driver('bcrypt')->make($principalOtp),
                'delegate_otp_hash' => Hash::driver('bcrypt')->make($delegateOtp),
                'principal_otp_verified' => false,
                'delegate_otp_verified' => false,
                'otp_expires_at' => $expiresAt,
                'valid_until' => $validUntil,
            ]);

            AuditLogger::record(
                action: AuditableAction::DELEGATION_CREATED,
                subject: $delegation,
                changes: [
                    'delegation_id' => $delegation->id,
                    'principal_id' => $principal->id,
                    'delegate_id' => $delegate->id,
                    'max_amount_rials' => $maxAmountRials,
                    'valid_until' => $validUntil->toIso8601String(),
                ],
                actorType: Citizen::class,
                actorId: $principal->id
            );

            // Deliver each party's OTP to their own mobile (§7.1 T4, TASK-121).
            // Without this dispatch the delegation could never be activated.
            $principalMobile = $principal->mobile;
            if (is_string($principalMobile) && $principalMobile !== '') {
                $this->smsGateway->sendOtp(mobile: $principalMobile, code: $principalOtp, purpose: OtpPurpose::DELEGATION);
            }

            $delegateMobile = $delegate->mobile;
            if (is_string($delegateMobile) && $delegateMobile !== '') {
                $this->smsGateway->sendOtp(mobile: $delegateMobile, code: $delegateOtp, purpose: OtpPurpose::DELEGATION);
            }

            return [
                'delegation' => $delegation->fresh(['principal', 'delegate']),
                'principal_otp' => $principalOtp,
                'delegate_otp' => $delegateOtp,
            ];
        });
    }
}
