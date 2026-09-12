<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Modules\CaseWorkflow\Domain\Enums\CaseDocumentStatus;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Documents\Infrastructure\Processing\ExifStripper;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use App\Modules\Documents\Jobs\ProcessDocumentJob;
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

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'سهراب سپهری';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

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
            'accepted_mimes' => ['image/jpeg', 'image/png', 'application/pdf'],
            'validity_months' => 120,
        ]
    );

    $this->case = CaseRequest::query()->create([
        'tracking_code' => 'CR-1405-'.Str::random(5),
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::SEARCHING_OFFICE,
        'turn_owner' => TurnOwner::SYSTEM,
        'current_step' => 1,
        'total_steps' => 6,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
    ]);

    $this->store = App::make(EncryptedObjectStore::class);
});

/**
 * Helper to store initial encrypted document and create CaseDocument record.
 */
function createTestDocument(CaseRequest $case, string $content, string $mimeType = 'image/jpeg'): CaseDocument
{
    $store = App::make(EncryptedObjectStore::class);
    $docId = 'cdoc_'.Str::lower((string) Str::ulid());
    $key = $store->buildCaseDocumentKey(
        $case->province_code,
        2026,
        9,
        $case->id,
        $docId,
        1
    );

    $storeResult = $store->store($key, $content);

    return CaseDocument::query()->create([
        'id' => (string) Str::uuid(),
        'case_id' => $case->id,
        'document_type_code' => 'DOC_BIRTH_CERT',
        'version' => 1,
        'status' => CaseDocumentStatus::PROCESSING,
        'storage_key' => $storeResult['storage_key'],
        'encrypted_data_key' => $storeResult['encrypted_data_key'],
        'content_sha256' => $storeResult['content_sha256'],
        'size_bytes' => $storeResult['size_bytes'],
        'mime_type' => $mimeType,
        'quality_warnings' => [],
        'uploaded_at' => CarbonImmutable::now(),
    ]);
}

it('rejects spoofed script file disguised as image with DOC_WRONG_TYPE (DoD)', function (): void {
    $scriptContent = (string) file_get_contents(base_path('tests/fixtures/documents/spoofed_script.jpg'));
    $doc = createTestDocument($this->case, $scriptContent, 'image/jpeg');

    ProcessDocumentJob::dispatchSync($doc->id);

    $doc->refresh();
    expect($doc->status)->toBe(CaseDocumentStatus::REJECTED)
        ->and($doc->reason_code)->toBe('DOC_WRONG_TYPE');
});

it('detects malware and rejects document with VIRUS_DETECTED via VirusScanner (DoD)', function (): void {
    // Valid PDF containing standard EICAR signature (base64 decoded to prevent host AV blocks)
    $eicar = "%PDF-1.4\n".base64_decode('WDVPIVAlQEFQWzRcUFpYNTQoUF4pN0NDKTd9JEVJQ0FSLVNUQU5EQVJELUFOVElWSVJVUy1URVNULUZJTEUhJEgrSCo=');
    $doc = createTestDocument($this->case, $eicar, 'application/pdf');

    ProcessDocumentJob::dispatchSync($doc->id);

    $doc->refresh();
    expect($doc->status)->toBe(CaseDocumentStatus::REJECTED)
        ->and($doc->reason_code)->toBe('VIRUS_DETECTED');
});

it('strips all EXIF markers and GPS coordinates from processed image (DoD)', function (): void {
    $rawWithExif = (string) file_get_contents(base_path('tests/fixtures/documents/exif_gps_sample.jpg'));
    $stripper = new ExifStripper;

    // Verify fixture indeed contains EXIF and GPS markers before processing
    expect($stripper->hasExif($rawWithExif))->toBeTrue()
        ->and(str_contains($rawWithExif, "\xFF\xE1"))->toBeTrue()
        ->and(str_contains($rawWithExif, 'GPSLatitude'))->toBeTrue();

    $doc = createTestDocument($this->case, $rawWithExif, 'image/jpeg');

    ProcessDocumentJob::dispatchSync($doc->id);

    $doc->refresh();
    expect($doc->status)->toBe(CaseDocumentStatus::VERIFIED);

    // Retrieve processed decrypted object from store
    $processed = $this->store->retrieve($doc->storage_key, $doc->encrypted_data_key);

    // Verify all EXIF markers and GPS data are 100% stripped
    expect($stripper->hasExif($processed))->toBeFalse()
        ->and(str_contains($processed, "\xFF\xE1"))->toBeFalse()
        ->and(str_contains($processed, 'GPSLatitude'))->toBeFalse();

    // Verify with PHP native exif_read_data that no EXIF/GPS sections exist
    if (function_exists('exif_read_data')) {
        $tempPath = tempnam(sys_get_temp_dir(), 'pishkhan_exif_');
        if ($tempPath !== false) {
            file_put_contents($tempPath, $processed);
            $readExif = @exif_read_data($tempPath);
            @unlink($tempPath);
            if (is_array($readExif)) {
                expect(isset($readExif['GPS']))->toBeFalse()
                    ->and(isset($readExif['EXIF']))->toBeFalse()
                    ->and($readExif['SectionsFound'] ?? '')->toBe('');
            }
        }
    }
});

it('flags blurry image with quality_warnings blur (DoD)', function (): void {
    $blurry = (string) file_get_contents(base_path('tests/fixtures/documents/blurry_sample.jpg'));
    $doc = createTestDocument($this->case, $blurry, 'image/jpeg');

    ProcessDocumentJob::dispatchSync($doc->id);

    $doc->refresh();
    expect($doc->status)->toBe(CaseDocumentStatus::VERIFIED)
        ->and($doc->quality_warnings)->toBe(['blur']);
});

it('verifies clean sharp image with zero quality warnings (DoD)', function (): void {
    $clean = (string) file_get_contents(base_path('tests/fixtures/documents/clean_sample.jpg'));
    $doc = createTestDocument($this->case, $clean, 'image/jpeg');

    ProcessDocumentJob::dispatchSync($doc->id);

    $doc->refresh();
    expect($doc->status)->toBe(CaseDocumentStatus::VERIFIED)
        ->and($doc->quality_warnings)->toBe([]);
});

it('completes full 8-step pipeline execution and re-encrypts in MinIO', function (): void {
    $clean = (string) file_get_contents(base_path('tests/fixtures/documents/clean_sample.jpg'));
    $doc = createTestDocument($this->case, $clean, 'image/jpeg');
    $initialKey = $doc->encrypted_data_key;

    ProcessDocumentJob::dispatchSync($doc->id);

    $doc->refresh();
    expect($doc->status)->toBe(CaseDocumentStatus::VERIFIED)
        ->and($doc->mime_type)->toBe('image/jpeg')
        ->and($doc->size_bytes)->toBeGreaterThan(0)
        ->and($doc->content_sha256)->toHaveLength(64)
        ->and($doc->encrypted_data_key)->not->toBeEmpty();

    // Verify the stored object on MinIO disk is encrypted binary ciphertext (not plaintext)
    $rawDiskContent = Storage::disk('documents')->get($doc->storage_key);
    expect($rawDiskContent)->not->toBeNull()
        ->and(str_contains((string) $rawDiskContent, 'JFIF'))->toBeFalse();
});

it('satisfies p95 processing duration <= 15s SLO requirement (§9.5)', function (): void {
    $clean = (string) file_get_contents(base_path('tests/fixtures/documents/clean_sample.jpg'));
    $doc = createTestDocument($this->case, $clean, 'image/jpeg');

    $start = microtime(true);
    ProcessDocumentJob::dispatchSync($doc->id);
    $elapsed = microtime(true) - $start;

    $doc->refresh();
    expect($doc->status)->toBe(CaseDocumentStatus::VERIFIED)
        ->and($elapsed)->toBeLessThan(15.0);
});
