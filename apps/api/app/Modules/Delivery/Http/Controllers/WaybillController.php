<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Http\Controllers;

use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use App\Modules\Delivery\Infrastructure\Pdf\WaybillGenerator;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use App\Modules\Identity\Domain\Models\Operator;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

/**
 * WaybillController (Architecture §6.8, TASK-097).
 * Handles official PDF waybill generation, encrypted storage, and secure signed downloads.
 */
final class WaybillController
{
    public const SIGNED_URL_TTL_SECONDS = 60;

    /**
     * GET /deliveries/{id}/waybill
     * Generates waybill, stores encrypted in MinIO, and returns signed 60-second download URL.
     */
    public function show(
        string $id,
        Request $request,
        WaybillGenerator $generator,
        EncryptedObjectStore $objectStore
    ): JsonResponse {
        $operator = $this->resolveOperator($request);

        /** @var DeliveryRequest|null $delivery */
        $delivery = DeliveryRequest::query()
            ->with(['office', 'case.citizen'])
            ->where('id', $id)
            ->first();

        if ($delivery === null || $delivery->office_id !== $operator->office_id) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'Delivery request not found.',
            ], 404));
        }

        $cacheKey = "delivery_waybill_meta:{$delivery->id}";
        /** @var array{storage_key: string, encrypted_data_key: string}|null $meta */
        $meta = Cache::get($cacheKey);

        if ($meta === null) {
            $stored = $generator->generateAndStore($delivery, $objectStore);
            $meta = [
                'storage_key' => $stored['storage_key'],
                'encrypted_data_key' => $stored['encrypted_data_key'],
            ];
            Cache::put($cacheKey, $meta, Carbon::now()->addHours(24));
        }

        $expiresAt = Carbon::now()->addSeconds(self::SIGNED_URL_TTL_SECONDS);
        $downloadUrl = URL::temporarySignedRoute(
            'deliveries.waybill.download',
            $expiresAt,
            ['id' => $delivery->id]
        );

        AuditLogger::record(
            action: 'delivery.waybill_requested',
            subject: $delivery,
            changes: ['storage_key' => $meta['storage_key']],
            context: ['office_id' => $delivery->office_id],
            actorType: 'operator',
            actorId: $operator->id
        );

        return new JsonResponse([
            'data' => [
                'delivery_id' => $delivery->id,
                'tracking_barcode' => $delivery->tracking_barcode,
                'storage_key' => $meta['storage_key'],
                'download_url' => $downloadUrl,
                'expires_in_seconds' => self::SIGNED_URL_TTL_SECONDS,
            ],
            'message' => 'بارنامه با موفقیت تولید و پیوند دانلود ۶۰ ثانیه‌ای صادر شد.',
        ]);
    }

    /**
     * GET /deliveries/{id}/waybill/download
     * Validates temporary signature and streams decrypted PDF.
     */
    public function download(
        string $id,
        Request $request,
        WaybillGenerator $generator,
        EncryptedObjectStore $objectStore
    ): Response {
        if (! $request->hasValidSignature()) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'code' => 'URL_SIGNATURE_EXPIRED',
                'detail' => 'پیوند دانلود منقضی شده است یا نامعتبر است (اعتبار: ۶۰ ثانیه).',
            ], 403));
        }

        /** @var DeliveryRequest|null $delivery */
        $delivery = DeliveryRequest::query()
            ->with(['office', 'case.citizen'])
            ->where('id', $id)
            ->first();

        if ($delivery === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'code' => 'RESOURCE_NOT_FOUND',
                'detail' => 'Delivery request not found.',
            ], 404));
        }

        $cacheKey = "delivery_waybill_meta:{$delivery->id}";
        /** @var array{storage_key: string, encrypted_data_key: string}|null $meta */
        $meta = Cache::get($cacheKey);

        if ($meta !== null && $objectStore->exists($meta['storage_key'])) {
            $pdfContent = $objectStore->retrieve($meta['storage_key'], $meta['encrypted_data_key']);
        } else {
            $pdfContent = $generator->generate($delivery);
        }

        AuditLogger::record(
            action: 'delivery.waybill_downloaded',
            subject: $delivery,
            changes: ['tracking_barcode' => $delivery->tracking_barcode],
            context: ['office_id' => $delivery->office_id],
            actorType: 'system',
            actorId: null
        );

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"waybill-{$delivery->tracking_barcode}.pdf\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function resolveOperator(Request $request): Operator
    {
        $user = $request->user();

        if (! $user instanceof Operator) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 403,
                'detail' => 'User is not an operator.',
            ], 403));
        }

        return $user;
    }
}
