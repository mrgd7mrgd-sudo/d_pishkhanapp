<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Infrastructure\Policies;

use App\Modules\Consultation\Domain\Models\SubscriptionPlan;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class SubscriptionPlanPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, SubscriptionPlan $plan): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, SubscriptionPlan $plan): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, SubscriptionPlan $plan): bool
    {
        return $this->isSystemAdmin($user);
    }
}
