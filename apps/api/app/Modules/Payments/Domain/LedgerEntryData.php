<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain;

use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Models\LedgerAccount;

final class LedgerEntryData
{
    public readonly string $accountId;

    public function __construct(
        string|LedgerAccount $account,
        public readonly LedgerDirection $direction,
        public readonly int $amountRials,
    ) {
        $this->accountId = $account instanceof LedgerAccount ? $account->id : $account;
    }
}
