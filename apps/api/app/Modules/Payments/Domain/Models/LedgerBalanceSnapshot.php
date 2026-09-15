<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ledger Balance Snapshot Model for materialized ledger balance checkpoints (Architecture §6.7, §9.2, TASK-091).
 *
 * @property string $id
 * @property string $account_id
 * @property int $balance_rials
 * @property string|null $as_of_transaction_id
 * @property Carbon $snapshot_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read LedgerAccount|null $account
 * @property-read LedgerTransaction|null $asOfTransaction
 */
final class LedgerBalanceSnapshot extends Model
{
    use HasUuids;

    protected $table = 'ledger_balance_snapshots';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'balance_rials',
        'as_of_transaction_id',
        'snapshot_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance_rials' => 'integer',
            'snapshot_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<LedgerAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }

    /**
     * @return BelongsTo<LedgerTransaction, $this>
     */
    public function asOfTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'as_of_transaction_id');
    }
}
