<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\Payments\Domain\Models\Payout;
use Illuminate\Contracts\Auth\Authenticatable;

final class PayoutPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        return $user instanceof Operator && $user->role === 'manager';
    }

    public function view(?Authenticatable $user, Payout $payout): bool
    {
        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user instanceof Operator && $user->role === 'manager') {
            return $payout->office_id === $user->office_id;
        }

        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, Payout $payout): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, Payout $payout): bool
    {
        return false;
    }
}
