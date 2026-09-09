<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use Illuminate\Contracts\Auth\Authenticatable;

final class OfficePolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, Office $office): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, Office $office): bool
    {
        if (! $user instanceof Operator) {
            return false;
        }

        if ($user->hasRole('office_manager') && $user->office_id === $office->id) {
            return true;
        }

        return $user->hasRole('system_admin');
    }

    public function delete(?Authenticatable $user, Office $office): bool
    {
        return $this->isSystemAdmin($user);
    }
}
