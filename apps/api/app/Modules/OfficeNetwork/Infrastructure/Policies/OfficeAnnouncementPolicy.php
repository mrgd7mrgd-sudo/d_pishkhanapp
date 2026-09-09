<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\OfficeNetwork\Domain\Models\OfficeAnnouncement;
use Illuminate\Contracts\Auth\Authenticatable;

final class OfficeAnnouncementPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, OfficeAnnouncement $announcement): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->canManage($user);
    }

    public function update(?Authenticatable $user, OfficeAnnouncement $announcement): bool
    {
        return $this->canManage($user, $announcement->office_id);
    }

    public function delete(?Authenticatable $user, OfficeAnnouncement $announcement): bool
    {
        return $this->canManage($user, $announcement->office_id);
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
