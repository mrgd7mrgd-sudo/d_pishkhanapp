<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Domain\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Service Domain Model (§6.1, §6.2, D-06, TASK-036).
 *
 * @property string $id
 * @property string $category_id
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property list<string> $tags
 * @property list<string>|array<string, mixed>|null $requirements
 * @property int $estimated_days_min
 * @property int $estimated_days_max
 * @property int $fee_rials
 * @property float $office_share_percent
 * @property string|null $department
 * @property bool $is_popular
 * @property bool $is_new
 * @property bool $is_active
 * @property string|null $search_vector
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ServiceCategory $category
 * @property-read Collection<int, ServiceRequiredDoc> $requiredDocs
 * @property-read Collection<int, DocumentType> $documentTypes
 */
final class Service extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'services';

    protected $fillable = [
        'category_id',
        'slug',
        'title',
        'description',
        'tags',
        'requirements',
        'estimated_days_min',
        'estimated_days_max',
        'fee_rials',
        'office_share_percent',
        'department',
        'is_popular',
        'is_new',
        'is_active',
        'search_vector',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'requirements' => 'array',
            'estimated_days_min' => 'integer',
            'estimated_days_max' => 'integer',
            'fee_rials' => 'integer',
            'office_share_percent' => 'float',
            'is_popular' => 'boolean',
            'is_new' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'id');
    }

    /**
     * @return HasMany<ServiceRequiredDoc, $this>
     */
    public function requiredDocs(): HasMany
    {
        return $this->hasMany(ServiceRequiredDoc::class, 'service_id', 'id');
    }

    /**
     * @return BelongsToMany<DocumentType, $this>
     */
    public function documentTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            DocumentType::class,
            'service_required_docs',
            'service_id',
            'document_type_code',
            'id',
            'code'
        )->withPivot(['id', 'is_mandatory', 'sort_order'])->withTimestamps();
    }
}
