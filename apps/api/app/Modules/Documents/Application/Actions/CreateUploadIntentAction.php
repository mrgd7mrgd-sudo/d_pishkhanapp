<?php

declare(strict_types=1);

namespace App\Modules\Documents\Application\Actions;

use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Shared\Errors\ErrorCode;
use Carbon\CarbonImmutable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * CreateUploadIntentAction (Architecture §5.6 (8), §7.4, §7.5).
 * Generates presigned PUT URL valid for 5 minutes.
 */
final class CreateUploadIntentAction
{
    public const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024; // 10MB

    public const MAX_FILES_PER_CASE = 20;

    public const MAX_UPLOADS_PER_HOUR = 50;

    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/heic',
        'application/pdf',
    ];

    public function __construct(
        private readonly EncryptedObjectStore $store
    ) {}

    /**
     * @param array{
     *     case_id: string,
     *     document_type_code: string,
     *     filename: string,
     *     mime_type: string,
     *     size_bytes: int
     * } $data
     * @return array{
     *     upload_id: string,
     *     upload_url: string,
     *     method: string,
     *     headers: array<string, string>,
     *     expires_at: string,
     *     max_size_bytes: int
     * }
     */
    public function execute(array $data, Citizen $citizen): array
    {
        $this->enforceRateLimit($citizen->id);
        $this->validatePayload($data);

        $case = CaseRequest::query()->where('id', $data['case_id'])->first();
        if ($case === null || $case->citizen_id !== $citizen->id) {
            $this->abortJson(
                Response::HTTP_NOT_FOUND,
                'RESOURCE_NOT_FOUND',
                'پرونده مورد نظر یافت نشد.'
            );
        }

        $this->enforceDocumentCountLimit($case->id);

        $uploadId = 'upl_'.Str::lower((string) Str::ulid());
        $expiresAt = CarbonImmutable::now()->addMinutes(5);
        $tempStorageKey = "uploads/tmp/{$uploadId}";

        $uploadUrl = $this->store->generatePresignedUploadUrl(
            $tempStorageKey,
            $expiresAt,
            $data['mime_type']
        );

        $intent = [
            'upload_id' => $uploadId,
            'citizen_id' => $citizen->id,
            'case_id' => $case->id,
            'document_type_code' => $data['document_type_code'],
            'filename' => $data['filename'],
            'mime_type' => $data['mime_type'],
            'size_bytes' => $data['size_bytes'],
            'temp_storage_key' => $tempStorageKey,
            'created_at' => CarbonImmutable::now()->toIso8601String(),
            'expires_at' => CarbonImmutable::now()->addHours(24)->toIso8601String(),
        ];

        Cache::put("upload_intent:{$uploadId}", $intent, CarbonImmutable::now()->addHours(24));
        $this->trackUploadIntent($uploadId, $tempStorageKey, (int) CarbonImmutable::now()->addHours(24)->getTimestamp());

        return [
            'upload_id' => $uploadId,
            'upload_url' => $uploadUrl,
            'method' => 'PUT',
            'headers' => [
                'Content-Type' => $data['mime_type'],
            ],
            'expires_at' => $expiresAt->toIso8601String(),
            'max_size_bytes' => self::MAX_FILE_SIZE_BYTES,
        ];
    }

    private function enforceRateLimit(string $citizenId): void
    {
        $key = "upload-intent:{$citizenId}";
        if (RateLimiter::tooManyAttempts($key, self::MAX_UPLOADS_PER_HOUR)) {
            $this->abortJson(
                Response::HTTP_TOO_MANY_REQUESTS,
                ErrorCode::RATE_LIMITED->value,
                'سقف تعداد آپلود در ساعت (۵۰ بار) تکمیل شده است. لطفاً ساعتی دیگر تلاش کنید.'
            );
        }

        RateLimiter::hit($key, 3600);
    }

    /**
     * @param array{
     *     case_id: string,
     *     document_type_code: string,
     *     filename: string,
     *     mime_type: string,
     *     size_bytes: int
     * } $data
     */
    private function validatePayload(array $data): void
    {
        if ($data['size_bytes'] > self::MAX_FILE_SIZE_BYTES) {
            $this->abortJson(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'DOCUMENT_SIZE_EXCEEDED',
                'حجم فایل بیش از سقف مجاز ۱۰ مگابایت است.'
            );
        }

        if (! in_array($data['mime_type'], self::ALLOWED_MIME_TYPES, true)) {
            $this->abortJson(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'DOCUMENT_INVALID_MIME',
                'فرمت فایل نامعتبر است. فرمت‌های مجاز: image/jpeg, image/png, image/heic, application/pdf'
            );
        }
    }

    private function enforceDocumentCountLimit(string $caseId): void
    {
        $existingCount = CaseDocument::query()->where('case_id', $caseId)->count();
        if ($existingCount >= self::MAX_FILES_PER_CASE) {
            $this->abortJson(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'DOCUMENT_LIMIT_EXCEEDED',
                'حداکثر تعداد مدارک مجاز برای هر پرونده (۲۰ عدد) تکمیل شده است.'
            );
        }
    }

    private function trackUploadIntent(string $uploadId, string $tempKey, int $expiryTimestamp): void
    {
        /** @var list<array{id: string, temp_storage_key: string, expires_at: int}> $tracker */
        $tracker = Cache::get('unfinalized_upload_intents', []);
        $tracker[] = [
            'id' => $uploadId,
            'temp_storage_key' => $tempKey,
            'expires_at' => $expiryTimestamp,
        ];
        Cache::forever('unfinalized_upload_intents', $tracker);
    }

    /**
     * @return never
     */
    private function abortJson(int $status, string $code, string $detail): void
    {
        throw new HttpResponseException(new \Illuminate\Http\JsonResponse([
            'type' => "https://api.pishkhan.ir/errors/{$code}",
            'title' => $code,
            'status' => $status,
            'code' => $code,
            'detail' => $detail,
            'instance' => \Illuminate\Support\Facades\Request::path(),
        ], $status));
    }
}
