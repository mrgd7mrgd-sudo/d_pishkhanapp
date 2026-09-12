<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Models;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * CaseRequest Domain Model (Architecture §6.1, §6.5, TASK-049).
 *
 * @property string $id
 * @property string $tracking_code
 * @property string $citizen_id
 * @property string $service_id
 * @property string|null $office_id
 * @property string $province_code
 * @property CaseStatus $status
 * @property TurnOwner $turn_owner
 * @property int $current_step
 * @property int $total_steps
 * @property int $fee_paid_rials
 * @property int $office_share_rials
 * @property int $platform_share_rials
 * @property mixed $citizen_location
 * @property string|null $delegation_id
 * @property DeliveryPreference $delivery_preference
 * @property Carbon|null $sla_deadline_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $closed_at
 * @property-read Citizen|null $citizen
 * @property-read Service|null $service
 * @property-read Office|null $office
 *
 * @method static Builder<static> byProvince(string $provinceCode)
 * @method static Builder<static> active()
 */
final class CaseRequest extends Model
{
    use HasUuids;

    protected $table = 'case_requests';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tracking_code',
        'citizen_id',
        'service_id',
        'office_id',
        'province_code',
        'status',
        'turn_owner',
        'current_step',
        'total_steps',
        'fee_paid_rials',
        'office_share_rials',
        'platform_share_rials',
        'citizen_location',
        'delegation_id',
        'delivery_preference',
        'sla_deadline_at',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CaseStatus::class,
            'turn_owner' => TurnOwner::class,
            'delivery_preference' => DeliveryPreference::class,
            'current_step' => 'integer',
            'total_steps' => 'integer',
            'fee_paid_rials' => 'integer',
            'office_share_rials' => 'integer',
            'platform_share_rials' => 'integer',
            'sla_deadline_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public static bool $allowDirectStatusAssignment = false;

    /**
     * Guard against direct mutation of case status outside CaseStateMachine (§5.4).
     */
    public function setStatusAttribute(mixed $value): void
    {
        if (! self::$allowDirectStatusAssignment && ! $this->isStateTransitionAllowed()) {
            throw new \LogicException('Direct mutation of CaseRequest::$status is prohibited. Use CaseStateMachine.');
        }

        $this->attributes['status'] = $value instanceof CaseStatus ? $value->value : $value;
    }

    private function isStateTransitionAllowed(): bool
    {
        if (! $this->exists) {
            return true;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($trace as $frame) {
            if (isset($frame['class']) && $frame['class'] === CaseStateMachine::class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return BelongsTo<Citizen, $this>
     */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    /**
     * @return HasMany<CaseTimelineStep, $this>
     */
    public function timelineSteps(): HasMany
    {
        return $this->hasMany(CaseTimelineStep::class, 'case_id')->orderBy('sequence');
    }

    /**
     * @return HasMany<CaseTimelineStep, $this>
     */
    public function timeline(): HasMany
    {
        return $this->timelineSteps();
    }

    /**
     * @return HasMany<CaseDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'case_id');
    }

    /**
     * @return HasMany<CaseReturn, $this>
     */
    public function returns(): HasMany
    {
        return $this->hasMany(CaseReturn::class, 'case_id');
    }

    /**
     * @return HasMany<GovInquiry, $this>
     */
    public function govInquiries(): HasMany
    {
        return $this->hasMany(GovInquiry::class, 'case_id');
    }

    /**
     * @return HasMany<DispatchOffer, $this>
     */
    public function dispatchOffers(): HasMany
    {
        return $this->hasMany(DispatchOffer::class, 'case_id');
    }

    /**
     * Get IDs of offices that declined or let offer expire for this case.
     *
     * @return list<string>
     */
    public function declinedOfficeIds(): array
    {
        return $this->dispatchOffers()
            ->whereIn('status', [
                DispatchOfferStatus::DECLINED->value,
                DispatchOfferStatus::EXPIRED->value,
            ])
            ->pluck('office_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Scope query to single province for Partition Pruning (§6.5).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeByProvince(Builder $query, string $provinceCode): Builder
    {
        return $query->where('province_code', strtoupper($provinceCode));
    }

    /**
     * Scope query to non-terminal active cases.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            CaseStatus::COMPLETED->value,
            CaseStatus::REJECTED->value,
            CaseStatus::CANCELLED->value,
        ]);
    }
}
