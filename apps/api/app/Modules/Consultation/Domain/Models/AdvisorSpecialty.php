<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdvisorSpecialty extends Model
{
    use HasUuids;

    protected $table = 'advisor_specialties';

    protected $fillable = [
        'id',
        'advisor_id',
        'specialty_name',
    ];

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class, 'advisor_id');
    }
}
