<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * OfficeReview Domain Model (§6.1, §7.3, TASK-101).
 *
 * Invariants:
 * - Only 1 review per completed case (idx_office_reviews_case)
 * - Office rating and review_count are derived from verified reviews
 *
 * @property string $id
 * @property string $office_id
 * @property string $citizen_id
 * @property string $case_id
 * @property int $rating
 * @property string $comment
 * @property array<string>|null $tags
 * @property int $likes
 * @property bool $is_verified
 * @property string|null $manager_reply
 * @property Carbon|null $manager_replied_at
 * @property string|null $manager_operator_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Office $office
 * @property-read Citizen $citizen
 * @property-read CaseRequest $caseRequest
 * @property-read Operator|null $managerOperator
 */
final class OfficeReview extends Model
{
    use HasUuids;

    protected $table = 'office_reviews';

    protected $fillable = [
        'office_id',
        'citizen_id',
        'case_id',
        'rating',
        'comment',
        'tags',
        'likes',
        'is_verified',
        'manager_reply',
        'manager_replied_at',
        'manager_operator_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'tags' => 'array',
            'likes' => 'integer',
            'is_verified' => 'boolean',
            'manager_replied_at' => 'datetime',
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
     * @return BelongsTo<Citizen, $this>
     */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    /**
     * @return BelongsTo<CaseRequest, $this>
     */
    public function caseRequest(): BelongsTo
    {
        return $this->belongsTo(CaseRequest::class, 'case_id');
    }

    /**
     * @return BelongsTo<Operator, $this>
     */
    public function managerOperator(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'manager_operator_id');
    }

    public function hasReply(): bool
    {
        return $this->manager_reply !== null && $this->manager_reply !== '';
    }
}
