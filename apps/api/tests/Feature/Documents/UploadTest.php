<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Modules\CaseWorkflow\Domain\Enums\CaseDocumentStatus;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Documents\Application\Actions\PruneUnfinalizedUploadsAction;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(null);
    CarbonImmutable::setTestNow(null);
    $this->seed(ProvinceSeeder::class);
    $this->seed(ReturnReasonSeeder::class);
    Storage::fake('documents');
    Cache::flush();
    RateLimiter::clear('upload-intent:*');

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
        'slug' => 'national-card-issue-'.Str::random(5),
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
});

afterEach(function (): void {
    Carbon::setTestNow(null);
    CarbonImmutable::setTestNow(null);
});

it('returns exact Architecture §5.6 Sample 8 structure for upload-intent with 5 min validity', function (): void {
    $now = CarbonImmutable::parse('2026-09-08 11:31:07');
    Carbon::setTestNow($now);
    CarbonImmutable::setTestNow($now);

    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/documents/upload-intent', [
            'case_id' => $this->caseA->id,
            'document_type_code' => 'DOC_BIRTH_CERT',
            'filename' => 'shenasnameh.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 2_148_576,
        ]);

    $response->assertStatus(201);
    $data = $response->json('data');

    expect($data)->toHaveKeys(['upload_id', 'upload_url', 'method', 'headers', 'expires_at', 'max_size_bytes'])
        ->and($data['upload_id'])->toStartWith('upl_')
        ->and($data['method'])->toBe('PUT')
        ->and($data['headers'])->toBe(['Content-Type' => 'image/jpeg'])
        ->and($data['expires_at'])->toBe('2026-09-08T11:36:07+00:00')
        ->and($data['max_size_bytes'])->toBe(10_485_760);
});

it('rejects files larger than 10MB (DoD: 11MB rejected with 422)', function (): void {
    $elevenMb = 11 * 1024 * 1024; // 11,534,336 bytes

    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/documents/upload-intent', [
            'case_id' => $this->caseA->id,
            'document_type_code' => 'DOC_BIRTH_CERT',
            'filename' => 'huge_file.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => $elevenMb,
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'code' => 'DOCUMENT_SIZE_EXCEEDED',
        ]);
});

it('rejects disallowed MIME types (DoD: invalid MIME rejected with 422)', function (): void {
    $invalidMimes = ['application/x-php', 'text/plain', 'video/mp4', 'application/x-msdownload'];

    foreach ($invalidMimes as $mime) {
        $response = $this->actingAs($this->citizenA, 'sanctum')
            ->postJson('/api/v1/documents/upload-intent', [
                'case_id' => $this->caseA->id,
                'document_type_code' => 'DOC_BIRTH_CERT',
                'filename' => 'malicious.php',
                'mime_type' => $mime,
                'size_bytes' => 1024,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 'DOCUMENT_INVALID_MIME',
            ]);
    }
});

it('enforces anti-enumeration (404) when requesting upload for another citizens case', function (): void {
    $response = $this->actingAs($this->citizenB, 'sanctum')
        ->postJson('/api/v1/documents/upload-intent', [
            'case_id' => $this->caseA->id,
            'document_type_code' => 'DOC_BIRTH_CERT',
            'filename' => 'shenasnameh.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 500_000,
        ]);

    $response->assertStatus(404);
});

it('enforces maximum 20 documents per case constraint (422 DOCUMENT_LIMIT_EXCEEDED)', function (): void {
    for ($i = 1; $i <= 20; $i++) {
        CaseDocument::query()->create([
            'id' => (string) Str::uuid(),
            'case_id' => $this->caseA->id,
            'document_type_code' => 'DOC_BIRTH_CERT',
            'version' => $i,
            'status' => CaseDocumentStatus::PROCESSING,
            'storage_key' => "case-documents/THR/2026/09/{$this->caseA->id}/cdoc_{$i}/v{$i}.enc",
            'encrypted_data_key' => 'enc-key',
            'content_sha256' => hash('sha256', "dummy-{$i}"),
            'size_bytes' => 1024,
            'mime_type' => 'image/jpeg',
            'quality_warnings' => [],
            'uploaded_at' => CarbonImmutable::now(),
        ]);
    }

    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/documents/upload-intent', [
            'case_id' => $this->caseA->id,
            'document_type_code' => 'DOC_BIRTH_CERT',
            'filename' => 'excess_file.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100_000,
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'code' => 'DOCUMENT_LIMIT_EXCEEDED',
        ]);
});

it('enforces rate limit of 50 uploads per hour per citizen (429)', function (): void {
    for ($i = 0; $i < 50; $i++) {
        RateLimiter::hit("upload-intent:{$this->citizenA->id}", 3600);
    }

    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/documents/upload-intent', [
            'case_id' => $this->caseA->id,
            'document_type_code' => 'DOC_BIRTH_CERT',
            'filename' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 500_000,
        ]);

    $response->assertStatus(429);
});

it('completes upload, moves to permanent encrypted MinIO path, creates CaseDocument and enqueues quality check', function (): void {
    $intentRes = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/documents/upload-intent', [
            'case_id' => $this->caseA->id,
            'document_type_code' => 'DOC_BIRTH_CERT',
            'filename' => 'shenasnameh.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
        ]);

    $intentData = $intentRes->json('data');
    $uploadId = $intentData['upload_id'];

    // Simulate client uploading binary bytes to temp path
    $rawPlaintext = 'SENSITIVE_NATIONAL_IDENTITY_SCAN_PLAINTEXT_BYTES_'.Str::random(64);
    Storage::disk('documents')->put("uploads/tmp/{$uploadId}", $rawPlaintext);

    $completeRes = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/documents/upload-complete', [
            'upload_id' => $uploadId,
        ]);

    $completeRes->assertStatus(200);
    $data = $completeRes->json('data');

    expect($data)->toHaveKeys(['id', 'status', 'quality_check'])
        ->and($data['id'])->toStartWith('cdoc_')
        ->and($data['status'])->toBe('processing')
        ->and($data['quality_check']['state'])->toBe('queued')
        ->and($data['quality_check']['job_id'])->toStartWith('job_');

    // Verify CaseDocument record in database
    $docId = str_replace('cdoc_', '', $data['id']);
    $caseDoc = CaseDocument::query()->where('id', $docId)->first();
    expect($caseDoc)->not->toBeNull()
        ->and($caseDoc->version)->toBe(1)
        ->and($caseDoc->document_type_code)->toBe('DOC_BIRTH_CERT')
        ->and($caseDoc->storage_key)->toStartWith('case-documents/THR/')
        ->and($caseDoc->storage_key)->toEndWith('/v1.enc')
        ->and($caseDoc->content_sha256)->toBe(hash('sha256', $rawPlaintext))
        ->and($caseDoc->size_bytes)->toBe(strlen($rawPlaintext));

    // Verify temp upload cleaned up
    expect(Storage::disk('documents')->exists("uploads/tmp/{$uploadId}"))->toBeFalse();
});

it('proves direct MinIO object download yields raw ciphertext unreadable without decryption (DoD)', function (): void {
    $store = app(EncryptedObjectStore::class);

    $plaintext = 'CONFIDENTIAL_NATIONAL_ID_IMAGE_BINARY_DATA_WITH_SENSITIVE_DETAILS_'.Str::random(100);
    $storageKey = $store->buildCaseDocumentKey('THR', 2026, 9, $this->caseA->id, 'cdoc_test_123', 1);

    // Store encrypted object
    $metadata = $store->store($storageKey, $plaintext);

    // Direct raw download from MinIO/disk
    $rawDownloadedBytes = $store->getRaw($storageKey);

    // 1. Raw downloaded content must NOT contain the plaintext
    expect($rawDownloadedBytes)->not->toContain($plaintext)
        ->and($rawDownloadedBytes)->not->toBe($plaintext)
        ->and(strlen($rawDownloadedBytes))->toBeGreaterThanOrEqual(strlen($plaintext) + 28);

    // Print actual raw ciphertext proof for DoD audit
    $hexPreview = bin2hex(substr($rawDownloadedBytes, 0, 32));
    echo "\n--- [DoD VERIFICATION: RAW MINIO CIPHERTEXT PROOF] ---\n";
    echo "Storage Key: {$storageKey}\n";
    echo 'Plaintext length: '.strlen($plaintext)." bytes\n";
    echo 'Raw MinIO Ciphertext length: '.strlen($rawDownloadedBytes)." bytes\n";
    echo "Ciphertext Hex preview (first 32 bytes): {$hexPreview}\n";
    echo 'Plaintext found in raw object: '.(str_contains($rawDownloadedBytes, $plaintext) ? 'YES (FAIL)' : 'NO (SECURE PASS)')."\n";
    echo "---------------------------------------------------------\n";

    // 2. Decrypt with correct key must yield original plaintext
    $decrypted = $store->retrieve($storageKey, $metadata['encrypted_data_key']);
    expect($decrypted)->toBe($plaintext);
});

it('prunes unfinalized uploads and deletes temporary files after 24 hours (DoD)', function (): void {
    $now = CarbonImmutable::parse('2026-09-08 10:00:00');
    Carbon::setTestNow($now);
    CarbonImmutable::setTestNow($now);

    // Create upload intent
    $intentRes = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/documents/upload-intent', [
            'case_id' => $this->caseA->id,
            'document_type_code' => 'DOC_BIRTH_CERT',
            'filename' => 'unfinalized.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
        ]);

    $uploadId = $intentRes->json('data.upload_id');
    $tempKey = "uploads/tmp/{$uploadId}";
    Storage::disk('documents')->put($tempKey, 'DUMMY_UNFINALIZED_BYTES');

    expect(Storage::disk('documents')->exists($tempKey))->toBeTrue()
        ->and(Cache::has("upload_intent:{$uploadId}"))->toBeTrue();

    // 12 hours later: should NOT be pruned
    Carbon::setTestNow($now->addHours(12));
    CarbonImmutable::setTestNow($now->addHours(12));
    $pruner = new PruneUnfinalizedUploadsAction;
    $pruned = $pruner->execute();
    expect($pruned)->toBe(0)
        ->and(Storage::disk('documents')->exists($tempKey))->toBeTrue();

    // 25 hours later: MUST be pruned
    Carbon::setTestNow($now->addHours(25));
    CarbonImmutable::setTestNow($now->addHours(25));
    $pruned = $pruner->execute();
    expect($pruned)->toBe(1)
        ->and(Storage::disk('documents')->exists($tempKey))->toBeFalse()
        ->and(Cache::has("upload_intent:{$uploadId}"))->toBeFalse();
});
