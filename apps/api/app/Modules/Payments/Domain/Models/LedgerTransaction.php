<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Double-entry balanced transaction header (Architecture §6.1, §6.2, §8.2).
 *
 * @property string $id
 * @property string $reference
 * @property LedgerTransactionType $type
 * @property string|null $case_id
 * @property string|null $payment_intent_id
 * @property string|null $description
 */
final class LedgerTransaction extends Model
{
    use HasUuids;

    protected $table = 'ledger_transactions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'reference',
        'type',
        'case_id',
        'payment_intent_id',
        'description',
        'posted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerTransactionType::class,
            'posted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'transaction_id');
    }

    /**
     * @return BelongsTo<CaseRequest, $this>
     */
    public function caseRequest(): BelongsTo
    {
        return $this->belongsTo(CaseRequest::class, 'case_id');
    }
}
