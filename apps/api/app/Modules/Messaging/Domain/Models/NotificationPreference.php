<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Domain\Models;

use App\Modules\Identity\Domain\Models\Citizen;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notification Preference Model (Architecture §6.1, TASK-072).
 *
 * @property string $id
 * @property string $citizen_id
 * @property string $notification_type
 * @property bool $sms_enabled
 * @property bool $push_enabled
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Citizen|null $citizen
 */
final class NotificationPreference extends Model
{
    use HasUuids;

    protected $table = 'notification_preferences';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'citizen_id',
        'notification_type',
        'sms_enabled',
        'push_enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Citizen, $this>
     */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }
}
