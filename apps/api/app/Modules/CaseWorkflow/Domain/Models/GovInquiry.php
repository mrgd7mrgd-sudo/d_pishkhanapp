<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Models;

use App\Modules\CaseWorkflow\Domain\Enums\GovInquiryProvider;
use App\Modules\CaseWorkflow\Domain\Enums\GovInquiryStatus;
use App\Shared\Security\PiiRedactor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GovInquiry extends Model
{
    use HasUuids;

    protected $table = 'gov_inquiries';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'queued',
        'attempts' => 0,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'case_id',
        'provider',
        'status',
        'request_snapshot',
        'response_snapshot',
        'attempts',
        'last_error',
        'completed_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'case_id' => 'string',
            'provider' => GovInquiryProvider::class,
            'status' => GovInquiryStatus::class,
            'attempts' => 'integer',
            'last_error' => 'string',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CaseRequest, $this>
     */
    public function caseRequest(): BelongsTo
    {
        return $this->belongsTo(CaseRequest::class, 'case_id');
    }

    /**
     * @return Attribute<array<string, mixed>, array<string, mixed>>
     */
    protected function requestSnapshot(): Attribute
    {
        return Attribute::make(
            get: static function (mixed $value): array {
                if ($value === null || $value === '') {
                    return [];
                }
                /** @var array<string, mixed> $decoded */
                $decoded = is_string($value) ? json_decode($value, true) : (array) $value;

                return is_array($decoded) ? $decoded : [];
            },
            set: static function (mixed $value): string {
                /** @var array<string, mixed> $array */
                $array = is_array($value) ? $value : [];
                $redacted = PiiRedactor::redactArray($array);

                return json_encode($redacted, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            },
        );
    }

    /**
     * @return Attribute<array<string, mixed>|null, array<string, mixed>|null>
     */
    protected function responseSnapshot(): Attribute
    {
        return Attribute::make(
            get: static function (mixed $value): ?array {
                if ($value === null || $value === '') {
                    return null;
                }
                /** @var array<string, mixed> $decoded */
                $decoded = is_string($value) ? json_decode($value, true) : (array) $value;

                return is_array($decoded) ? $decoded : null;
            },
            set: static function (mixed $value): ?string {
                if ($value === null) {
                    return null;
                }
                /** @var array<string, mixed> $array */
                $array = is_array($value) ? $value : [];
                $redacted = PiiRedactor::redactArray($array);

                return json_encode($redacted, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            },
        );
    }

    public function recordAttempt(?string $error = null): void
    {
        $this->increment('attempts');
        if ($error !== null) {
            $this->update(['last_error' => $error]);
        }
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function markSucceeded(array $response): void
    {
        $this->update([
            'status' => GovInquiryStatus::SUCCEEDED,
            'response_snapshot' => $response,
            'completed_at' => CarbonImmutable::now(),
            'last_error' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $response
     */
    public function markMismatch(string $reason, ?array $response = null): void
    {
        $this->update([
            'status' => GovInquiryStatus::MISMATCH,
            'last_error' => $reason,
            'response_snapshot' => $response,
            'completed_at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $response
     */
    public function markFailed(string $error, ?array $response = null): void
    {
        $this->update([
            'status' => GovInquiryStatus::FAILED,
            'last_error' => $error,
            'response_snapshot' => $response,
            'completed_at' => CarbonImmutable::now(),
        ]);
    }
}
