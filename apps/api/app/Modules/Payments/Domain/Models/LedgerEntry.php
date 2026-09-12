<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use App\Modules\Payments\Domain\Enums\LedgerDirection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Immutable double-entry line item (Architecture §6.1, §6.2, §8.2, TASK-054).
 *
 * @property string $id
 * @property string $transaction_id
 * @property string $account_id
 * @property LedgerDirection $direction
 * @property int $amount_rials
 */
final class LedgerEntry extends Model
{
    use HasUuids;

    protected $table = 'ledger_entries';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'transaction_id',
        'account_id',
        'direction',
        'amount_rials',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => LedgerDirection::class,
            'amount_rials' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(function (): void {
            throw new LogicException('Ledger entries are strictly immutable. UPDATE is prohibited.');
        });

        self::deleting(function (): void {
            throw new LogicException('Ledger entries are strictly immutable. DELETE is prohibited.');
        });
    }

    /**
     * @return BelongsTo<LedgerTransaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'transaction_id');
    }

    /**
     * @return BelongsTo<LedgerAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }
}
