<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * CaseReturn Domain Model (Architecture §6.1, §6.3, TASK-051).
 * Records an action-required return event initiated by an operator.
 *
 * @property string $id
 * @property string $case_id
 * @property ReturnReasonCode $reason_code
 * @property string|null $target_document_type_code
 * @property string|null $operator_note
 * @property string|null $operator_id
 * @property Carbon|null $deadline_at
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CaseRequest|null $case
 * @property-read ReturnReason|null $reason
 * @property-read Operator|null $operator
 */
final class CaseReturn extends Model
{
    use HasUuids;

    protected $table = 'case_returns';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'case_id',
        'reason_code',
        'target_document_type_code',
        'operator_note',
        'operator_id',
        'deadline_at',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason_code' => ReturnReasonCode::class,
            'deadline_at' => 'datetime',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CaseRequest, $this>
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseRequest::class, 'case_id');
    }

    /**
     * @return BelongsTo<ReturnReason, $this>
     */
    public function reason(): BelongsTo
    {
        return $this->belongsTo(ReturnReason::class, 'reason_code', 'code');
    }

    /**
     * @return BelongsTo<Operator, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }
}
