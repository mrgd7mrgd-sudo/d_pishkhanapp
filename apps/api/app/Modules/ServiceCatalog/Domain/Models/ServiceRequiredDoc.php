<?php

declare(strict_types=1);

namespace App\Modules\ServiceCatalog\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Service Required Document Domain Model (§6.1, §6.2, TASK-036).
 *
 * @property string $id
 * @property string $service_id
 * @property string $document_type_code
 * @property bool $is_mandatory
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Service $service
 * @property-read DocumentType $documentType
 */
final class ServiceRequiredDoc extends Model
{
    use HasUuids;

    protected $table = 'service_required_docs';

    protected $fillable = [
        'service_id',
        'document_type_code',
        'is_mandatory',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_code', 'code');
    }
}
