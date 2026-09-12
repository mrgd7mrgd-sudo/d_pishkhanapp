<?php

declare(strict_types=1);

namespace App\Modules\Documents\Application\Actions;

use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Shared\Audit\AuditableAction;
use App\Shared\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * IssueSignedUrlAction (Architecture §6.8, §7.6).
 * Generates a 60-second valid temporary signed URL to view a case document via the decryption proxy,
 * enforcing ownership checks (anti-enumeration) and recording an immutable audit log entry.
 */
final class IssueSignedUrlAction
{
    /**
     * @return array{
     *     url: string,
     *     expires_at: string,
     *     expires_in_seconds: int,
     *     mime_type: string,
     *     size_bytes: int
     * }
     */
    public function execute(string $documentId, Authenticatable $actor): array
    {
        $cleanId = str_starts_with($documentId, 'cdoc_') ? substr($documentId, 5) : $documentId;

        /** @var CaseDocument|null $document */
        $document = CaseDocument::query()
            ->with('caseRequest')
            ->where('id', $cleanId)
            ->first();

        if ($document === null) {
            $this->abortNotFound();
        }

        // Anti-enumeration authorization check (§7.3)
        if ($actor instanceof Citizen) {
            if ($document->caseRequest === null || $document->caseRequest->citizen_id !== $actor->id) {
                $this->abortNotFound();
            }
        }

        // Record immutable audit log entry (§7.6: "document.viewed - مهم‌ترین ردیف این جدول")
        AuditLogger::record(
            action: AuditableAction::DOCUMENT_VIEWED,
            subject: $document,
            changes: [
                'document_type_code' => $document->document_type_code,
                'version' => $document->version,
                'mime_type' => $document->mime_type,
            ],
            context: [
                'ip' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent() ?? 'unknown',
            ],
            actorType: $actor instanceof Citizen ? 'citizen' : 'operator',
            actorId: (string) $actor->getAuthIdentifier(),
        );

        $expiresAt = CarbonImmutable::now()->addSeconds(60);
        $url = URL::temporarySignedRoute(
            'documents.proxy.view',
            $expiresAt,
            ['document' => $document->id]
        );

        return [
            'url' => $url,
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_in_seconds' => 60,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
        ];
    }

    /**
     * @return never
     */
    private function abortNotFound(): void
    {
        throw new HttpResponseException(new JsonResponse([
            'type' => 'https://api.pishkhan.ir/errors/RESOURCE_NOT_FOUND',
            'title' => 'RESOURCE_NOT_FOUND',
            'status' => Response::HTTP_NOT_FOUND,
            'code' => 'RESOURCE_NOT_FOUND',
            'detail' => 'مدرک یا پرونده یافت نشد.',
            'instance' => Request::path(),
        ], Response::HTTP_NOT_FOUND));
    }
}
