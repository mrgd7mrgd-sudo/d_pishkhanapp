<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Infrastructure\Policies;

use App\Modules\Consultation\Domain\Models\Subscription;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class SubscriptionPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function view(?Authenticatable $user, Subscription $subscription): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Citizen && $subscription->citizen_id === $user->id;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen;
    }

    public function update(?Authenticatable $user, Subscription $subscription): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Citizen && $subscription->citizen_id === $user->id;
    }

    public function delete(?Authenticatable $user, Subscription $subscription): bool
    {
        return $this->isSystemAdmin($user);
    }
}
