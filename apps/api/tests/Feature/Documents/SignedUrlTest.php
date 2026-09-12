<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Modules\CaseWorkflow\Domain\Enums\CaseDocumentStatus;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\ServiceCatalog\Domain\Models\DocumentType;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\ReturnReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(null);
    CarbonImmutable::setTestNow(null);
    $this->seed(ProvinceSeeder::class);
    $this->seed(ReturnReasonSeeder::class);
    Storage::fake('documents');

    ServiceCategory::query()->firstOrCreate(
        ['id' => 'identity'],
        ['title' => 'خدمات هویتی و سجلی', 'slug' => 'identity', 'icon' => 'card', 'display_order' => 1]
    );

    $this->citizenA = new Citizen;
    $this->citizenA->national_id = '0010350802';
    $this->citizenA->mobile = '09121112233';
    $this->citizenA->full_name = 'سهراب سپهری';
    $this->citizenA->tier = CitizenTier::BRONZE;
    $this->citizenA->province_code = 'THR';
    $this->citizenA->save();

    $this->citizenB = new Citizen;
    $this->citizenB->national_id = '0020450903';
    $this->citizenB->mobile = '09129998877';
    $this->citizenB->full_name = 'نیما یوشیج';
    $this->citizenB->tier = CitizenTier::SILVER;
    $this->citizenB->province_code = 'THR';
    $this->citizenB->save();

    $this->service = Service::query()->create([
        'category_id' => 'identity',
        'title' => 'صدور کارت ملی هوشمند',
        'slug' => 'national-card-'.Str::random(5),
        'description' => 'درخواست کارت ملی',
        'base_fee_rials' => 500_000,
        'sla_hours' => 48,
        'tags' => ['in-person'],
    ]);

    $this->docType = DocumentType::query()->firstOrCreate(
        ['code' => 'DOC_BIRTH_CERT'],
        [
            'title' => 'شناسنامه',
            'accepted_mimes' => ['image/jpeg', 'image/png'],
            'validity_months' => 120,
        ]
    );

    $this->caseA = CaseRequest::query()->create([
        'tracking_code' => 'CR-1405-'.Str::random(5),
        'citizen_id' => $this->citizenA->id,
        'service_id' => $this->service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::SEARCHING_OFFICE,
        'turn_owner' => TurnOwner::SYSTEM,
        'current_step' => 1,
        'total_steps' => 6,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
    ]);

    $this->caseB = CaseRequest::query()->create([
        'tracking_code' => 'CR-1405-'.Str::random(5),
        'citizen_id' => $this->citizenB->id,
        'service_id' => $this->service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::SEARCHING_OFFICE,
        'turn_owner' => TurnOwner::SYSTEM,
        'current_step' => 1,
        'total_steps' => 6,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
    ]);

    $this->store = App::make(EncryptedObjectStore::class);

    // Create encrypted documents for citizen A and citizen B
    $this->rawImageA = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00\xFF\xDB\x00C\x00CITIZEN_A_IDENTITY_PHOTO";
    $keyA = $this->store->buildCaseDocumentKey('THR', 2026, 9, $this->caseA->id, 'cdoc_doc_a', 1);
    $storeResA = $this->store->store($keyA, $this->rawImageA);

    $this->docA = CaseDocument::query()->create([
        'id' => (string) Str::uuid(),
        'case_id' => $this->caseA->id,
        'document_type_code' => 'DOC_BIRTH_CERT',
        'version' => 1,
        'status' => CaseDocumentStatus::VERIFIED,
        'storage_key' => $storeResA['storage_key'],
        'encrypted_data_key' => $storeResA['encrypted_data_key'],
        'content_sha256' => $storeResA['content_sha256'],
        'size_bytes' => $storeResA['size_bytes'],
        'mime_type' => 'image/jpeg',
        'quality_warnings' => [],
        'uploaded_at' => CarbonImmutable::now(),
    ]);

    $this->rawImageB = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00\xFF\xDB\x00C\x00CITIZEN_B_CONFIDENTIAL_DOC";
    $keyB = $this->store->buildCaseDocumentKey('THR', 2026, 9, $this->caseB->id, 'cdoc_doc_b', 1);
    $storeResB = $this->store->store($keyB, $this->rawImageB);

    $this->docB = CaseDocument::query()->create([
        'id' => (string) Str::uuid(),
        'case_id' => $this->caseB->id,
        'document_type_code' => 'DOC_BIRTH_CERT',
        'version' => 1,
        'status' => CaseDocumentStatus::VERIFIED,
        'storage_key' => $storeResB['storage_key'],
        'encrypted_data_key' => $storeResB['encrypted_data_key'],
        'content_sha256' => $storeResB['content_sha256'],
        'size_bytes' => $storeResB['size_bytes'],
        'mime_type' => 'image/jpeg',
        'quality_warnings' => [],
        'uploaded_at' => CarbonImmutable::now(),
    ]);
});

afterEach(function (): void {
    Carbon::setTestNow(null);
    CarbonImmutable::setTestNow(null);
});

it('issues signed URL with 60-second validity and serves decrypted binary content via proxy (DoD)', function (): void {
    $now = CarbonImmutable::parse('2026-09-08 12:00:00');
    Carbon::setTestNow($now);
    CarbonImmutable::setTestNow($now);

    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/documents/{$this->docA->id}/view-url");

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data)->toHaveKeys(['url', 'expires_at', 'expires_in_seconds', 'mime_type', 'size_bytes'])
        ->and($data['expires_in_seconds'])->toBe(60)
        ->and($data['expires_at'])->toBe('2026-09-08T12:01:00+00:00')
        ->and($data['mime_type'])->toBe('image/jpeg');

    // Access the signed URL immediately (within 60s window)
    $viewResponse = $this->get($data['url']);
    $viewResponse->assertStatus(200);
    $viewResponse->assertHeader('Content-Type', 'image/jpeg');
    expect($viewResponse->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('no-cache');
    expect($viewResponse->getContent())->toBe($this->rawImageA);
});

it('expires signed URL after 60 seconds and returns 403 (DoD)', function (): void {
    $now = CarbonImmutable::parse('2026-09-08 12:00:00');
    Carbon::setTestNow($now);
    CarbonImmutable::setTestNow($now);

    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/documents/{$this->docA->id}/view-url");

    $response->assertStatus(200);
    $signedUrl = $response->json('data.url');

    // Travel beyond the 60-second validity window (+61 seconds)
    $expiredTime = $now->addSeconds(61);
    Carbon::setTestNow($expiredTime);
    CarbonImmutable::setTestNow($expiredTime);

    $expiredResponse = $this->get($signedUrl);
    $expiredResponse->assertStatus(403);
    $expiredData = $expiredResponse->json();
    expect($expiredData['code'])->toBe('URL_SIGNATURE_INVALID');

    // Log DoD proof
    echo "\n--- [DoD VERIFICATION: 60-SECOND URL EXPIRATION PROOF] ---\n";
    echo "Issued At: {$now->toIso8601String()}\n";
    echo "Checked At (+61s): {$expiredTime->toIso8601String()}\n";
    echo "Response Status: {$expiredResponse->status()} (FORBIDDEN)\n";
    echo "Response Code: {$expiredData['code']}\n";
    echo "---------------------------------------------------------\n";
});

it('records document.viewed audit log row with opener citizen id on every issuance (DoD)', function (): void {
    $initialAuditCount = DB::table('audit_logs')
        ->where('action', 'document.viewed')
        ->count();

    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/documents/{$this->docA->id}/view-url");

    $response->assertStatus(200);

    $latestLog = DB::table('audit_logs')
        ->where('action', 'document.viewed')
        ->latest('occurred_at')
        ->first();

    expect($latestLog)->not->toBeNull()
        ->and($latestLog->actor_id)->toBe($this->citizenA->id)
        ->and($latestLog->actor_type)->toBe('citizen')
        ->and($latestLog->subject_id)->toBe($this->docA->id)
        ->and($latestLog->action)->toBe('document.viewed');

    $finalAuditCount = DB::table('audit_logs')
        ->where('action', 'document.viewed')
        ->count();
    expect($finalAuditCount)->toBe($initialAuditCount + 1);
});

it('blocks horizontal access and enforces anti-enumeration (404) when Citizen A requests Citizen B document (DoD)', function (): void {
    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/documents/{$this->docB->id}/view-url");

    $response->assertStatus(404);
    $data = $response->json();
    expect($data['code'])->toBe('RESOURCE_NOT_FOUND');

    // Ensure no document.viewed log was written for docB
    $unauthorizedAudit = DB::table('audit_logs')
        ->where('action', 'document.viewed')
        ->where('subject_id', $this->docB->id)
        ->exists();
    expect($unauthorizedAudit)->toBeFalse();
});

it('rejects unsigned or tampered proxy view requests with 403 (DoD)', function (): void {
    // 1. Direct call without signature query string
    $unsignedResponse = $this->get("/api/v1/documents/view/{$this->docA->id}");
    $unsignedResponse->assertStatus(403);
    expect($unsignedResponse->json('code'))->toBe('URL_SIGNATURE_INVALID');

    // 2. Call with forged signature parameter
    $tamperedResponse = $this->get("/api/v1/documents/view/{$this->docA->id}?expires=9999999999&signature=tampered_fake_signature");
    $tamperedResponse->assertStatus(403);
    expect($tamperedResponse->json('code'))->toBe('URL_SIGNATURE_INVALID');
});
