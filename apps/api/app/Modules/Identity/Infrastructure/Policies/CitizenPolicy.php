<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Contracts\Auth\Authenticatable;

final class CitizenPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        if ($user instanceof Operator) {
            return $user->hasAnyRole(['system_admin', 'auditor', 'office_manager']);
        }

        return false;
    }

    public function view(?Authenticatable $user, Citizen $citizen): bool
    {
        if ($user instanceof Citizen) {
            return $user->id === $citizen->id;
        }

        if ($user instanceof Operator) {
            return $user->hasAnyRole(['office_operator', 'office_manager', 'system_admin', 'auditor']);
        }

        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, Citizen $citizen): bool
    {
        if ($user instanceof Citizen) {
            return $user->id === $citizen->id;
        }

        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, Citizen $citizen): bool
    {
        return $this->isSystemAdmin($user);
    }
}
