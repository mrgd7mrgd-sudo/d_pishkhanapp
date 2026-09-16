<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Identity\Domain\Enums\DelegationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Legal Delegation Model (Architecture §5.3, §6.1, §7.1 T4, TASK-121).
 *
 * Invariant §5.3: valid_until in the future, max_amount_rials > 0, activation requires two-party OTP.
 */
final class Delegation extends Model
{
    use HasUuids;

    protected $table = 'delegations';

    protected $fillable = [
        'id',
        'principal_citizen_id',
        'delegate_citizen_id',
        'document_number',
        'status',
        'max_amount_rials',
        'allowed_service_ids',
        'principal_otp_hash',
        'delegate_otp_hash',
        'principal_otp_verified',
        'delegate_otp_verified',
        'otp_expires_at',
        'valid_until',
        'activated_at',
        'revoked_at',
    ];

    protected $casts = [
        'status' => DelegationStatus::class,
        'max_amount_rials' => 'integer',
        'allowed_service_ids' => 'array',
        'principal_otp_verified' => 'boolean',
        'delegate_otp_verified' => 'boolean',
        'otp_expires_at' => 'datetime',
        'valid_until' => 'datetime',
        'activated_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Delegation $delegation): void {
            if ($delegation->max_amount_rials <= 0) {
                throw new InvalidArgumentException('سقف مبلغ نمایندگی (max_amount_rials) باید اکیداً مثبت باشد (§5.3).');
            }

            if ($delegation->isDirty('valid_until') && $delegation->valid_until->isPast() && $delegation->status === DelegationStatus::PendingOtp) {
                throw new InvalidArgumentException('تاریخ پایان اعتبار نمایندگی (valid_until) باید در آینده باشد (§5.3).');
            }
        });
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'principal_citizen_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'delegate_citizen_id');
    }

    public function isServiceAllowed(string $serviceId): bool
    {
        if ($this->allowed_service_ids === null || empty($this->allowed_service_ids)) {
            return true;
        }

        return in_array($serviceId, $this->allowed_service_ids, true);
    }
}
