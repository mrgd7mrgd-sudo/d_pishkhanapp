<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Models;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\PaymentGateway;
use App\Modules\Payments\Domain\Enums\PaymentIntentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * PaymentIntent Domain Model (Architecture §6.1, §8.2, TASK-084).
 *
 * @property string $id
 * @property string $citizen_id
 * @property int $amount_rials
 * @property PaymentGateway $gateway
 * @property string $authority
 * @property PaymentIntentStatus $status
 * @property string|null $ref_id
 * @property string|null $card_pan_masked
 * @property Carbon $expires_at
 * @property Carbon|null $verified_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Citizen|null $citizen
 * @property-read LedgerTransaction|null $ledgerTransaction
 *
 * @method static Builder<static> pendingReconciliation(Carbon $olderThan)
 */
final class PaymentIntent extends Model
{
    use HasUuids;

    protected $table = 'payment_intents';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'citizen_id',
        'amount_rials',
        'gateway',
        'authority',
        'status',
        'ref_id',
        'card_pan_masked',
        'expires_at',
        'verified_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_rials' => 'integer',
            'gateway' => PaymentGateway::class,
            'status' => PaymentIntentStatus::class,
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Ensure full 16-digit PAN is never stored raw; always masked as first 6 + 6 asterisks + last 4.
     * Architecture §7.7: Zero raw financial PII in storage.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function cardPanMasked(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value,
            set: function (?string $value): ?string {
                if ($value === null || trim($value) === '') {
                    return null;
                }

                $clean = trim($value);
                $digits = (string) preg_replace('/\D/', '', $clean);

                // If a full 16-digit PAN is provided without masking
                if (strlen($digits) === 16 && ! str_contains($clean, '*')) {
                    return substr($digits, 0, 6).'******'.substr($digits, -4);
                }

                return $clean;
            },
        );
    }

    /**
     * @return BelongsTo<Citizen, $this>
     */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    /**
     * @return HasOne<LedgerTransaction, $this>
     */
    public function ledgerTransaction(): HasOne
    {
        return $this->hasOne(LedgerTransaction::class, 'payment_intent_id');
    }

    /**
     * Scope for finding redirected intents that require reconciliation (§8.2, §5.9).
     *
     * @param  Builder<PaymentIntent>  $query
     */
    public function scopePendingReconciliation(Builder $query, Carbon $olderThan): void
    {
        $query->where('status', PaymentIntentStatus::REDIRECTED)
            ->where('created_at', '<=', $olderThan);
    }
}
