<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Delivery\Domain\Enums\CourierType;
use App\Modules\Delivery\Domain\Enums\DeliveryDocType;
use App\Modules\Delivery\Domain\Enums\DeliveryPaymentMethod;
use App\Modules\Delivery\Domain\Enums\DeliveryStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * DeliveryRequest Domain Model (Architecture §6.1, §6.3, TASK-094).
 *
 * @property string $id
 * @property string $case_id
 * @property string $office_id
 * @property DeliveryDocType $doc_type
 * @property string $doc_type_name
 * @property string|null $doc_serial_number
 * @property string $destination_address
 * @property string $destination_postal_code
 * @property string|null $destination_zone
 * @property CourierType $courier_type
 * @property DeliveryStatus $delivery_status
 * @property string|null $courier_name
 * @property string|null $courier_phone
 * @property string|null $courier_plate
 * @property string|null $otp_hash
 * @property Carbon|null $otp_expires_at
 * @property int $shipping_fee_rials
 * @property DeliveryPaymentMethod $payment_method
 * @property bool $require_old_doc_return
 * @property bool $is_sealed_pack
 * @property string|null $security_note
 * @property string $tracking_barcode
 * @property Carbon|null $dispatched_at
 * @property Carbon|null $delivered_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CaseRequest|null $case
 * @property-read Office|null $office
 */
final class DeliveryRequest extends Model
{
    use HasUuids;

    protected $table = 'delivery_requests';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'case_id',
        'office_id',
        'doc_type',
        'doc_type_name',
        'doc_serial_number',
        'destination_address',
        'destination_postal_code',
        'destination_zone',
        'courier_type',
        'delivery_status',
        'courier_name',
        'courier_phone',
        'courier_plate',
        'otp_hash',
        'otp_expires_at',
        'shipping_fee_rials',
        'payment_method',
        'require_old_doc_return',
        'is_sealed_pack',
        'security_note',
        'tracking_barcode',
        'dispatched_at',
        'delivered_at',
    ];

    /**
     * The attributes that should be hidden for serialization (Architecture §7.4, §7.7).
     * OTP hash is never returned in API responses.
     *
     * @var list<string>
     */
    protected $hidden = [
        'otp_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'doc_type' => DeliveryDocType::class,
            'courier_type' => CourierType::class,
            'delivery_status' => DeliveryStatus::class,
            'payment_method' => DeliveryPaymentMethod::class,
            'shipping_fee_rials' => 'integer',
            'require_old_doc_return' => 'boolean',
            'is_sealed_pack' => 'boolean',
            'otp_expires_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
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
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    /**
     * @return HasMany<DeliveryEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(DeliveryEvent::class, 'delivery_request_id')->orderByDesc('occurred_at');
    }

    /**
     * Set a new 6-digit OTP (hashed with bcrypt, never raw).
     */
    public function setOtp(string $rawOtp, \DateTimeInterface $expiresAt): void
    {
        $this->otp_hash = Hash::make($rawOtp);
        $this->otp_expires_at = Carbon::instance($expiresAt);
    }

    /**
     * Verify the presented OTP code.
     */
    public function verifyOtp(string $rawOtp): bool
    {
        if ($this->otp_hash === null || $this->otp_expires_at === null) {
            return false;
        }

        if (Carbon::now()->isAfter($this->otp_expires_at)) {
            return false;
        }

        return Hash::check($rawOtp, $this->otp_hash);
    }
}
