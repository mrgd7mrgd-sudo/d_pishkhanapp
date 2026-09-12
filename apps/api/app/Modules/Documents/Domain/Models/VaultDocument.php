<?php

declare(strict_types=1);

namespace App\Modules\Documents\Domain\Models;

use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * VaultDocument Domain Model (Architecture §6.1, §6.2, line 1083).
 * Citizen's personal document vault with versioning, attributes, and soft delete.
 *
 * @property string $id
 * @property string $citizen_id
 * @property string $category
 * @property string|null $document_type_code
 * @property string $title
 * @property string|null $doc_number
 * @property Carbon|null $issue_date
 * @property Carbon|null $expiry_date
 * @property bool $is_verified
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Citizen|null $citizen
 * @property-read Collection<int, VaultDocumentVersion> $versions
 * @property-read VaultDocumentVersion|null $latestVersion
 * @property-read Collection<int, VaultDocumentAttribute> $attributes
 */
final class VaultDocument extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'vault_documents';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'citizen_id',
        'category',
        'document_type_code',
        'title',
        'doc_number',
        'issue_date',
        'expiry_date',
        'is_verified',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
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
     * @return HasMany<VaultDocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(VaultDocumentVersion::class, 'vault_document_id')->orderByDesc('version');
    }

    /**
     * @return HasOne<VaultDocumentVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(VaultDocumentVersion::class, 'vault_document_id')->latestOfMany('version');
    }

    /**
     * @return HasMany<VaultDocumentAttribute, $this>
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(VaultDocumentAttribute::class, 'vault_document_id')->orderBy('display_order');
    }
}
