<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Domain\Models;

use App\Modules\AiAssistance\Domain\Enums\AiChannel;
use App\Modules\Identity\Domain\Models\Citizen;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AiConversation Domain Model (§6.1, §8.1)
 *
 * @property string $id
 * @property string $citizen_id
 * @property AiChannel $channel
 * @property string $title
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class AiConversation extends Model
{
    use HasUuids;

    protected $table = 'ai_conversations';

    protected $fillable = [
        'citizen_id',
        'channel',
        'title',
    ];

    protected $casts = [
        'channel' => AiChannel::class,
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->orderBy('created_at', 'asc');
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(AiUsageRecord::class, 'conversation_id');
    }
}
