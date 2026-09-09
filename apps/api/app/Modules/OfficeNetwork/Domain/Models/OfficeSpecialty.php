<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Office Specialty Domain Model (Architecture §6.1, §6.4, TASK-037).
 *
 * @property string $id
 * @property string $office_id
 * @property string $title
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Office $office
 */
final class OfficeSpecialty extends Model
{
    use HasUuids;

    protected $table = 'office_specialties';

    protected $fillable = [
        'office_id',
        'title',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
