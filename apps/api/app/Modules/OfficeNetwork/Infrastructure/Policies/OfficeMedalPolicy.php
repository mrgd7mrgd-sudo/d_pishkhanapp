<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Infrastructure\Policies;

use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\OfficeNetwork\Domain\Models\OfficeMedal;
use Illuminate\Contracts\Auth\Authenticatable;

final class OfficeMedalPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, OfficeMedal $medal): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, OfficeMedal $medal): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, OfficeMedal $medal): bool
    {
        return $this->isSystemAdmin($user);
    }
}
