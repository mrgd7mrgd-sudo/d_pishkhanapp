<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Infrastructure\Policies;

use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class AdvisorPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, Advisor $advisor): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, Advisor $advisor): bool
    {
        return $this->isSystemAdmin($user) || ($user instanceof Citizen && $advisor->citizen_id === $user->id);
    }

    public function delete(?Authenticatable $user, Advisor $advisor): bool
    {
        return $this->isSystemAdmin($user);
    }
}
