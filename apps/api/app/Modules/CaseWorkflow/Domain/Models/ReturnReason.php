<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * ReturnReason Reference Domain Model (Architecture §6.1, §6.3, TASK-051).
 * Seeded reference dictionary of the 10 return reasons.
 *
 * @property string $code
 * @property string $title
 * @property string $default_message
 * @property string|null $sample_image_url
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ReturnReason extends Model
{
    protected $table = 'return_reasons';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'title',
        'default_message',
        'sample_image_url',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => ReturnReasonCode::class,
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
