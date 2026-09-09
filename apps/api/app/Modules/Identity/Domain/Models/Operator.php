<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Shared\Crypto\EncryptedCast;
use App\Shared\Crypto\HashedCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Operator Domain Model (§6.1, §6.2, §7.2)
 *
 * @property string $id
 * @property string|null $office_id
 * @property string $username
 * @property string $password_hash
 * @property string $full_name
 * @property string|null $national_id_encrypted
 * @property string $national_id
 * @property string $national_id_hash
 * @property string|null $mobile_encrypted
 * @property string $mobile
 * @property string $mobile_hash
 * @property string|null $allowed_ip_ranges
 * @property OperatorRole $role
 * @property int $counter_number
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 */
final class Operator extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;
    use HasUuids;
    use SoftDeletes;

    protected string $guard_name = 'web';

    protected $table = 'operators';

    protected $attributes = [
        'role' => 'operator',
        'counter_number' => 1,
        'is_active' => true,
    ];

    protected $fillable = [
        'office_id',
        'username',
        'password_hash',
        'full_name',
        'national_id_encrypted',
        'national_id_hash',
        'mobile_encrypted',
        'mobile_hash',
        'allowed_ip_ranges',
        'role',
        'counter_number',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'national_id_encrypted',
        'mobile_encrypted',
    ];

    /**
     * Get the password for the operator authentication.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

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

    public function getNationalIdAttribute(): ?string
    {
        $value = $this->attributes['national_id_encrypted'] ?? null;
        if ($value === null) {
            return null;
        }

        return (new EncryptedCast)->get($this, 'national_id_encrypted', $value, $this->attributes);
    }

    public function setNationalIdAttribute(?string $value): void
    {
        $this->attributes['national_id_encrypted'] = (new EncryptedCast)->set($this, 'national_id_encrypted', $value, $this->attributes);
        $this->attributes['national_id_hash'] = (new HashedCast)->set($this, 'national_id_hash', $value, $this->attributes);
    }

    public function getMobileAttribute(): ?string
    {
        $value = $this->attributes['mobile_encrypted'] ?? null;
        if ($value === null) {
            return null;
        }

        return (new EncryptedCast)->get($this, 'mobile_encrypted', $value, $this->attributes);
    }

    public function setMobileAttribute(?string $value): void
    {
        $this->attributes['mobile_encrypted'] = (new EncryptedCast)->set($this, 'mobile_encrypted', $value, $this->attributes);
        $this->attributes['mobile_hash'] = (new HashedCast)->set($this, 'mobile_hash', $value, $this->attributes);
    }
}
