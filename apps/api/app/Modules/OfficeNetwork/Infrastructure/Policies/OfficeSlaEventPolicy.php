<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Infrastructure\Policies;

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSlaEvent;
use Illuminate\Contracts\Auth\Authenticatable;

final class OfficeSlaEventPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        if (! $user instanceof Operator) {
            return false;
        }

        return $this->isSystemAdmin($user) || $this->isAuditor($user) || $user->hasRole('office_manager');
    }

    public function view(?Authenticatable $user, OfficeSlaEvent $event): bool
    {
        if (! $user instanceof Operator) {
            return false;
        }

        if ($this->isSystemAdmin($user) || $this->isAuditor($user)) {
            return true;
        }

        $isManager = $user->hasRole('office_manager') || $user->role === OperatorRole::MANAGER;

        return $isManager && $user->office_id === $event->office_id;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, OfficeSlaEvent $event): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, OfficeSlaEvent $event): bool
    {
        return $this->isSystemAdmin($user);
    }
}
