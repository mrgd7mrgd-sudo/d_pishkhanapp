<?php

declare(strict_types=1);

namespace App\Modules\Documents\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * VaultDocumentAttribute Domain Model (Architecture §6.1, §6.2, line 1083).
 * Key-value metadata attributes mapping prototype DocumentItem.attributes.
 *
 * @property string $id
 * @property string $vault_document_id
 * @property string $label
 * @property string $value
 * @property int $display_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read VaultDocument|null $document
 */
final class VaultDocumentAttribute extends Model
{
    use HasUuids;

    protected $table = 'vault_document_attributes';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'vault_document_id',
        'label',
        'value',
        'display_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
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
