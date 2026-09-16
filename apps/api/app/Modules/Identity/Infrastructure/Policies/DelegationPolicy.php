<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Delegation;
use Illuminate\Contracts\Auth\Authenticatable;

final class DelegationPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $this->isSystemAdmin($user);
    }

    public function view(?Authenticatable $user, Delegation $delegation): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Citizen && (
            $delegation->principal_citizen_id === $user->id ||
            $delegation->delegate_citizen_id === $user->id
        );
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, Delegation $delegation): bool
    {
        return $user instanceof Citizen && $delegation->principal_citizen_id === $user->id;
    }

    public function delete(?Authenticatable $user, Delegation $delegation): bool
    {
        return $this->isSystemAdmin($user);
    }

    /**
     * Determine whether delegate can act on behalf of principal under this delegation (§7.3).
     */
    public function actOnBehalf(?Authenticatable $user, Delegation $delegation, ?string $serviceId = null, ?int $amountRials = null): bool
    {
        if (! ($user instanceof Citizen) || $user->id !== $delegation->delegate_citizen_id) {
            return false;
        }

        if (! $delegation->isActive()) {
            return false;
        }

        if ($serviceId !== null && ! $delegation->isServiceAllowed($serviceId)) {
            return false;
        }

        if ($amountRials !== null && ! $delegation->isAmountAllowed($amountRials)) {
            return false;
        }

        return true;
    }
}
