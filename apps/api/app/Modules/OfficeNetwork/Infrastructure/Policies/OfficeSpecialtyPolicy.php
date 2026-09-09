<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSpecialty;
use Illuminate\Contracts\Auth\Authenticatable;

final class OfficeSpecialtyPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, OfficeSpecialty $specialty): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->canManage($user);
    }

    public function update(?Authenticatable $user, OfficeSpecialty $specialty): bool
    {
        return $this->canManage($user, $specialty->office_id);
    }

    public function delete(?Authenticatable $user, OfficeSpecialty $specialty): bool
    {
        return $this->canManage($user, $specialty->office_id);
    }

    private function canManage(?Authenticatable $user, ?string $officeId = null): bool
    {
        if (! $user instanceof Operator) {
            return false;
        }

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($officeId !== null && $user->hasRole('office_manager') && $user->office_id === $officeId) {
            return true;
        }

        return false;
    }
}
