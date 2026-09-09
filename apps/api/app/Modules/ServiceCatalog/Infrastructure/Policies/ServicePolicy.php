<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Infrastructure\Policies;

use App\Modules\Identity\Domain\Enums\SystemPermission;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use Illuminate\Contracts\Auth\Authenticatable;

final class ServicePolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, Service $service): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->canManage($user);
    }

    public function update(?Authenticatable $user, Service $service): bool
    {
        return $this->canManage($user);
    }

    public function delete(?Authenticatable $user, Service $service): bool
    {
        return $this->canManage($user);
    }

    private function canManage(?Authenticatable $user): bool
    {
        if (! $user instanceof Operator) {
            return false;
        }

        return $this->isSystemAdmin($user) || $user->hasPermissionTo(SystemPermission::SERVICE_CATALOG_MANAGE->value);
    }
}
