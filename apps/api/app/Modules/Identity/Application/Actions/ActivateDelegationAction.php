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
use Illuminate\Support\Facades\RateLimiter;
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

        // Brute-force protection (§7.2): 5 failed attempts per party per delegation, 15-minute lockout.
        $lockoutKey = 'delegation-otp:'.$delegationId.':'.$citizen->id;
        if (RateLimiter::tooManyAttempts($lockoutKey, 5)) {
            throw new InvalidArgumentException('به دلیل تلاش‌های ناموفق مکرر، امکان تلاش مجدد در حال حاضر وجود ندارد.');
        }

        $verified = DB::transaction(function () use ($citizen, $delegation, $isPrincipal, $isDelegate, $otpCode): bool {
            if ($isPrincipal && ! password_verify($otpCode, (string) $delegation->principal_otp_hash)) {
                return false;
            }

            if ($isDelegate && ! password_verify($otpCode, (string) $delegation->delegate_otp_hash)) {
                return false;
            }

            if ($isPrincipal) {
                $delegation->principal_otp_verified = true;
            }

            if ($isDelegate) {
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

            return true;
        });

        if (! $verified) {
            RateLimiter::hit($lockoutKey, 900);

            throw new InvalidArgumentException($isPrincipal
                ? 'کد تایید موکل نادرست است.'
                : 'کد تایید وکیل/نماینده نادرست است.');
        }

        RateLimiter::clear($lockoutKey);

        return $delegation->fresh(['principal', 'delegate']);
    }
}
