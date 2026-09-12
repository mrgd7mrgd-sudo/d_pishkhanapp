<?php

declare(strict_types=1);

namespace App\Modules\Documents\Http\Controllers;

use App\Modules\Documents\Application\Actions\CompleteUploadAction;
use App\Modules\Documents\Application\Actions\CreateUploadIntentAction;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * UploadController (Architecture §5.6 (8), §6.8).
 * Handles two-stage document upload: intent and completion.
 */
final class UploadController
{
    public function __construct(
        private readonly CreateUploadIntentAction $createIntentAction,
        private readonly CompleteUploadAction $completeUploadAction
    ) {}

    /**
     * Stage 1: Request upload intent and get presigned PUT URL (5 min validity).
     */
    public function intent(Request $request): JsonResponse
    {
        $citizen = $this->resolveCitizen($request);

        $validated = $request->validate([
            'case_id' => ['required', 'string'],
            'document_type_code' => ['required', 'string', 'max:64'],
            'filename' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'max:100'],
            'size_bytes' => ['required', 'integer', 'min:1'],
        ]);

        /** @var array{case_id: string, document_type_code: string, filename: string, mime_type: string, size_bytes: int} $data */
        $data = [
            'case_id' => (string) $validated['case_id'],
            'document_type_code' => (string) $validated['document_type_code'],
            'filename' => (string) $validated['filename'],
            'mime_type' => (string) $validated['mime_type'],
            'size_bytes' => (int) $validated['size_bytes'],
        ];

        $result = $this->createIntentAction->execute($data, $citizen);

        return new JsonResponse([
            'data' => $result,
        ], Response::HTTP_CREATED);
    }

    /**
     * Stage 2: Finalize upload, move to MinIO encrypted storage, queue quality analysis.
     */
    public function complete(Request $request): JsonResponse
    {
        $citizen = $this->resolveCitizen($request);

        $validated = $request->validate([
            'upload_id' => ['required', 'string'],
        ]);

        $result = $this->completeUploadAction->execute(
            (string) $validated['upload_id'],
            $citizen
        );

        return new JsonResponse([
            'data' => $result,
        ], Response::HTTP_OK);
    }

    /**
     * Fallback endpoint for direct PUT upload when using signed URLs in local/test environment.
     */
    public function directPut(Request $request): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/FORBIDDEN',
                'title' => 'FORBIDDEN',
                'status' => Response::HTTP_FORBIDDEN,
                'code' => 'INVALID_SIGNATURE',
                'detail' => 'امضای نشانی منقضی شده یا نامعتبر است.',
                'instance' => $request->path(),
            ], Response::HTTP_FORBIDDEN);
        }

        $storageKey = (string) $request->query('key', '');
        if ($storageKey === '') {
            return new JsonResponse(['error' => 'Storage key is required'], Response::HTTP_BAD_REQUEST);
        }

        $content = (string) $request->getContent();
        Storage::disk('documents')->put($storageKey, $content);

        return new JsonResponse(['status' => 'uploaded'], Response::HTTP_OK);
    }

    private function resolveCitizen(Request $request): Citizen
    {
        $user = $request->user();
        if (! ($user instanceof Citizen)) {
            throw new HttpResponseException(new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/UNAUTHORIZED',
                'title' => 'UNAUTHORIZED',
                'status' => Response::HTTP_UNAUTHORIZED,
                'code' => 'UNAUTHORIZED',
                'detail' => 'احراز هویت شهروند الزامی است.',
                'instance' => $request->path(),
            ], Response::HTTP_UNAUTHORIZED));
        }

        return $user;
    }
}
