<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Modules\Documents\Domain\Models\VaultDocument;
use App\Modules\Documents\Domain\Models\VaultDocumentVersion;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\ReturnReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(null);
    CarbonImmutable::setTestNow(null);
    $this->seed(ProvinceSeeder::class);
    $this->seed(ReturnReasonSeeder::class);
    Storage::fake('documents');

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

    $this->store = App::make(EncryptedObjectStore::class);
});

afterEach(function (): void {
    Carbon::setTestNow(null);
    CarbonImmutable::setTestNow(null);
});

it('uploads a new document version while preserving previous version immutability (DoD)', function (): void {
    // 1. Create initial vault document (Version 1)
    $contentV1 = 'RAW_VERSION_1_PLAINTEXT_DATA_IDENTIFICATION_SCAN';
    $responseV1 = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/vault', [
            'title' => 'گواهینامه رانندگی',
            'category' => 'vehicular',
            'document_type_code' => 'DOC_DRIVING_LICENSE',
            'doc_number' => 'DL-998877',
            'issue_date' => '2024-05-10',
            'expiry_date' => '2034-05-10',
            'raw_content' => $contentV1,
            'mime_type' => 'image/jpeg',
            'attributes' => [
                ['label' => 'پایه', 'value' => 'پایه دوم'],
                ['label' => 'کد پیگیری', 'value' => 'TRK-445566'],
            ],
        ]);

    $responseV1->assertStatus(201);
    $docId = $responseV1->json('data.id');
    expect($responseV1->json('data.version'))->toBe(1)
        ->and($responseV1->json('data.attributes'))->toHaveCount(2);

    $v1Model = VaultDocumentVersion::query()
        ->where('vault_document_id', $docId)
        ->where('version', 1)
        ->first();
    expect($v1Model)->not->toBeNull();
    /** @var VaultDocumentVersion $v1Model */
    $v1StorageKey = $v1Model->storage_key;
    expect(Storage::disk('documents')->exists($v1StorageKey))->toBeTrue();

    // 2. Upload a new version for the existing document (Version 2)
    $contentV2 = 'RAW_VERSION_2_RENEWED_PLAINTEXT_DATA_SCAN';
    $responseV2 = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/vault', [
            'document_id' => $docId,
            'title' => 'گواهینامه رانندگی (تمدید شده)',
            'category' => 'vehicular',
            'raw_content' => $contentV2,
            'mime_type' => 'image/jpeg',
        ]);

    $responseV2->assertStatus(201);
    expect($responseV2->json('data.version'))->toBe(2);

    // 3. Verify both Version 1 and Version 2 exist in database
    $allVersions = VaultDocumentVersion::query()
        ->where('vault_document_id', $docId)
        ->orderBy('version')
        ->get();

    expect($allVersions)->toHaveCount(2)
        ->and($allVersions[0]->version)->toBe(1)
        ->and($allVersions[1]->version)->toBe(2);

    // 4. Verify Version 1 decrypted content remains intact and uncorrupted
    $decryptedV1 = $this->store->retrieve($allVersions[0]->storage_key, $allVersions[0]->encrypted_data_key);
    expect($decryptedV1)->toBe($contentV1);

    // 5. Verify Version 2 decrypted content is stored separately
    $decryptedV2 = $this->store->retrieve($allVersions[1]->storage_key, $allVersions[1]->encrypted_data_key);
    expect($decryptedV2)->toBe($contentV2);
});

it('performs soft delete retaining encrypted files in MinIO storage (DoD)', function (): void {
    $content = 'SECRET_PASSPORT_IMAGE_CONTENT';
    $response = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/vault', [
            'title' => 'گذرنامه',
            'category' => 'identity',
            'raw_content' => $content,
        ]);

    $response->assertStatus(201);
    $docId = $response->json('data.id');

    $doc = VaultDocument::query()->with('latestVersion')->find($docId);
    expect($doc)->not->toBeNull();
    /** @var VaultDocument $doc */
    $storageKey = $doc->latestVersion?->storage_key;
    expect($storageKey)->not->toBeNull();
    expect(Storage::disk('documents')->exists((string) $storageKey))->toBeTrue();

    // Perform soft delete
    $deleteResponse = $this->actingAs($this->citizenA, 'sanctum')
        ->deleteJson("/api/v1/vault/{$docId}");

    $deleteResponse->assertStatus(200);

    // Assert soft-deleted in database
    $this->assertSoftDeleted('vault_documents', ['id' => $docId]);

    // Assert MinIO encrypted file STILL EXISTS physically (DoD proof)
    expect(Storage::disk('documents')->exists((string) $storageKey))->toBeTrue();

    // Querying active documents does not include the deleted document
    $listResponse = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson('/api/v1/vault');
    $ids = collect($listResponse->json('data'))->pluck('id')->all();
    expect($ids)->not->toContain($docId);
});

it('blocks horizontal access and returns 404 when Citizen A accesses Citizen B vault document (DoD)', function (): void {
    // Citizen B creates a vault document
    $responseB = $this->actingAs($this->citizenB, 'sanctum')
        ->postJson('/api/v1/vault', [
            'title' => 'شناسنامه نیما',
            'category' => 'identity',
            'raw_content' => 'NIMA_PRIVATE_BIRTH_CERT',
        ]);

    $responseB->assertStatus(201);
    $docBId = $responseB->json('data.id');

    // Citizen A attempts to read Citizen B's document
    $showResponse = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/vault/{$docBId}");
    $showResponse->assertStatus(404);
    expect($showResponse->json('code'))->toBe('RESOURCE_NOT_FOUND');

    // Citizen A attempts to delete Citizen B's document
    $deleteResponse = $this->actingAs($this->citizenA, 'sanctum')
        ->deleteJson("/api/v1/vault/{$docBId}");
    $deleteResponse->assertStatus(404);
    expect($deleteResponse->json('code'))->toBe('RESOURCE_NOT_FOUND');

    // Citizen A's list never includes Citizen B's document
    $listResponse = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson('/api/v1/vault');
    $listResponse->assertStatus(200);
    $ids = collect($listResponse->json('data'))->pluck('id')->all();
    expect($ids)->not->toContain($docBId);
});

it('exposes vault metadata in responses while document content is accessible only via Signed URL (DoD)', function (): void {
    $now = CarbonImmutable::parse('2026-09-08 14:00:00');
    Carbon::setTestNow($now);
    CarbonImmutable::setTestNow($now);

    $rawContent = 'NATIONAL_ID_CARD_PLAIN_BYTES';
    $postResponse = $this->actingAs($this->citizenA, 'sanctum')
        ->postJson('/api/v1/vault', [
            'title' => 'کارت ملی',
            'category' => 'identity',
            'doc_number' => '0010350802',
            'raw_content' => $rawContent,
            'attributes' => [
                ['label' => 'تاریخ تولد', 'value' => '1307/07/15'],
            ],
        ]);

    $docId = $postResponse->json('data.id');

    // 1. Check show endpoint metadata
    $showResponse = $this->actingAs($this->citizenA, 'sanctum')
        ->getJson("/api/v1/vault/{$docId}");

    $showResponse->assertStatus(200);
    $data = $showResponse->json('data');

    // Metadata & attributes are present, raw content is never in JSON response
    expect($data)->toHaveKeys(['id', 'title', 'category', 'doc_number', 'attributes', 'versions', 'view_url'])
        ->and(json_encode($data))->not->toContain($rawContent)
        ->and($data['view_url'])->not->toBeNull();

    // 2. Fetch content via Signed URL proxy
    $viewResponse = $this->get($data['view_url']);
    $viewResponse->assertStatus(200);
    expect($viewResponse->getContent())->toBe($rawContent)
        ->and($viewResponse->headers->get('Cache-Control'))->toContain('no-store');

    // 3. Verify Signed URL expires after 60 seconds
    $expired = $now->addSeconds(61);
    Carbon::setTestNow($expired);
    CarbonImmutable::setTestNow($expired);

    $expiredResponse = $this->get($data['view_url']);
    $expiredResponse->assertStatus(403);
    expect($expiredResponse->json('code'))->toBe('URL_SIGNATURE_INVALID');
});
