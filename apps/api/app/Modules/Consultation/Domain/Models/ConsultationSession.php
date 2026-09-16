<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Models;

use App\Modules\Consultation\Domain\Enums\ConsultationMode;
use App\Modules\Consultation\Domain\Enums\ConsultationSessionStatus;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ConsultationSession extends Model
{
    use HasUuids;

    protected $table = 'consultation_sessions';

    protected $fillable = [
        'id',
        'advisor_id',
        'citizen_id',
        'mode',
        'status',
        'duration_seconds',
        'total_fee_rials',
        'tracking_code',
        'uploaded_docs_count',
        'advisor_verdict',
        'linked_service_id',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'mode' => ConsultationMode::class,
        'status' => ConsultationSessionStatus::class,
        'duration_seconds' => 'integer',
        'total_fee_rials' => 'integer',
        'uploaded_docs_count' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class, 'advisor_id');
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function linkedService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'linked_service_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SessionMessage::class, 'session_id');
    }
}
