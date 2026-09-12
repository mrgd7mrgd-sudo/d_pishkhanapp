<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * CaseTimelineStep Domain Model (Architecture §6.1, §6.2, D — §6.2, TASK-050).
 * Independent table for case history, steps, SLA times, and actor audits.
 *
 * @property string $id
 * @property string $case_id
 * @property int $sequence
 * @property string $title
 * @property string|null $description
 * @property TimelineStepStatus $status
 * @property TurnOwner $turn_owner
 * @property string $turn_owner_label
 * @property TimelineActorType $actor_type
 * @property string|null $actor_id
 * @property string|null $office_note
 * @property int|null $duration_actual_minutes
 * @property int|null $duration_typical_minutes
 * @property Carbon $occurred_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CaseRequest|null $case
 */
final class CaseTimelineStep extends Model
{
    use HasUuids;

    protected $table = 'case_timeline_steps';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'case_id',
        'sequence',
        'title',
        'description',
        'status',
        'turn_owner',
        'turn_owner_label',
        'actor_type',
        'actor_id',
        'office_note',
        'duration_actual_minutes',
        'duration_typical_minutes',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'status' => TimelineStepStatus::class,
            'turn_owner' => TurnOwner::class,
            'actor_type' => TimelineActorType::class,
            'duration_actual_minutes' => 'integer',
            'duration_typical_minutes' => 'integer',
            'occurred_at' => 'datetime',
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
}
