<?php

declare(strict_types=1);

namespace App\Modules\Documents\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * VaultDocumentVersion Domain Model (Architecture §6.1, §6.2, line 1083).
 * Immutable document version stored with envelope encryption in MinIO.
 *
 * @property string $id
 * @property string $vault_document_id
 * @property int $version
 * @property string $storage_key
 * @property string $encrypted_data_key
 * @property string $content_sha256
 * @property int $size_bytes
 * @property string $mime_type
 * @property string|null $file_name
 * @property list<string> $quality_warnings
 * @property Carbon $created_at
 * @property-read VaultDocument|null $document
 */
final class VaultDocumentVersion extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'vault_document_versions';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'vault_document_id',
        'version',
        'storage_key',
        'encrypted_data_key',
        'content_sha256',
        'size_bytes',
        'mime_type',
        'file_name',
        'quality_warnings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'size_bytes' => 'integer',
            'quality_warnings' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VaultDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(VaultDocument::class, 'vault_document_id');
    }
}
