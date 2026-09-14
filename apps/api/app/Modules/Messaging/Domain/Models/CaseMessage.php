<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Messaging\Domain\Enums\MessageSenderType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Case Message Model (Architecture §6.1, §6.4, TASK-072).
 *
 * @property string $id
 * @property string $case_id
 * @property MessageSenderType $sender_type
 * @property string|null $sender_id
 * @property string $sender_name
 * @property string $body
 * @property string|null $attachment_key
 * @property CarbonInterface|null $read_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read CaseRequest|null $case
 */
final class CaseMessage extends Model
{
    use HasUuids;

    protected $table = 'case_messages';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'case_id',
        'sender_type',
        'sender_id',
        'sender_name',
        'body',
        'attachment_key',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sender_type' => MessageSenderType::class,
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CaseRequest, $this>
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseRequest::class, 'case_id');
    }

    /**
     * Scope query to unread messages.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
