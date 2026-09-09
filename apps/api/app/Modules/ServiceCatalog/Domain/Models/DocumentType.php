<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Domain\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Document Type Domain Model (§6.1, §6.2, TASK-036).
 *
 * @property string $code
 * @property string $title
 * @property string|null $description
 * @property list<string> $accepted_mimes
 * @property bool $requires_original
 * @property int|null $validity_months
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ServiceRequiredDoc> $requiredDocs
 * @property-read Collection<int, Service> $services
 */
final class DocumentType extends Model
{
    protected $table = 'document_types';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'title',
        'description',
        'accepted_mimes',
        'requires_original',
        'validity_months',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_mimes' => 'array',
            'requires_original' => 'boolean',
            'validity_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ServiceRequiredDoc, $this>
     */
    public function requiredDocs(): HasMany
    {
        return $this->hasMany(ServiceRequiredDoc::class, 'document_type_code', 'code');
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'service_required_docs',
            'document_type_code',
            'service_id',
            'code',
            'id'
        )->withPivot(['id', 'is_mandatory', 'sort_order'])->withTimestamps();
    }
}
