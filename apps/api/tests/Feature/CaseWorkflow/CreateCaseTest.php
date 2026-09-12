<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Events\CaseCreated;
use App\Modules\CaseWorkflow\Domain\Models\CaseDocument;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    ServiceCategory::query()->firstOrCreate(
        ['id' => 'identity'],
        ['title' => 'خدمات هویتی و سجلی', 'slug' => 'identity', 'icon' => 'card', 'display_order' => 1]
    );

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند تستی';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    $this->service = Service::query()->create([
        'category_id' => 'identity',
        'slug' => 'svc_identity_smart_card',
        'title' => 'صدور و تعویض کارت هوشمند ملی (بیومتریک)',
        'description' => 'درخواست کارت ملی هوشمند',
        'tags' => ['in-person'],
        'estimated_days_min' => 7,
        'estimated_days_max' => 14,
        'fee_rials' => 3400000,
        'office_share_percent' => 50.0,
        'is_active' => true,
    ]);

    $this->ledgerService = app(LedgerService::class);
});

function topUpCitizenWallet(LedgerService $ledger, Citizen $citizen, int $amountRials): void
{
    $wallet = $ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $citizen->id, LedgerAccountKind::WALLET);
    $clearing = $ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::CLEARING);

    $ledger->recordTransaction(
        reference: 'TOPUP-'.Str::random(8),
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($clearing->id, LedgerDirection::DEBIT, $amountRials),
            new LedgerEntryData($wallet->id, LedgerDirection::CREDIT, $amountRials),
        ],
        description: 'شارژ اولیه تستی'
    );
}

test('successful case creation strictly matches architecture sample 5 response and atomic ledger deduction', function (): void {
    Event::fake([CaseCreated::class]);

    topUpCitizenWallet($this->ledgerService, $this->citizen, 5000000);

    $idempotencyKey = '01J8XQ7K3M9YV2N5B8T4';
    $payload = [
        'service_id' => 'svc_identity_smart_card',
        'dispatch_mode' => 'auto',
        'office_id' => null,
        'payment_method' => 'wallet',
        'delivery_preference' => 'courier',
        'delivery_address_id' => 'addr_01J8XTEST',
        'on_behalf_of_delegation_id' => null,
        'documents' => [
            ['document_type_code' => 'DOC_BIRTH_CERT', 'upload_id' => 'upl_01J8XATEST'],
            ['document_type_code' => 'DOC_POSTAL_CODE', 'upload_id' => 'upl_01J8XBTEST'],
        ],
        'commitment_signed' => true,
        'citizen_location' => ['lat' => 35.7480, 'lng' => 51.4120],
    ];

    $response = $this->actingAs($this->citizen, 'sanctum')
        ->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/cases', $payload);

    $response->assertStatus(201);

    $data = $response->json('data');
    expect($data)->toBeArray()
        ->and($data['tracking_code'])->toMatch('/^CR-1405-\d{5}$/')
        ->and($data['status'])->toBe(CaseStatus::SEARCHING_OFFICE->value)
        ->and($data['turn_owner'])->toBe(TurnOwner::SYSTEM->value)
        ->and($data['turn_owner_label'])->toBe('در حال یافتن دفتر مناسب')
        ->and($data['service']['id'])->toBe($this->service->id)
        ->and($data['service']['title'])->toBe('صدور و تعویض کارت هوشمند ملی (بیومتریک)')
        ->and($data['service']['tag'])->toBe('in-person')
        ->and($data['assigned_office'])->toBeNull()
        ->and($data['current_step'])->toBe(1)
        ->and($data['total_steps'])->toBe(6)
        ->and($data['fee_paid_rials'])->toBe(3400000)
        ->and($data['office_share_rials'])->toBe(1700000)
        ->and($data['platform_share_rials'])->toBe(1700000)
        ->and($data['sla']['phase'])->toBe('dispatch')
        ->and($data['realtime_channel'])->toBe('private-case.'.$data['id'])
        ->and($data['timeline'])->toHaveCount(1)
        ->and($data['timeline'][0]['title'])->toBe('ثبت درخواست')
        ->and($data['timeline'][0]['status'])->toBe(TimelineStepStatus::DONE->value)
        ->and($data['timeline'][0]['turn_owner'])->toBe(TurnOwner::CITIZEN->value);

    // Ledger balance verified: 5,000,000 - 3,400,000 = 1,600,000
    $wallet = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    expect($this->ledgerService->getBalanceRials($wallet))->toBe(1600000);

    // Escrow balance verified: 3,400,000
    $escrow = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);
    expect($this->ledgerService->getBalanceRials($escrow))->toBe(3400000);

    // Case documents created
    expect(CaseDocument::query()->where('case_id', $data['id'])->count())->toBe(2);

    Event::assertDispatched(CaseCreated::class, fn (CaseCreated $e): bool => $e->case->id === $data['id']);
});

test('insufficient wallet balance returns RFC 7807 402 WALLET_INSUFFICIENT_BALANCE with complete meta', function (): void {
    // Citizen wallet has 1,245,000 rials, but service requires 3,400,000 rials
    topUpCitizenWallet($this->ledgerService, $this->citizen, 1245000);

    $idempotencyKey = '01J8XQ7K3M9YV2N5B8T5';
    $payload = [
        'service_id' => 'svc_identity_smart_card',
        'dispatch_mode' => 'auto',
        'payment_method' => 'wallet',
        'delivery_preference' => 'in_person',
        'commitment_signed' => true,
    ];

    $response = $this->actingAs($this->citizen, 'sanctum')
        ->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/cases', $payload);

    $response->assertStatus(402)
        ->assertJson([
            'code' => 'WALLET_INSUFFICIENT_BALANCE',
            'status' => 402,
            'title' => 'موجودی کیف پول کافی نیست',
            'meta' => [
                'required_rials' => 3400000,
                'available_rials' => 1245000,
                'shortfall_rials' => 2155000,
                'topup_url' => '/api/v1/wallet/topup',
            ],
        ]);

    // Invariant: zero case created, zero deduction
    expect(CaseRequest::query()->count())->toBe(0);
    $wallet = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    expect($this->ledgerService->getBalanceRials($wallet))->toBe(1245000);
});

test('missing idempotency key header returns validation error 422', function (): void {
    topUpCitizenWallet($this->ledgerService, $this->citizen, 5000000);

    $payload = [
        'service_id' => 'svc_identity_smart_card',
        'dispatch_mode' => 'auto',
        'payment_method' => 'wallet',
        'delivery_preference' => 'in_person',
        'commitment_signed' => true,
    ];

    $response = $this->actingAs($this->citizen, 'sanctum')
        ->postJson('/api/v1/cases', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['idempotency_key']);
});

test('reusing same idempotency key with same payload returns cached response without duplicate deduction', function (): void {
    topUpCitizenWallet($this->ledgerService, $this->citizen, 5000000);

    $idempotencyKey = '01J8XQ7K3M9YV2N5B8T9';
    $payload = [
        'service_id' => 'svc_identity_smart_card',
        'dispatch_mode' => 'auto',
        'payment_method' => 'wallet',
        'delivery_preference' => 'in_person',
        'commitment_signed' => true,
    ];

    // First request
    $response1 = $this->actingAs($this->citizen, 'sanctum')
        ->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/cases', $payload);

    $response1->assertStatus(201);
    $caseId1 = $response1->json('data.id');

    // Second request with exact same payload
    $response2 = $this->actingAs($this->citizen, 'sanctum')
        ->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/cases', $payload);

    $response2->assertStatus(201);
    $caseId2 = $response2->json('data.id');

    expect($caseId2)->toBe($caseId1);

    // Wallet deducted only ONCE: 5,000,000 - 3,400,000 = 1,600,000
    $wallet = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    expect($this->ledgerService->getBalanceRials($wallet))->toBe(1600000);

    // Exactly 1 case in database
    expect(CaseRequest::query()->count())->toBe(1);
});

test('reusing idempotency key with modified payload returns 409 conflict', function (): void {
    topUpCitizenWallet($this->ledgerService, $this->citizen, 5000000);

    $idempotencyKey = '01J8XQ7K3M9YV2N5B8T8';
    $payload1 = [
        'service_id' => 'svc_identity_smart_card',
        'dispatch_mode' => 'auto',
        'payment_method' => 'wallet',
        'delivery_preference' => 'in_person',
        'commitment_signed' => true,
    ];

    $response1 = $this->actingAs($this->citizen, 'sanctum')
        ->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/cases', $payload1);

    $response1->assertStatus(201);

    // Different payload
    $payload2 = array_merge($payload1, ['delivery_preference' => 'courier']);

    $response2 = $this->actingAs($this->citizen, 'sanctum')
        ->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/cases', $payload2);

    $response2->assertStatus(409)
        ->assertJson([
            'code' => 'IDEMPOTENCY_KEY_PAYLOAD_MISMATCH',
        ]);
});

test('failure at document attachment rolls back whole transaction', function (): void {
    topUpCitizenWallet($this->ledgerService, $this->citizen, 5000000);

    CaseDocument::saving(function (): void {
        throw new \RuntimeException('Simulated document attachment failure');
    });

    $idempotencyKey = '01J8XQ7K3M9YV2N5B8T7';
    $payload = [
        'service_id' => 'svc_identity_smart_card',
        'dispatch_mode' => 'auto',
        'payment_method' => 'wallet',
        'delivery_preference' => 'in_person',
        'commitment_signed' => true,
        'documents' => [
            ['document_type_code' => 'DOC_BIRTH_CERT', 'upload_id' => 'upl_fail'],
        ],
    ];

    try {
        $this->actingAs($this->citizen, 'sanctum')
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/cases', $payload);
    } catch (\Throwable) {
        // Expected DB exception caught
    }

    // Atomicity invariant: Zero cases created, Zero fee deducted from wallet
    expect(CaseRequest::query()->count())->toBe(0);
    $wallet = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    expect($this->ledgerService->getBalanceRials($wallet))->toBe(5000000);
});
