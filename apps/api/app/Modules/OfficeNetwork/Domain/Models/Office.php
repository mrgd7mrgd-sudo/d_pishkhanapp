<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Models;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Office Domain Model (Architecture §6.1, §6.4, TASK-037).
 *
 * NOTE: distance_km and coords.mapX/mapY MUST NOT be stored (§6.2, §6.4).
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $manager_name
 * @property OfficeMembershipStatus $membership_status
 * @property bool $is_online
 * @property float $rating
 * @property int $review_count
 * @property string|null $address
 * @property string|null $city_id
 * @property string|null $province_code
 * @property string|null $location
 * @property string|null $phone
 * @property array<string, mixed>|null $working_hours
 * @property int $active_counters
 * @property int $current_waiting_queue
 * @property float $sla_score
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Operator> $operators
 * @property-read Collection<int, OfficeServiceCoverage> $serviceCoverages
 * @property-read Collection<int, OfficeSpecialty> $specialties
 * @property-read Collection<int, OfficeMedal> $medals
 * @property-read Collection<int, OfficeAnnouncement> $announcements
 * @property-read Collection<int, OfficeReview> $reviews
 */
final class Office extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'offices';

    protected $fillable = [
        'code',
        'name',
        'manager_name',
        'membership_status',
        'is_online',
        'rating',
        'review_count',
        'address',
        'city_id',
        'province_code',
        'location',
        'phone',
        'working_hours',
        'active_counters',
        'current_waiting_queue',
        'sla_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'membership_status' => OfficeMembershipStatus::class,
            'is_online' => 'boolean',
            'rating' => 'float',
            'review_count' => 'integer',
            'working_hours' => 'array',
            'active_counters' => 'integer',
            'current_waiting_queue' => 'integer',
            'sla_score' => 'float',
        ];
    }

    /**
     * @return HasMany<Operator, $this>
     */
    public function operators(): HasMany
    {
        return $this->hasMany(Operator::class, 'office_id');
    }

    /**
     * @return HasMany<OfficeServiceCoverage, $this>
     */
    public function serviceCoverages(): HasMany
    {
        return $this->hasMany(OfficeServiceCoverage::class, 'office_id');
    }

    /**
     * @return HasMany<OfficeSpecialty, $this>
     */
    public function specialties(): HasMany
    {
        return $this->hasMany(OfficeSpecialty::class, 'office_id');
    }

    /**
     * @return HasMany<OfficeMedal, $this>
     */
    public function medals(): HasMany
    {
        return $this->hasMany(OfficeMedal::class, 'office_id');
    }

    /**
     * @return HasMany<OfficeAnnouncement, $this>
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(OfficeAnnouncement::class, 'office_id');
    }

    /**
     * @return HasMany<OfficeReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(OfficeReview::class, 'office_id');
    }

    /**
     * @return HasMany<OfficeSlaEvent, $this>
     */
    public function slaEvents(): HasMany
    {
        return $this->hasMany(OfficeSlaEvent::class, 'office_id');
    }
}
