<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Models;

use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdvisorReview extends Model
{
    use HasUuids;

    protected $table = 'advisor_reviews';

    protected $fillable = [
        'id',
        'advisor_id',
        'citizen_id',
        'session_id',
        'rating_accuracy',
        'rating_eloquence',
        'rating_patience',
        'overall_rating',
        'comment',
    ];

    protected $casts = [
        'rating_accuracy' => 'float',
        'rating_eloquence' => 'float',
        'rating_patience' => 'float',
        'overall_rating' => 'float',
    ];

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class, 'advisor_id');
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    /**
     * Derive overall rating from the 3 dimensions: accuracy, eloquence, patience.
     */
    public static function deriveOverallRating(float $accuracy, float $eloquence, float $patience): float
    {
        return round(($accuracy + $eloquence + $patience) / 3, 2);
    }
}
