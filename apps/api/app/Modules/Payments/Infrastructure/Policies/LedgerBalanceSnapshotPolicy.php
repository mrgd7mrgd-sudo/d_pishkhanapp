<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Policies;

use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\Payments\Domain\Models\LedgerBalanceSnapshot;
use Illuminate\Contracts\Auth\Authenticatable;

final class LedgerBalanceSnapshotPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function view(?Authenticatable $user, LedgerBalanceSnapshot $snapshot): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, LedgerBalanceSnapshot $snapshot): bool
    {
        return false; // Snapshots are immutable
    }

    public function delete(?Authenticatable $user, LedgerBalanceSnapshot $snapshot): bool
    {
        return false; // Snapshots are immutable
    }
}
