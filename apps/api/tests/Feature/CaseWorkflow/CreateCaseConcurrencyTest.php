<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Jobs\DispatchCaseJob;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerTransaction;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake([DispatchCaseJob::class]);
    $this->seed(ProvinceSeeder::class);

    ServiceCategory::query()->firstOrCreate(
        ['id' => 'identity'],
        ['title' => 'خدمات هویتی و سجلی', 'slug' => 'identity', 'icon' => 'card', 'display_order' => 1]
    );

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند تستی همزمانی';
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

test('10 requests with identical idempotency key produce exactly one case and one deduction', function (): void {
    // Initial citizen wallet balance: 10,000,000 rials
    $wallet = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $clearing = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::CLEARING);

    $this->ledgerService->recordTransaction(
        reference: 'TOPUP-CONCURRENCY',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($clearing, LedgerDirection::DEBIT, 10000000),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, 10000000),
        ],
        description: 'شارژ همزمانی'
    );

    $idempotencyKey = '01J8XQ7K-CONCURRENCY-'.Str::random(6);
    $payload = [
        'service_id' => 'svc_identity_smart_card',
        'dispatch_mode' => 'auto',
        'office_id' => null,
        'payment_method' => 'wallet',
        'delivery_preference' => 'courier',
        'delivery_address_id' => 'addr_01J8XTEST',
        'commitment_signed' => true,
        'documents' => [
            ['document_type_code' => 'DOC_BIRTH_CERT', 'upload_id' => 'upl_01J8XATEST'],
        ],
    ];

    $responses = [];
    $caseIds = [];

    // Send 10 requests with identical Idempotency-Key
    for ($i = 0; $i < 10; $i++) {
        $response = $this->actingAs($this->citizen, 'sanctum')
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/cases', $payload);

        $response->assertStatus(201);
        $responses[] = $response;
        $caseIds[] = $response->json('data.id');
    }

    // Invariant 1: Exactly 1 case request in database
    expect(CaseRequest::query()->count())->toBe(1);

    // Invariant 2: All 10 responses returned the exact same case id
    $uniqueIds = array_unique($caseIds);
    expect($uniqueIds)->toHaveCount(1);

    // Invariant 3: Exactly 1 fee transaction recorded for this case
    $createdCase = CaseRequest::query()->first();
    expect($createdCase)->not->toBeNull();

    $serviceFeeTxs = LedgerTransaction::query()
        ->where('type', LedgerTransactionType::SERVICE_FEE->value)
        ->get();
    expect($serviceFeeTxs)->toHaveCount(1)
        ->and($serviceFeeTxs->first()?->case_id)->toBe($createdCase->id);

    // Invariant 4: Wallet balance deducted exactly ONCE: 10,000,000 - 3,400,000 = 6,600,000
    expect($this->ledgerService->getBalanceRials($wallet))->toBe(6600000);

    // Invariant 5: Escrow balance increased exactly ONCE: 3,400,000
    $escrow = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);
    expect($this->ledgerService->getBalanceRials($escrow))->toBe(3400000);
});
