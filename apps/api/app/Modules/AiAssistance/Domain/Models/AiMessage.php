<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Domain\Models;

use App\Modules\AiAssistance\Domain\Enums\AiMessageRole;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AiMessage Domain Model (§6.1, §8.1)
 *
 * @property string $id
 * @property string $conversation_id
 * @property AiMessageRole $role
 * @property string $content
 * @property array<mixed>|null $citations
 * @property array<mixed>|null $suggested_actions
 * @property CarbonInterface $created_at
 */
final class AiMessage extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'ai_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'citations',
        'suggested_actions',
        'created_at',
    ];

    protected $casts = [
        'role' => AiMessageRole::class,
        'citations' => 'array',
        'suggested_actions' => 'array',
        'created_at' => 'immutable_datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
