<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Models;

use App\Modules\Consultation\Domain\Enums\SubscriptionStatus;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Subscription extends Model
{
    use HasUuids;

    protected $table = 'subscriptions';

    protected $fillable = [
        'id',
        'plan_id',
        'citizen_id',
        'status',
        'started_on',
        'expires_on',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'started_on' => 'date',
        'expires_on' => 'date',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function quotaUsages(): HasMany
    {
        return $this->hasMany(QuotaUsage::class, 'subscription_id');
    }
}
