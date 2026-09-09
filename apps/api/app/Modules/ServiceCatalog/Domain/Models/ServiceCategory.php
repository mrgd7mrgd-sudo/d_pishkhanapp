<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Domain\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Service Category Domain Model (§6.1, §6.2, TASK-036).
 *
 * NOTE: service_count is NEVER stored in database (§6.2). It is derived via query or relation.
 *
 * @property string $id
 * @property string $title
 * @property string|null $short_title
 * @property string|null $icon_name
 * @property string|null $color
 * @property string|null $badge
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Service> $services
 * @property-read int|null $services_count
 */
final class ServiceCategory extends Model
{
    use SoftDeletes;

    protected $table = 'service_categories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'title',
        'short_title',
        'icon_name',
        'color',
        'badge',
        'description',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id', 'id');
    }
}
