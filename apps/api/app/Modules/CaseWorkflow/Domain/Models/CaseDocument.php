<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Enums\CaseDocumentStatus;
use App\Modules\ServiceCatalog\Domain\Models\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * CaseDocument Domain Model (Architecture §6.1, §6.2, TASK-050).
 * Append-only versioned documents with envelope encryption and quality analysis warnings.
 *
 * @property string $id
 * @property string $case_id
 * @property string $document_type_code
 * @property int $version
 * @property CaseDocumentStatus $status
 * @property string $storage_key
 * @property string $encrypted_data_key
 * @property string $content_sha256
 * @property int $size_bytes
 * @property string $mime_type
 * @property list<string> $quality_warnings
 * @property string|null $reason_code
 * @property string|null $reviewed_by
 * @property Carbon $uploaded_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CaseRequest|null $case
 * @property-read DocumentType|null $documentType
 *
 * @method static Builder<static> latestVersion()
 * @method static Builder<static> verified()
 */
final class CaseDocument extends Model
{
    use HasUuids;

    protected $table = 'case_documents';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'case_id',
        'document_type_code',
        'version',
        'status',
        'storage_key',
        'encrypted_data_key',
        'content_sha256',
        'size_bytes',
        'mime_type',
        'quality_warnings',
        'reason_code',
        'reviewed_by',
        'uploaded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => CaseDocumentStatus::class,
            'size_bytes' => 'integer',
            'quality_warnings' => 'array',
            'uploaded_at' => 'datetime',
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
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_code', 'code');
    }

    /**
     * Scope query to verified documents.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', CaseDocumentStatus::VERIFIED->value);
    }
}
