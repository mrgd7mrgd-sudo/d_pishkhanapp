<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

final class QuotaUsage extends Model
{
    use HasUuids;

    protected $table = 'quota_usages';

    protected $fillable = [
        'id',
        'subscription_id',
        'quota_key',
        'used',
        'limit',
        'period_start',
    ];

    protected $casts = [
        'used' => 'integer',
        'limit' => 'integer',
        'period_start' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (QuotaUsage $quota): void {
            if ($quota->used < 0) {
                throw new InvalidArgumentException('Quota used amount cannot be negative (§5.3).');
            }
            if ($quota->limit < 0) {
                throw new InvalidArgumentException('Quota limit cannot be negative (§5.3).');
            }
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    /**
     * Consume quota units, enforcing Invariant §5.3: used must never exceed limit and must never be negative.
     */
    public function consume(int $amount = 1): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Quota consumption amount must be strictly positive.');
        }

        if ($this->used + $amount > $this->limit) {
            throw new InvalidArgumentException('Quota limit exceeded.');
        }

        $this->increment('used', $amount);
    }
}
