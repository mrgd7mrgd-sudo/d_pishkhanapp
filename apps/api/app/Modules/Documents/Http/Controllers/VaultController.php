<?php

declare(strict_types=1);

namespace App\Modules\Documents\Http\Controllers;

use App\Modules\Documents\Domain\Models\VaultDocument;
use App\Modules\Documents\Domain\Models\VaultDocumentAttribute;
use App\Modules\Documents\Domain\Models\VaultDocumentVersion;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use App\Modules\Identity\Domain\Models\Citizen;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * VaultController (Architecture §6.1, §6.2, §6.8, line 1083, TASK-060).
 * Citizen's personal encrypted document vault with immutable versions and soft deletes.
 */
final class VaultController
{
    /**
     * List all vault documents belonging to the authenticated citizen.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        $query = VaultDocument::query()
            ->where('citizen_id', $citizen->id)
            ->with(['attributes', 'latestVersion']);

        if ($request->filled('category')) {
            $query->where('category', (string) $request->query('category'));
        }

        $docs = $query->latest('updated_at')->get();

        $data = $docs->map(fn (VaultDocument $doc): array => $this->formatDocumentSummary($doc));

        return new JsonResponse(['data' => $data], SymfonyResponse::HTTP_OK);
    }

    /**
     * Store new vault document or add a new immutable version to an existing document.
     */
    public function store(Request $request, EncryptedObjectStore $store): JsonResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        $validated = $request->validate([
            'document_id' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'document_type_code' => ['nullable', 'string', 'max:64'],
            'doc_number' => ['nullable', 'string', 'max:128'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.label' => ['required_with:attributes', 'string', 'max:128'],
            'attributes.*.value' => ['required_with:attributes', 'string'],
            'file' => ['nullable', 'file', 'max:10240'],
            'raw_content' => ['nullable', 'string'],
            'mime_type' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $request, $citizen, $store): JsonResponse {
            $docId = $validated['document_id'] ?? null;
            if ($docId !== null) {
                /** @var VaultDocument|null $doc */
                $doc = VaultDocument::query()
                    ->where('id', $docId)
                    ->where('citizen_id', $citizen->id)
                    ->first();

                if ($doc === null) {
                    return new JsonResponse([
                        'type' => 'https://api.pishkhan.ir/errors/RESOURCE_NOT_FOUND',
                        'title' => 'RESOURCE_NOT_FOUND',
                        'status' => SymfonyResponse::HTTP_NOT_FOUND,
                        'code' => 'RESOURCE_NOT_FOUND',
                        'detail' => 'سند مورد نظر یافت نشد.',
                        'instance' => $request->path(),
                    ], SymfonyResponse::HTTP_NOT_FOUND);
                }

                $doc->update([
                    'title' => $validated['title'],
                    'category' => $validated['category'],
                    'document_type_code' => $validated['document_type_code'] ?? $doc->document_type_code,
                    'doc_number' => $validated['doc_number'] ?? $doc->doc_number,
                    'issue_date' => $validated['issue_date'] ?? $doc->issue_date,
                    'expiry_date' => $validated['expiry_date'] ?? $doc->expiry_date,
                ]);

                $versionNumber = (int) VaultDocumentVersion::query()
                    ->where('vault_document_id', $doc->id)
                    ->max('version') + 1;
            } else {
                $doc = VaultDocument::query()->create([
                    'id' => (string) Str::uuid(),
                    'citizen_id' => $citizen->id,
                    'category' => $validated['category'],
                    'document_type_code' => $validated['document_type_code'] ?? null,
                    'title' => $validated['title'],
                    'doc_number' => $validated['doc_number'] ?? null,
                    'issue_date' => $validated['issue_date'] ?? null,
                    'expiry_date' => $validated['expiry_date'] ?? null,
                    'is_verified' => false,
                ]);

                $versionNumber = 1;
            }

            // Sync attributes if supplied
            if (isset($validated['attributes']) && is_array($validated['attributes'])) {
                VaultDocumentAttribute::query()->where('vault_document_id', $doc->id)->delete();
                foreach (array_values($validated['attributes']) as $index => $attr) {
                    VaultDocumentAttribute::query()->create([
                        'id' => (string) Str::uuid(),
                        'vault_document_id' => $doc->id,
                        'label' => (string) $attr['label'],
                        'value' => (string) $attr['value'],
                        'display_order' => $index,
                    ]);
                }
            }

            // Process and encrypt document content
            $resolved = $this->resolveUploadContent($request);
            $content = $resolved['content'];
            $mimeType = $resolved['mime_type'];
            $fileName = $resolved['file_name'];

            $storageKey = $store->buildVaultDocumentKey(
                $citizen->province_code ?? 'THR',
                hash('sha256', $citizen->id),
                $doc->id,
                $versionNumber
            );

            $storeResult = $store->store($storageKey, $content);

            $version = VaultDocumentVersion::query()->create([
                'id' => (string) Str::uuid(),
                'vault_document_id' => $doc->id,
                'version' => $versionNumber,
                'storage_key' => $storeResult['storage_key'],
                'encrypted_data_key' => $storeResult['encrypted_data_key'],
                'content_sha256' => $storeResult['content_sha256'],
                'size_bytes' => $storeResult['size_bytes'],
                'mime_type' => $mimeType,
                'file_name' => $fileName,
                'quality_warnings' => [],
            ]);

            $doc->load(['attributes', 'latestVersion']);

            return new JsonResponse([
                'data' => array_merge($this->formatDocumentSummary($doc), [
                    'version' => $version->version,
                ]),
            ], SymfonyResponse::HTTP_CREATED);
        });
    }

    /**
     * Show single vault document detail with signed view URL.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        /** @var VaultDocument|null $doc */
        $doc = VaultDocument::query()
            ->where('id', $id)
            ->where('citizen_id', $citizen->id)
            ->with(['attributes', 'versions'])
            ->first();

        if ($doc === null) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/RESOURCE_NOT_FOUND',
                'title' => 'RESOURCE_NOT_FOUND',
                'status' => SymfonyResponse::HTTP_NOT_FOUND,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'سند مورد نظر یافت نشد.',
                'instance' => $request->path(),
            ], SymfonyResponse::HTTP_NOT_FOUND);
        }

        /** @var VaultDocumentVersion|null $latest */
        $latest = $doc->versions->first();
        $viewUrl = null;
        if ($latest !== null) {
            $viewUrl = URL::temporarySignedRoute(
                'documents.vault.view',
                CarbonImmutable::now()->addSeconds(60),
                ['version' => $latest->id]
            );
        }

        $detail = array_merge($this->formatDocumentSummary($doc), [
            'view_url' => $viewUrl,
            'versions' => $doc->versions->map(fn (VaultDocumentVersion $v): array => [
                'id' => $v->id,
                'version' => $v->version,
                'file_name' => $v->file_name,
                'mime_type' => $v->mime_type,
                'size_bytes' => $v->size_bytes,
                'created_at' => $v->created_at->toIso8601String(),
            ])->values()->all(),
        ]);

        return new JsonResponse(['data' => $detail], SymfonyResponse::HTTP_OK);
    }

    /**
     * Soft delete document (file remains in MinIO storage).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        /** @var VaultDocument|null $doc */
        $doc = VaultDocument::query()
            ->where('id', $id)
            ->where('citizen_id', $citizen->id)
            ->first();

        if ($doc === null) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/RESOURCE_NOT_FOUND',
                'title' => 'RESOURCE_NOT_FOUND',
                'status' => SymfonyResponse::HTTP_NOT_FOUND,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'سند مورد نظر یافت نشد.',
                'instance' => $request->path(),
            ], SymfonyResponse::HTTP_NOT_FOUND);
        }

        $doc->delete();

        return new JsonResponse(['message' => 'سند با موفقیت حذف شد.'], SymfonyResponse::HTTP_OK);
    }

    /**
     * Decryption Proxy: Serve decrypted binary of a vault document version via 60s signed URL.
     */
    public function view(Request $request, string $version, EncryptedObjectStore $store): SymfonyResponse
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

        /** @var VaultDocumentVersion|null $v */
        $v = VaultDocumentVersion::query()->where('id', $version)->first();
        if ($v === null) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/errors/RESOURCE_NOT_FOUND',
                'title' => 'RESOURCE_NOT_FOUND',
                'status' => SymfonyResponse::HTTP_NOT_FOUND,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'نسخه مدرک یافت نشد.',
                'instance' => $request->path(),
            ], SymfonyResponse::HTTP_NOT_FOUND);
        }

        $plaintext = $store->retrieve($v->storage_key, $v->encrypted_data_key);

        return new SymfonyResponse($plaintext, SymfonyResponse::HTTP_OK, [
            'Content-Type' => $v->mime_type,
            'Content-Length' => (string) strlen($plaintext),
            'Content-Disposition' => 'inline; filename="vault_'.($v->file_name ?? $v->id).'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * @return array{
     *     content: string,
     *     mime_type: string,
     *     file_name: string|null
     * }
     */
    private function resolveUploadContent(Request $request): array
    {
        $file = $request->file('file');
        if ($file instanceof UploadedFile) {
            $content = (string) file_get_contents($file->getRealPath());

            return [
                'content' => $content,
                'mime_type' => $file->getClientMimeType(),
                'file_name' => $file->getClientOriginalName(),
            ];
        }

        if ($request->filled('raw_content')) {
            $content = (string) $request->input('raw_content');
            $mimeType = (string) $request->input('mime_type', 'image/jpeg');

            return [
                'content' => $content,
                'mime_type' => $mimeType,
                'file_name' => 'document.jpg',
            ];
        }

        // Minimal 1x1 JPEG default
        $defaultJpeg = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00\xFF\xDB\x00C\x00\x08\x06\x06\x07\x06\x05\x08\x07\x07\x07\t\t\x08\n\x0c\x14\r\x0c\x0b\x0b\x0c\x19\x12\x13\x0f\x14\x1d\x1a\x1f\x1e\x1d\x1a\x1c\x1c $.' \",#\x1c\x1c(7),01444\x1f'9=82<.342\xFF\xC0\x00\x0b\x08\x00\x01\x00\x01\x01\x01\x11\x00\xFF\xC4\x00\x1f\x00\x00\x01\x05\x01\x01\x01\x01\x01\x01\x00\x00\x00\x00\x00\x00\x00\x00\x01\x02\x03\x04\x05\x06\x07\x08\t\n\x0b\xFF\xDA\x00\x08\x01\x01\x00\x00?\x00\xbf\x00\xFF\xD9";

        return [
            'content' => $defaultJpeg,
            'mime_type' => 'image/jpeg',
            'file_name' => 'default.jpg',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDocumentSummary(VaultDocument $doc): array
    {
        $attrs = $doc->attributes->map(fn (VaultDocumentAttribute $a): array => [
            'label' => $a->label,
            'value' => $a->value,
        ])->values()->all();

        $latest = $doc->latestVersion;

        return [
            'id' => $doc->id,
            'title' => $doc->title,
            'category' => $doc->category,
            'document_type_code' => $doc->document_type_code,
            'doc_number' => $doc->doc_number,
            'issue_date' => $doc->issue_date?->format('Y-m-d'),
            'expiry_date' => $doc->expiry_date?->format('Y-m-d'),
            'is_verified' => $doc->is_verified,
            'attributes' => $attrs,
            'latest_version' => $latest !== null ? [
                'version' => $latest->version,
                'file_name' => $latest->file_name,
                'mime_type' => $latest->mime_type,
                'size_bytes' => $latest->size_bytes,
                'created_at' => $latest->created_at->toIso8601String(),
            ] : null,
            'created_at' => $doc->created_at->toIso8601String(),
            'updated_at' => $doc->updated_at->toIso8601String(),
        ];
    }
}
