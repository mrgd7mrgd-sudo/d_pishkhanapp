<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            'updated_at' => 'datetime',
        ];
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
