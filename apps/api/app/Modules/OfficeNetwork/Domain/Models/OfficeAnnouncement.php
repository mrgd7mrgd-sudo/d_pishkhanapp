<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Office Announcement Domain Model (Architecture §6.1, §6.4, TASK-037).
 *
 * @property string $id
 * @property string $office_id
 * @property string $title
 * @property string $content
 * @property string $priority
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Office $office
 */
final class OfficeAnnouncement extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'office_announcements';

    protected $fillable = [
        'office_id',
        'title',
        'content',
        'priority',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }
}
