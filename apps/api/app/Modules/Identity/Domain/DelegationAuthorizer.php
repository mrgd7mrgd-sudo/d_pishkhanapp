<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

use App\Modules\Identity\Domain\Enums\DelegationStatus;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Delegation;
use Carbon\CarbonImmutable;

/**
 * Single source of truth for delegation-based authorization (Architecture §7.3):
 * "Does an active delegation authorize this delegate to act on behalf of
 * this principal for this service?"
 *
 * Replaces the previously duplicated active-delegation queries that lived
 * in CaseRequestPolicy, CaseDocumentPolicy and IssueSignedUrlAction.
 */
final class DelegationAuthorizer
{
    /**
     * The active delegation (if any) authorizing the delegate to act on
     * behalf of the principal for the given service.
     */
    public function activeDelegationFor(Citizen $delegate, string $principalCitizenId, string $serviceId): ?Delegation
    {
        return Delegation::query()
            ->where('principal_citizen_id', $principalCitizenId)
            ->where('delegate_citizen_id', $delegate->id)
            ->where('status', DelegationStatus::Active)
            ->where('valid_until', '>', CarbonImmutable::now())
            ->get()
            ->first(fn (Delegation $delegation): bool => $delegation->isServiceAllowed($serviceId));
    }

    /**
     * Whether the delegate is currently authorized to act on behalf of the
     * principal for the given service.
     */
    public function isAuthorizedFor(Citizen $delegate, string $principalCitizenId, string $serviceId): bool
    {
        return $this->activeDelegationFor($delegate, $principalCitizenId, $serviceId) !== null;
    }
}
