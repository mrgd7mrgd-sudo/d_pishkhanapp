<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Office Medal Domain Model (Architecture §6.1, §6.4, TASK-037).
 *
 * @property string $id
 * @property string $office_id
 * @property string $title
 * @property string|null $icon
 * @property Carbon|null $earned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Office $office
 */
final class OfficeMedal extends Model
{
    use HasUuids;

    protected $table = 'office_medals';

    protected $fillable = [
        'office_id',
        'title',
        'icon',
        'earned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
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
