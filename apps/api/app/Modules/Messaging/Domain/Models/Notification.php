<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Domain\Models;

use App\Modules\Identity\Domain\Models\Citizen;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notification Model (Architecture §6.1, §6.4, TASK-072).
 *
 * @property string $id
 * @property string $citizen_id
 * @property string $type
 * @property string $title
 * @property string $body
 * @property array<string, mixed>|null $payload
 * @property CarbonInterface|null $read_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Citizen|null $citizen
 */
final class Notification extends Model
{
    use HasUuids;

    protected $table = 'notifications';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'citizen_id',
        'type',
        'title',
        'body',
        'payload',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'read_at' => 'datetime',
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

    /**
     * Scope query to unread notifications.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
