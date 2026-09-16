<?php

declare(strict_types=1);

namespace App\Integration\Government\Drivers;

use App\Integration\Government\DTO\PostalAddress;
use App\Integration\Government\DTO\ShipmentRequest;
use App\Integration\Government\DTO\ShipmentResult;
use App\Integration\Government\DTO\TrackingResult;
use App\Integration\Government\PostalClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real HTTP Driver for National Postal Company API (Architecture §8.5, TASK-098).
 */
class HttpDriver implements PostalClient
{
    public function __construct(
        private readonly string $baseUrl = 'https://api.post.ir',
        private readonly string $apiKey = 'default_key',
        private readonly int $timeoutSeconds = 10
    ) {}

    public function validatePostalCode(string $postalCode): PostalAddress
    {
        $clean = trim($postalCode);

        // 10-digit numeric validation
        if (strlen($clean) !== 10 || ! ctype_digit($clean)) {
            return new PostalAddress(
                isValid: false,
                postalCode: $clean,
                province: '',
                city: '',
                address: '',
                buildingNumber: null
            );
        }

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeoutSeconds)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("/api/v1/postal-code/{$clean}");

            if (! $response->successful()) {
                return new PostalAddress(
                    isValid: false,
                    postalCode: $clean,
                    province: '',
                    city: '',
                    address: '',
                    buildingNumber: null
                );
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();

            return new PostalAddress(
                isValid: (bool) ($data['is_valid'] ?? true),
                postalCode: $clean,
                province: (string) ($data['province'] ?? ''),
                city: (string) ($data['city'] ?? ''),
                address: (string) ($data['address'] ?? ''),
                buildingNumber: isset($data['building_number']) ? (string) $data['building_number'] : null
            );
        } catch (\Throwable $e) {
            throw new RuntimeException("Postal service HTTP request failed: {$e->getMessage()}", 502, $e);
        }
    }

    public function createShipment(ShipmentRequest $r): ShipmentResult
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeoutSeconds)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->post('/api/v1/shipments', [
                    'case_id' => $r->caseId,
                    'origin_office_id' => $r->originOfficeId,
                    'destination_postal_code' => $r->destinationPostalCode,
                    'destination_address' => $r->destinationAddress,
                    'recipient_name' => $r->recipientName,
                    'recipient_mobile' => $r->recipientMobile,
                    'package_type' => $r->packageType,
                ]);

            if (! $response->successful()) {
                return new ShipmentResult(
                    isSuccess: false,
                    barcode: null,
                    trackingUrl: null
                );
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();

            return new ShipmentResult(
                isSuccess: true,
                barcode: (string) ($data['barcode'] ?? ''),
                trackingUrl: (string) ($data['tracking_url'] ?? '')
            );
        } catch (\Throwable $e) {
            throw new RuntimeException("Postal shipment creation failed: {$e->getMessage()}", 502, $e);
        }
    }

    public function trackShipment(string $barcode): TrackingResult
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeoutSeconds)
                ->withHeaders(['X-API-Key' => $this->apiKey])
                ->get("/api/v1/tracking/{$barcode}");

            if (! $response->successful()) {
                return new TrackingResult(
                    barcode: $barcode,
                    status: 'unknown',
                    lastLocation: null,
                    lastEventTime: null,
                    history: []
                );
            }

            /** @var array<string, mixed> $data */
            $data = (array) $response->json();

            /** @var list<array<string, mixed>> $history */
            $history = is_array($data['history'] ?? null) ? array_values((array) $data['history']) : [];

            return new TrackingResult(
                barcode: $barcode,
                status: (string) ($data['status'] ?? 'in_transit'),
                lastLocation: (string) ($data['last_location'] ?? ''),
                lastEventTime: (string) ($data['last_event_time'] ?? now()->toIsoString()),
                history: $history
            );
        } catch (\Throwable $e) {
            throw new RuntimeException("Postal tracking request failed: {$e->getMessage()}", 502, $e);
        }
    }
}
