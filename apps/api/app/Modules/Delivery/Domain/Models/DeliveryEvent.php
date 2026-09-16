<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * DeliveryEvent Domain Model (Architecture §6.1, TASK-094).
 *
 * @property string $id
 * @property string $delivery_request_id
 * @property string $event
 * @property string|null $location
 * @property string|null $note
 * @property Carbon $occurred_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read DeliveryRequest|null $deliveryRequest
 */
final class DeliveryEvent extends Model
{
    use HasUuids;

    protected $table = 'delivery_events';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'delivery_request_id',
        'event',
        'location',
        'note',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DeliveryRequest, $this>
     */
    public function deliveryRequest(): BelongsTo
    {
        return $this->belongsTo(DeliveryRequest::class, 'delivery_request_id');
    }
}
