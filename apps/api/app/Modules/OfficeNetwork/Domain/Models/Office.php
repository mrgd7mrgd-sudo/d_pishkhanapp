<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Office Domain Model (Architecture §6.1, §6.4)
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property bool $is_online
 */
final class Office extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'offices';

    protected $fillable = [
        'code',
        'name',
        'is_online',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
        ];
    }
}
