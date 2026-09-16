<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SubscriptionPlan extends Model
{
    use HasUuids;

    protected $table = 'subscription_plans';

    protected $fillable = [
        'id',
        'plan_key',
        'title',
        'badge',
        'is_popular',
        'price_monthly_rials',
        'target_audience',
        'features',
        'quota',
        'is_active',
    ];

    protected $casts = [
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'price_monthly_rials' => 'integer',
        'features' => 'array',
        'quota' => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
