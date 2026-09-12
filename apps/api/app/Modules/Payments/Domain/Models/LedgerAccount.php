<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Shared\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * Double-entry ledger account (Architecture §6.1, §6.2, §8.2, TASK-054).
 * Crucial: NO writable balance column exists. Balance is always dynamically derived.
 *
 * @property string $id
 * @property LedgerOwnerType $owner_type
 * @property string|null $owner_id
 * @property LedgerAccountKind $kind
 * @property string $currency
 */
final class LedgerAccount extends Model
{
    use HasUuids;

    protected $table = 'ledger_accounts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'owner_type',
        'owner_id',
        'kind',
        'currency',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'owner_type' => LedgerOwnerType::class,
            'kind' => LedgerAccountKind::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'account_id');
    }

    /**
     * Calculate net balance in Rials derived dynamically via SUM(credit) - SUM(debit) (§6.2).
     */
    public function getBalanceRials(): int
    {
        $credits = (int) $this->entries()
            ->where('direction', LedgerDirection::CREDIT->value)
            ->sum('amount_rials');

        $debits = (int) $this->entries()
            ->where('direction', LedgerDirection::DEBIT->value)
            ->sum('amount_rials');

        return $credits - $debits;
    }

    /**
     * Get balance as type-safe Money value object.
     *
     * @throws InvalidArgumentException if balance is negative
     */
    public function getBalance(): Money
    {
        return Money::fromRials($this->getBalanceRials());
    }
}
