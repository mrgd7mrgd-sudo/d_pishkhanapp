<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Shared\Crypto\EncryptedCast;
use App\Shared\Crypto\HashedCast;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Citizen Domain Model (§6.1, §6.2, §7.4)
 *
 * @property string $id
 * @property string|null $national_id_encrypted
 * @property string $national_id
 * @property string $national_id_hash
 * @property string|null $mobile_encrypted
 * @property string $mobile
 * @property string $mobile_hash
 * @property string $full_name
 * @property string|null $father_name
 * @property string|null $birth_date
 * @property string|null $postal_code
 * @property string|null $address
 * @property string|null $city_id
 * @property string|null $province_code
 * @property CitizenTier $tier
 * @property bool $sana_verified
 * @property bool $digital_signature_active
 * @property int $credit_score
 */
final class Citizen extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'citizens';

    protected $fillable = [
        'national_id_encrypted',
        'national_id_hash',
        'mobile_encrypted',
        'mobile_hash',
        'full_name',
        'father_name',
        'birth_date',
        'postal_code',
        'address',
        'city_id',
        'province_code',
        'tier',
        'sana_verified',
        'digital_signature_active',
        'credit_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'national_id_encrypted' => EncryptedCast::class,
            'national_id_hash' => HashedCast::class,
            'mobile_encrypted' => EncryptedCast::class,
            'mobile_hash' => HashedCast::class,
            'tier' => CitizenTier::class,
            'sana_verified' => 'boolean',
            'digital_signature_active' => 'boolean',
            'credit_score' => 'integer',
            'birth_date' => 'date',
        ];
    }

    public function getNationalIdAttribute(): ?string
    {
        return $this->national_id_encrypted;
    }

    public function setNationalIdAttribute(string $value): void
    {
        $this->attributes['national_id_encrypted'] = (new EncryptedCast)->set($this, 'national_id_encrypted', $value, $this->attributes);
        $this->attributes['national_id_hash'] = (new HashedCast)->set($this, 'national_id_hash', $value, $this->attributes);
    }

    public function getMobileAttribute(): ?string
    {
        return $this->mobile_encrypted;
    }

    public function setMobileAttribute(string $value): void
    {
        $this->attributes['mobile_encrypted'] = (new EncryptedCast)->set($this, 'mobile_encrypted', $value, $this->attributes);
        $this->attributes['mobile_hash'] = (new HashedCast)->set($this, 'mobile_hash', $value, $this->attributes);
    }
}
