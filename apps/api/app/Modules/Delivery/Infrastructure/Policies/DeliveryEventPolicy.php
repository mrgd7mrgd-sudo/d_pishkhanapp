<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Infrastructure\Policies;

use App\Modules\Delivery\Domain\Models\DeliveryEvent;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

final class DeliveryEventPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function view(?Authenticatable $user, DeliveryEvent $event): bool
    {
        return $user !== null;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user instanceof Operator || $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, DeliveryEvent $event): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, DeliveryEvent $event): bool
    {
        return false;
    }
}
