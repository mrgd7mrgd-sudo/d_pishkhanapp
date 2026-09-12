<?php

declare(strict_types=1);

namespace App\Modules\Payments\Infrastructure\Policies;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Identity\Infrastructure\Policies\BasePolicy;
use App\Modules\Payments\Domain\Models\LedgerTransaction;
use Illuminate\Contracts\Auth\Authenticatable;

final class LedgerTransactionPolicy extends BasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user instanceof Citizen || $user instanceof Operator;
    }

    public function view(?Authenticatable $user, LedgerTransaction $transaction): bool
    {
        return $user !== null;
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->isSystemAdmin($user);
    }

    public function update(?Authenticatable $user, LedgerTransaction $transaction): bool
    {
        return false; // Transactions are immutable
    }

    public function delete(?Authenticatable $user, LedgerTransaction $transaction): bool
    {
        return false; // Transactions must never be deleted
    }
}
