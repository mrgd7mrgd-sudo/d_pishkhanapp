<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Domain\Enums\DelegationStatus;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Delegation;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class RevokeDelegationAction
{
    /**
     * Revoke legal delegation immediately by the principal.
     */
    public function execute(Citizen $principal, string $delegationId): Delegation
    {
        /** @var Delegation|null $delegation */
        $delegation = Delegation::query()->find($delegationId);
        if ($delegation === null) {
            throw new InvalidArgumentException('رکورد نمایندگی یافت نشد.');
        }

        if ($delegation->principal_citizen_id !== $principal->id) {
            throw new InvalidArgumentException('تنها موکل می‌تواند نمایندگی را ابطال نماید.');
        }

        if ($delegation->status === DelegationStatus::Revoked) {
            return $delegation;
        }

        return DB::transaction(function () use ($principal, $delegation): Delegation {
            $now = CarbonImmutable::now();
            $delegation->update([
                'status' => DelegationStatus::Revoked,
                'revoked_at' => $now,
            ]);

            AuditLogger::record(
                action: AuditableAction::DELEGATION_REVOKED,
                subject: $delegation,
                changes: [
                    'delegation_id' => $delegation->id,
                    'status' => DelegationStatus::Revoked->value,
                    'revoked_at' => $now->toIso8601String(),
                ],
                actorType: Citizen::class,
                actorId: $principal->id
            );

            return $delegation->fresh(['principal', 'delegate']);
        });
    }
}
