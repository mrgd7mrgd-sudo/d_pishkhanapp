<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Domain\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AiUsageRecord Domain Model (§6.1, §8.1)
 *
 * @property string $id
 * @property string $conversation_id
 * @property string $model
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $cost_rials
 * @property CarbonInterface $created_at
 */
final class AiUsageRecord extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'ai_usage_records';

    protected $fillable = [
        'conversation_id',
        'model',
        'input_tokens',
        'output_tokens',
        'cost_rials',
        'created_at',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost_rials' => 'integer',
        'created_at' => 'immutable_datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
