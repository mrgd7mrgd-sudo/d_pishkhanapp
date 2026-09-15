<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\Payments\Domain\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Payout Domain Model for Office Daily Settlements (Architecture §6.1, §8.2, TASK-084, TASK-090).
 *
 * @property string $id
 * @property string $office_id
 * @property int $amount_rials
 * @property PayoutStatus $status
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property int $total_cases_count
 * @property string|null $reference_number
 * @property string|null $ledger_transaction_id
 * @property Carbon $generated_at
 * @property Carbon|null $processed_at
 * @property string|null $failure_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Office|null $office
 * @property-read LedgerTransaction|null $ledgerTransaction
 */
final class Payout extends Model
{
    use HasUuids;

    protected $table = 'payouts';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'office_id',
        'amount_rials',
        'status',
        'period_start',
        'period_end',
        'total_cases_count',
        'reference_number',
        'ledger_transaction_id',
        'generated_at',
        'processed_at',
        'failure_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_rials' => 'integer',
            'status' => PayoutStatus::class,
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'total_cases_count' => 'integer',
            'generated_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    /**
     * @return BelongsTo<LedgerTransaction, $this>
     */
    public function ledgerTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'ledger_transaction_id');
    }
}
