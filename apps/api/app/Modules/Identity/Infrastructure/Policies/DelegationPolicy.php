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
}
