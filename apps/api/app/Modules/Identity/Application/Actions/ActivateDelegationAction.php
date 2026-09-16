<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Domain\Enums\DelegationStatus;
use App\Modules\Identity\Domain\Enums\RoleName;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Delegation;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ActivateDelegationAction
{
    /**
     * Verify OTP code from principal or delegate.
     * Invariant §5.3: Delegation ONLY becomes active when BOTH parties have verified their OTPs!
     */
    public function execute(Citizen $citizen, string $delegationId, string $otpCode): Delegation
    {
        /** @var Delegation|null $delegation */
        $delegation = Delegation::query()->with(['principal', 'delegate'])->find($delegationId);
        if ($delegation === null) {
            throw new InvalidArgumentException('رکورد نمایندگی یافت نشد.');
        }

        if ($delegation->status !== DelegationStatus::PendingOtp) {
            throw new InvalidArgumentException('این نمایندگی در وضعیت انتظار تایید پیامکی قرار ندارد.');
        }

        if ($delegation->otp_expires_at !== null && $delegation->otp_expires_at->isPast()) {
            throw new InvalidArgumentException('کد تایید پیامکی منقضی شده است.');
        }

        $isPrincipal = $delegation->principal_citizen_id === $citizen->id;
        $isDelegate = $delegation->delegate_citizen_id === $citizen->id;

        if (! $isPrincipal && ! $isDelegate) {
            throw new InvalidArgumentException('شما طرف مجاز در این قرارداد نمایندگی نیستید.');
        }

        $inputHash = hash('sha256', $otpCode);

        return DB::transaction(function () use ($citizen, $delegation, $isPrincipal, $isDelegate, $inputHash): Delegation {
            if ($isPrincipal) {
                if ($delegation->principal_otp_hash !== $inputHash) {
                    throw new InvalidArgumentException('کد تایید موکل نادرست است.');
                }
                $delegation->principal_otp_verified = true;
            }

            if ($isDelegate) {
                if ($delegation->delegate_otp_hash !== $inputHash) {
                    throw new InvalidArgumentException('کد تایید وکیل/نماینده نادرست است.');
                }
                $delegation->delegate_otp_verified = true;
            }

            // Invariant §5.3: Both must be verified to transition to active
            if ($delegation->principal_otp_verified && $delegation->delegate_otp_verified) {
                $delegation->status = DelegationStatus::Active;
                $delegation->activated_at = CarbonImmutable::now();

                // Grant citizen_delegate role to the delegate user (§7.3)
                $delegate = $delegation->delegate;
                if ($delegate !== null && ! $delegate->hasRole(RoleName::CITIZEN_DELEGATE->value)) {
                    $delegate->assignRole(RoleName::CITIZEN_DELEGATE->value);
                }

                AuditLogger::record(
                    action: AuditableAction::DELEGATION_ACTIVATED,
                    subject: $delegation,
                    changes: [
                        'delegation_id' => $delegation->id,
                        'status' => DelegationStatus::Active->value,
                        'activated_at' => $delegation->activated_at->toIso8601String(),
                    ],
                    actorType: Citizen::class,
                    actorId: $citizen->id
                );
            }

            $delegation->save();

            return $delegation->fresh(['principal', 'delegate']);
        });
    }
}
