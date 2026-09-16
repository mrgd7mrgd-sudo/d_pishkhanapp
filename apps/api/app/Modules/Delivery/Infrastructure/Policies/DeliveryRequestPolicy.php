<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Infrastructure\Policies;

use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class DeliveryRequestPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function view(?Authenticatable $user, DeliveryRequest $delivery): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user instanceof Operator) {
            return $delivery->office_id === $user->office_id;
        }

        if ($user instanceof Citizen) {
            return $delivery->case?->citizen_id === $user->id;
        }

        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Operator || $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, DeliveryRequest $delivery): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user instanceof Operator) {
            return $delivery->office_id === $user->office_id;
        }

        return false;
    }

    public function delete(?Authenticatable $user, DeliveryRequest $delivery): bool
    {
        return false;
    }
}
