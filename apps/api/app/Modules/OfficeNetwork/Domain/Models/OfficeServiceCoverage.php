<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Models;

use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Office Service Coverage Domain Model (Architecture §6.1, §6.4, TASK-037).
 *
 * @property string $id
 * @property string $office_id
 * @property string $category_id
 * @property string|null $service_id
 * @property bool $is_active
 * @property int $daily_capacity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Office $office
 * @property-read ServiceCategory $category
 * @property-read Service|null $service
 */
final class OfficeServiceCoverage extends Model
{
    use HasUuids;

    protected $table = 'office_service_coverage';

    protected $fillable = [
        'office_id',
        'category_id',
        'service_id',
        'is_active',
        'daily_capacity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'daily_capacity' => 'integer',
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
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
