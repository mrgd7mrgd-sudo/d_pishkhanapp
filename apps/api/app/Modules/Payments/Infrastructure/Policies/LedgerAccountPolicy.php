<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\Payments\Domain\Models\LedgerAccount;
use Illuminate\Contracts\Auth\Authenticatable;

final class LedgerAccountPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $user instanceof Operator;
    }

    public function view(?Authenticatable $user, LedgerAccount $account): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->isSystemAdmin($user)) {
            return true;
        }

        if ($user instanceof Citizen && $account->owner_type->value === 'citizen') {
            return $account->owner_id === $user->id;
        }

        if ($user instanceof Operator && $account->owner_type->value === 'office') {
            return $account->owner_id === $user->office_id;
        }

        return false;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, LedgerAccount $account): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function delete(?Authenticatable $user, LedgerAccount $account): bool
    {
        return false; // Ledger accounts must never be deleted
    }
}
