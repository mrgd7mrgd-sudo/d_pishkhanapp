<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * OfficeSlaEvent Domain Model (§5.9, §9.6, TASK-102).
 *
 * Records SLA breaches and performance metrics per office:
 * - late_return (delayed document return)
 * - offer_declined (dispatch offer declined)
 * - dispatch_expired (dispatch offer expired unresponded)
 * - review_delayed (expert review past SLA deadline)
 *
 * @property string $id
 * @property string $office_id
 * @property string|null $case_id
 * @property string $event_type
 * @property int|null $duration_minutes
 * @property int $penalty_points
 * @property bool $is_breach
 * @property Carbon $occurred_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Office $office
 * @property-read CaseRequest|null $caseRequest
 */
final class OfficeSlaEvent extends Model
{
    use HasUuids;

    protected $table = 'office_sla_events';

    protected $fillable = [
        'office_id',
        'case_id',
        'event_type',
        'duration_minutes',
        'penalty_points',
        'is_breach',
        'occurred_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'penalty_points' => 'integer',
            'is_breach' => 'boolean',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
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
     * @return BelongsTo<CaseRequest, $this>
     */
    public function caseRequest(): BelongsTo
    {
        return $this->belongsTo(CaseRequest::class, 'case_id');
    }
}
