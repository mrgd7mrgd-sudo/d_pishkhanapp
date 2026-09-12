<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\Payments\Domain\Models\LedgerEntry;
use Illuminate\Contracts\Auth\Authenticatable;

final class LedgerEntryPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $user instanceof Operator;
    }

    public function view(?Authenticatable $user, LedgerEntry $entry): bool
    {
        return $user !== null;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, LedgerEntry $entry): bool
    {
        return false; // Ledger entries are strictly immutable
    }

    public function delete(?Authenticatable $user, LedgerEntry $entry): bool
    {
        return false; // Ledger entries are strictly immutable
    }
}
