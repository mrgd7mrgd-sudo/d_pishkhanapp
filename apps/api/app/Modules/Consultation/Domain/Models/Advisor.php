<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Models;

use App\Modules\Consultation\Domain\Enums\AdvisorApplicationStatus;
use App\Modules\Consultation\Domain\Enums\ConsultationCategory;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Advisor extends Model
{
    use HasUuids;

    protected $table = 'advisors';

    protected $fillable = [
        'id',
        'citizen_id',
        'display_name',
        'avatar_key',
        'title',
        'category',
        'credentials_badge',
        'license_number',
        'experience_years',
        'rating',
        'review_count',
        'rating_accuracy',
        'rating_eloquence',
        'rating_patience',
        'is_online',
        'is_verified',
        'bio',
        'consultation_count',
        'price_text_chat_rials',
        'price_phone_per_minute_rials',
        'price_deep_review_rials',
        'application_status',
        'rejection_reason',
    ];

    protected $casts = [
        'category' => ConsultationCategory::class,
        'application_status' => AdvisorApplicationStatus::class,
        'experience_years' => 'integer',
        'rating' => 'float',
        'review_count' => 'integer',
        'rating_accuracy' => 'float',
        'rating_eloquence' => 'float',
        'rating_patience' => 'float',
        'is_online' => 'boolean',
        'is_verified' => 'boolean',
        'consultation_count' => 'integer',
        'price_text_chat_rials' => 'integer',
        'price_phone_per_minute_rials' => 'integer',
        'price_deep_review_rials' => 'integer',
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function specialties(): HasMany
    {
        return $this->hasMany(AdvisorSpecialty::class, 'advisor_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AdvisorReview::class, 'advisor_id');
    }

    /**
     * Recalculate and update the 3D ratings and overall rating from existing reviews.
     */
    public function recalculateRatings(): void
    {
        $avgAccuracy = (float) ($this->reviews()->avg('rating_accuracy') ?? 0.0);
        $avgEloquence = (float) ($this->reviews()->avg('rating_eloquence') ?? 0.0);
        $avgPatience = (float) ($this->reviews()->avg('rating_patience') ?? 0.0);
        $count = $this->reviews()->count();

        $overall = $count > 0
            ? round(($avgAccuracy + $avgEloquence + $avgPatience) / 3, 2)
            : 0.00;

        $this->update([
            'rating_accuracy' => round($avgAccuracy, 2),
            'rating_eloquence' => round($avgEloquence, 2),
            'rating_patience' => round($avgPatience, 2),
            'rating' => $overall,
            'review_count' => $count,
        ]);
    }
}
