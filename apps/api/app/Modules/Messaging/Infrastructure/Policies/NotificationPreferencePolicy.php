<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\Messaging\Domain\Models\NotificationPreference;
use Illuminate\Contracts\Auth\Authenticatable;

final class NotificationPreferencePolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $this->isSystemAdmin($user);
    }

    public function view(?Authenticatable $user, NotificationPreference $preference): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Citizen && $user->id === $preference->citizen_id;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, NotificationPreference $preference): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Citizen && $user->id === $preference->citizen_id;
    }

    public function delete(?Authenticatable $user, NotificationPreference $preference): bool
    {
        return $this->isSystemAdmin($user);
    }
}
