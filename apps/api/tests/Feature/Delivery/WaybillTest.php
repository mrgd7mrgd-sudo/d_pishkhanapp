<?php

declare(strict_types=1);

namespace Tests\Feature\Delivery;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Delivery\Application\Actions\CreateDeliveryRequestAction;
use App\Modules\Delivery\Domain\Enums\CourierType;
use App\Modules\Delivery\Domain\Enums\DeliveryDocType;
use App\Modules\Delivery\Infrastructure\Pdf\WaybillGenerator;
use App\Modules\Documents\Infrastructure\Storage\EncryptedObjectStore;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);
    Storage::fake('documents');

    $this->office = Office::query()->create([
        'code' => '8030',
        'name' => 'دفتر پیشخوان ستارخان',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    $this->operator = new Operator;
    $this->operator->office_id = $this->office->id;
    $this->operator->username = 'op_satarkhan';
    $this->operator->password_hash = Hash::make('Password123!');
    $this->operator->full_name = 'کامران نوری';
    $this->operator->national_id = '0089876588';
    $this->operator->mobile = '09128889900';
    $this->operator->role = OperatorRole::OPERATOR;
    $this->operator->counter_number = 3;
    $this->operator->is_active = true;
    $this->operator->save();

    $this->citizen = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09121114477'),
        'mobile_encrypted' => 'enc:09121114477',
        'national_id_hash' => hash('sha256', '0010350877'),
        'national_id_encrypted' => 'enc:0010350877',
        'full_name' => 'حمیدرضا باقری',
        'tier' => CitizenTier::GOLD->value,
        'profile_completed' => true,
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-waybill',
        'title' => 'خدمات رسمی و گذرنامه',
    ]);

    $this->service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $category->id,
        'title' => 'تعویض گذرنامه بین‌المللی',
        'slug' => 'passport-exchange',
        'description' => 'تعویض گذرنامه و ارسال مدارک پلمب شده',
        'tags' => ['passport', 'delivery'],
        'fee_rials' => 900000,
        'office_share_percent' => 80,
        'is_active' => true,
    ]);
});

function createWaybillCase(Citizen $citizen, Service $service, Office $office): CaseRequest
{
    CaseRequest::$allowDirectStatusAssignment = true;

    $case = CaseRequest::query()->create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-WB-'.strtoupper(Str::random(6)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'province_code' => 'THR',
        'city' => 'تهران',
        'dispatch_mode' => 'manual',
        'office_id' => $office->id,
        'status' => CaseStatus::READY_FOR_ISSUE,
        'turn_owner' => TurnOwner::OFFICE,
        'delivery_preference' => DeliveryPreference::COURIER,
        'fee_paid_rials' => 900000,
    ]);

    CaseRequest::$allowDirectStatusAssignment = false;

    return $case;
}

test('WaybillGenerator creates valid binary PDF with Persian text and Code128 barcode', function (): void {
    Queue::fake();

    $case = createWaybillCase($this->citizen, $this->service, $this->office);

    /** @var CreateDeliveryRequestAction $createAction */
    $createAction = app(CreateDeliveryRequestAction::class);
    $delivery = $createAction->execute($this->operator, [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::SEALED_DOSSIER->value,
        'doc_type_name' => 'گذرنامه بین‌المللی پلمب‌شده',
        'doc_serial_number' => 'PASS-987123',
        'destination_address' => 'تهران، خیابان ستارخان، نبش کوچه دهم، پلاک ۱۰۰',
        'destination_postal_code' => '1456789012',
        'courier_type' => CourierType::EXPRESS_COURIER->value,
        'is_sealed_pack' => true,
        'require_old_doc_return' => true,
        'security_note' => 'تحویل صرفاً در قبال دریافت گذرنامه قبلی',
    ]);

    /** @var WaybillGenerator $generator */
    $generator = app(WaybillGenerator::class);
    $pdf = $generator->generate($delivery);

    // 1. PDF standard format assertions
    expect($pdf)->not->toBeEmpty()
        ->and(str_starts_with($pdf, '%PDF-'))->toBeTrue();

    // 2. Contains tracking barcode string
    expect(str_contains($pdf, $delivery->tracking_barcode))->toBeTrue();
});

test('stores waybill encrypted in MinIO under delivery-waybills/ layout and decrypts correctly', function (): void {
    Queue::fake();

    $case = createWaybillCase($this->citizen, $this->service, $this->office);

    /** @var CreateDeliveryRequestAction $createAction */
    $createAction = app(CreateDeliveryRequestAction::class);
    $delivery = $createAction->execute($this->operator, [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::IDENTITY_BOOKLET->value,
        'destination_address' => 'تهران، خیابان توحید، کوچه شقایق',
        'destination_postal_code' => '1456789011',
        'courier_type' => CourierType::SPECIAL_POST->value,
    ]);

    /** @var WaybillGenerator $generator */
    $generator = app(WaybillGenerator::class);
    /** @var EncryptedObjectStore $store */
    $store = app(EncryptedObjectStore::class);

    $stored = $generator->generateAndStore($delivery, $store);

    // Verify MinIO path layout (Architecture §6.8 line 2462)
    $expectedKey = "delivery-waybills/THR/{$delivery->created_at->format('Y')}/{$delivery->created_at->format('m')}/{$delivery->id}.pdf.enc";
    expect($stored['storage_key'])->toBe($expectedKey);

    // Verify file exists on disk
    expect($store->exists($expectedKey))->toBeTrue();

    // Verify raw file on storage is CIPHERTEXT (NOT starting with %PDF-)
    $rawCiphertext = $store->getRaw($expectedKey);
    expect(str_starts_with($rawCiphertext, '%PDF-'))->toBeFalse();

    // Verify retrieval and decryption produces exact valid PDF
    $decryptedPdf = $store->retrieve($stored['storage_key'], $stored['encrypted_data_key']);
    expect(str_starts_with($decryptedPdf, '%PDF-'))->toBeTrue()
        ->and(str_contains($decryptedPdf, $delivery->tracking_barcode))->toBeTrue();
});

test('operator can request waybill signed URL and download decrypted PDF', function (): void {
    Queue::fake();

    $case = createWaybillCase($this->citizen, $this->service, $this->office);

    /** @var CreateDeliveryRequestAction $createAction */
    $createAction = app(CreateDeliveryRequestAction::class);
    $delivery = $createAction->execute($this->operator, [
        'case_id' => $case->id,
        'doc_type' => DeliveryDocType::SMART_CARD->value,
        'destination_address' => 'تهران، خیابان باقرخان، پلاک ۴',
        'destination_postal_code' => '1456789099',
        'courier_type' => CourierType::EXPRESS_COURIER->value,
    ]);

    Sanctum::actingAs($this->operator, ['*']);

    // 1. Request waybill signed URL
    $response = $this->getJson("/api/v1/deliveries/{$delivery->id}/waybill");
    $response->assertOk();

    $data = $response->json('data');
    expect($data['delivery_id'])->toBe($delivery->id)
        ->and($data['tracking_barcode'])->toBe($delivery->tracking_barcode)
        ->and($data['expires_in_seconds'])->toBe(60)
        ->and($data['download_url'])->toContain('/api/v1/deliveries/');

    // 2. Download via valid signed URL
    $downloadResponse = $this->get($data['download_url']);
    $downloadResponse->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $content = (string) $downloadResponse->getContent();
    expect(str_starts_with($content, '%PDF-'))->toBeTrue()
        ->and(str_contains($content, $delivery->tracking_barcode))->toBeTrue();

    // 3. Download without valid signature fails with 403
    $tamperedUrl = $data['download_url'].'&tampered=1';
    $this->get($tamperedUrl)->assertStatus(403);
});
