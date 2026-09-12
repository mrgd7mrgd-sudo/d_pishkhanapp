<?php

declare(strict_types=1);

namespace App\Modules\Documents\Http\Controllers;

use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\Documents\Application\Actions\IssueSignedUrlAction;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * DocumentUrlController (Architecture §6.8, §7.6).
 * Manages signed URL generation for document viewing and proxies decryption of encrypted objects.
 */
final class DocumentUrlController
{
    /**
     * Generate 60-second signed URL for authorized viewing of document.
     */
    public function issueSignedUrl(Request $request, string $document, IssueSignedUrlAction $action): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/UNAUTHORIZED',
                'title' => 'UNAUTHORIZED',
                'status' => SymfonyResponse::HTTP_UNAUTHORIZED,
                'code' => 'UNAUTHORIZED',
                'detail' => 'احراز هویت الزامی است.',
                'instance' => $request->path(),
            ], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        $result = $action->execute($document, $user);

        return new JsonResponse(['data' => $result], SymfonyResponse::HTTP_OK);
    }

    /**
     * Decryption Proxy: Serves decrypted document binary through validated 60-second signed URL.
     */
    public function view(Request $request, string $document, EncryptedObjectStore $store): SymfonyResponse
    {
        if (! $request->hasValidSignature()) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/URL_SIGNATURE_INVALID',
                'title' => 'URL_SIGNATURE_INVALID',
                'status' => SymfonyResponse::HTTP_FORBIDDEN,
                'code' => 'URL_SIGNATURE_INVALID',
                'detail' => 'امضای پیوند نامعتبر است یا منقضی شده است.',
                'instance' => $request->path(),
            ], SymfonyResponse::HTTP_FORBIDDEN);
        }

        $cleanId = str_starts_with($document, 'cdoc_') ? substr($document, 5) : $document;

        /** @var CaseDocument|null $caseDoc */
        $caseDoc = CaseDocument::query()->where('id', $cleanId)->first();
        if ($caseDoc === null) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/RESOURCE_NOT_FOUND',
                'title' => 'RESOURCE_NOT_FOUND',
                'status' => SymfonyResponse::HTTP_NOT_FOUND,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'مدرک یافت نشد.',
                'instance' => $request->path(),
            ], SymfonyResponse::HTTP_NOT_FOUND);
        }

        $plaintext = $store->retrieve($caseDoc->storage_key, $caseDoc->encrypted_data_key);

        return new SymfonyResponse($plaintext, SymfonyResponse::HTTP_OK, [
            'Content-Type' => $caseDoc->mime_type,
            'Content-Length' => (string) strlen($plaintext),
            'Content-Disposition' => 'inline; filename="document_'.$caseDoc->id.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
