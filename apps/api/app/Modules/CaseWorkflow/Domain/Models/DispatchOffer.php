<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * DispatchOffer Domain Model (Architecture §6.1, §5.8, §6.4, TASK-066).
 * Snapp-style dispatch offer dispatched to candidate offices.
 *
 * @property string $id
 * @property string $case_id
 * @property string $office_id
 * @property int $round
 * @property DispatchOfferStatus $status
 * @property Carbon $expires_at
 * @property Carbon|null $responded_at
 * @property string|null $responded_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CaseRequest|null $case
 * @property-read Office|null $office
 * @property-read Operator|null $responder
 *
 * @method static Builder<static> pending()
 * @method static Builder<static> activeForOffice(string $officeId)
 */
final class DispatchOffer extends Model
{
    use HasUuids;

    protected $table = 'dispatch_offers';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'case_id',
        'office_id',
        'round',
        'status',
        'expires_at',
        'responded_at',
        'responded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'status' => DispatchOfferStatus::class,
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
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
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    /**
     * @return BelongsTo<Operator, $this>
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(Operator::class, 'responded_by');
    }

    /**
     * Scope to pending offers.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DispatchOfferStatus::PENDING->value);
    }

    /**
     * Scope to active unexpired pending offers for an office.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActiveForOffice(Builder $query, string $officeId): Builder
    {
        return $query->where('office_id', $officeId)
            ->where('status', DispatchOfferStatus::PENDING->value)
            ->where('expires_at', '>', Carbon::now());
    }
}
