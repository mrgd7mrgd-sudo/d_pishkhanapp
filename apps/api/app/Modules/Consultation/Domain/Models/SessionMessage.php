<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Models;

use App\Modules\Consultation\Domain\Enums\SessionMessageSenderType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SessionMessage extends Model
{
    use HasUuids;

    protected $table = 'session_messages';

    protected $fillable = [
        'id',
        'session_id',
        'sender_type',
        'sender_id',
        'body',
        'attachment_key',
        'read_at',
    ];

    protected $casts = [
        'sender_type' => SessionMessageSenderType::class,
        'read_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConsultationSession::class, 'session_id');
    }
}
