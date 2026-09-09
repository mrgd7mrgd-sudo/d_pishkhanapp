<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Shared\Crypto\HashedCast;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * OTP Challenge Domain Model (§6.1, §7.2, TASK-024)
 *
 * @property string $id
 * @property string $mobile_hash
 * @property string $code_hash
 * @property OtpPurpose $purpose
 * @property int $attempts
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $verified_at
 * @property string|null $ip_address
 */
final class OtpChallenge extends Model
{
    use HasUuids;

    protected $table = 'otp_challenges';

    protected $fillable = [
        'mobile_hash',
        'code_hash',
        'purpose',
        'attempts',
        'expires_at',
        'verified_at',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mobile_hash' => HashedCast::class,
            'purpose' => OtpPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Create and hash code for challenge. Raw code is never stored.
     */
    public static function createChallenge(
        string $mobile,
        string $rawCode,
        OtpPurpose $purpose,
        ?string $ipAddress = null,
        int $ttlSeconds = 120
    ): self {
        $challenge = new self;
        $challenge->mobile_hash = $mobile;
        $challenge->code_hash = Hash::driver('bcrypt')->make($rawCode);
        $challenge->purpose = $purpose;
        $challenge->attempts = 0;
        $challenge->expires_at = CarbonImmutable::now()->addSeconds($ttlSeconds);
        $challenge->ip_address = $ipAddress;
        $challenge->save();

        return $challenge;
    }

    /**
     * Verify whether challenge has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verify user submitted code using constant-time hash comparison.
     */
    public function verifyCode(string $rawCode): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->attempts >= 5) {
            return false;
        }

        $this->increment('attempts');

        if (password_verify($rawCode, $this->code_hash)) {
            $this->update(['verified_at' => CarbonImmutable::now()]);

            return true;
        }

        return false;
    }
}
