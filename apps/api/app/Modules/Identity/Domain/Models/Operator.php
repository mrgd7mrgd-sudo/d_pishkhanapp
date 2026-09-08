<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Shared\Crypto\HashedCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Operator Domain Model (§6.1, §6.2)
 *
 * @property string $id
 * @property string|null $office_id
 * @property string $full_name
 * @property string $national_id_hash
 * @property string $mobile_hash
 * @property OperatorRole $role
 * @property int $counter_number
 * @property bool $is_active
 * @property string|null $last_login_at
 */
final class Operator extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'operators';

    protected $fillable = [
        'office_id',
        'full_name',
        'national_id_hash',
        'mobile_hash',
        'role',
        'counter_number',
        'is_active',
        'last_login_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'national_id_hash' => HashedCast::class,
            'mobile_hash' => HashedCast::class,
            'role' => OperatorRole::class,
            'is_active' => 'boolean',
            'counter_number' => 'integer',
            'last_login_at' => 'datetime',
        ];
    }
}
