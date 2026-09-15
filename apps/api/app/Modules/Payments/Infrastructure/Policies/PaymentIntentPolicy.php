<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\Payments\Domain\Models\PaymentIntent;
use Illuminate\Contracts\Auth\Authenticatable;

final class PaymentIntentPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function view(?Authenticatable $user, PaymentIntent $intent): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user instanceof Citizen) {
            return $intent->citizen_id === $user->id;
        }

        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, PaymentIntent $intent): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, PaymentIntent $intent): bool
    {
        return false;
    }
}
