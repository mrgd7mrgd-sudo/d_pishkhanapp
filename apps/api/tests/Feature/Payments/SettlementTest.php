<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\FeeSplitCalculator;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Jobs\SettleCaseFeeJob;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند تست تسویه';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    $this->office = Office::create([
        'code' => 'OFF-SETTLE-01',
        'name' => 'دفتر پیشخوان بهشتی تسویه',
        'province_code' => 'THR',
        'city' => 'تهران',
        'is_online' => true,
    ]);

    ServiceCategory::query()->firstOrCreate(
        ['id' => 'identity'],
        ['title' => 'سجلی', 'slug' => 'identity', 'icon' => 'card', 'display_order' => 1]
    );

    $this->service = Service::create([
        'id' => 'svc_settle_test',
        'category_id' => 'identity',
        'slug' => 'svc-settle-test',
        'title' => 'خدمت تست تسویه',
        'description' => 'تست تسویه',
        'tags' => ['in-person'],
        'estimated_days_min' => 3,
        'estimated_days_max' => 7,
        'fee_rials' => 3_400_000,
        'office_share_percent' => 50.0,
        'is_active' => true,
    ]);

    $this->ledger = app(LedgerService::class);
    $this->calculator = app(FeeSplitCalculator::class);
});

test('architecture §8.2 numerical example: settlement splits 3,400,000 rials into 1,700,000 office payable and 1,700,000 platform revenue', function (): void {
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);
    $officePayable = $this->ledger->getOrCreateAccount(LedgerOwnerType::OFFICE, $this->office->id, LedgerAccountKind::PAYABLE);
    $revenue = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::REVENUE);

    // Initial deposit in escrow upon case creation
    $this->ledger->recordTransaction(
        reference: 'fee_init_settle',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 3_400_000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 3_400_000),
        ]
    );
    expect($this->ledger->getBalanceRials($escrow))->toBe(3_400_000);

    $case = CaseRequest::create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'office_id' => $this->office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::COMPLETED,
        'tracking_code' => 'CR-1405-99841',
        'fee_paid_rials' => 3_400_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    // Execute settlement job
    $job = new SettleCaseFeeJob($case->id);
    $job->handle($this->ledger, $this->calculator);

    // Verify exact account balances matching Architecture §8.2:
    // Escrow balance should be back to 0
    expect($this->ledger->getBalanceRials($escrow))->toBe(0);

    // Office payable balance = 1,700,000 Rials
    expect($this->ledger->getBalanceRials($officePayable))->toBe(1_700_000);

    // Platform revenue balance = 1,700,000 Rials
    expect($this->ledger->getBalanceRials($revenue))->toBe(1_700_000);

    // Verify double-entry equality: total debits == total credits
    $totalDebits = (int) DB::table('ledger_entries')->where('direction', LedgerDirection::DEBIT->value)->sum('amount_rials');
    $totalCredits = (int) DB::table('ledger_entries')->where('direction', LedgerDirection::CREDIT->value)->sum('amount_rials');
    expect($totalDebits)->toBe($totalCredits);
});

test('idempotency: running SettleCaseFeeJob multiple times does not duplicate payout or revenue', function (): void {
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);
    $officePayable = $this->ledger->getOrCreateAccount(LedgerOwnerType::OFFICE, $this->office->id, LedgerAccountKind::PAYABLE);
    $revenue = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::REVENUE);

    $this->ledger->recordTransaction(
        reference: 'fee_idem_settle',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 2_000_000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 2_000_000),
        ]
    );

    $case = CaseRequest::create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'office_id' => $this->office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::COMPLETED,
        'tracking_code' => 'CR-1405-IDEM',
        'fee_paid_rials' => 2_000_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    $job = new SettleCaseFeeJob($case->id);

    // Run first time
    $job->handle($this->ledger, $this->calculator);

    $officeBalance1 = $this->ledger->getBalanceRials($officePayable);
    $revenueBalance1 = $this->ledger->getBalanceRials($revenue);
    $settlementTxCount1 = DB::table('ledger_transactions')
        ->where('case_id', $case->id)
        ->where('reference', 'SETTLE-'.$case->tracking_code)
        ->where('type', LedgerTransactionType::SERVICE_FEE->value)
        ->count();

    expect($officeBalance1)->toBe(1_000_000)
        ->and($revenueBalance1)->toBe(1_000_000)
        ->and($settlementTxCount1)->toBe(1);

    // Run second time (should be completely idempotent)
    $job->handle($this->ledger, $this->calculator);

    expect($this->ledger->getBalanceRials($officePayable))->toBe($officeBalance1)
        ->and($this->ledger->getBalanceRials($revenue))->toBe($revenueBalance1)
        ->and(DB::table('ledger_transactions')->where('case_id', $case->id)->where('reference', 'SETTLE-'.$case->tracking_code)->count())->toBe(1);
});

test('service with custom share percent (e.g. 70%) distributes exact amounts to office and platform', function (): void {
    $service70 = Service::create([
        'id' => 'svc_settle_70',
        'category_id' => 'identity',
        'slug' => 'svc-settle-70',
        'title' => 'خدمت با سهم ۷۰ درصد',
        'description' => 'تست',
        'tags' => ['in-person'],
        'estimated_days_min' => 1,
        'estimated_days_max' => 2,
        'fee_rials' => 1_000_000,
        'office_share_percent' => 70.0,
        'is_active' => true,
    ]);

    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);
    $officePayable = $this->ledger->getOrCreateAccount(LedgerOwnerType::OFFICE, $this->office->id, LedgerAccountKind::PAYABLE);
    $revenue = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::REVENUE);

    $this->ledger->recordTransaction(
        reference: 'fee_70_settle',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 1_000_000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 1_000_000),
        ]
    );

    $case = CaseRequest::create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $service70->id,
        'office_id' => $this->office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::COMPLETED,
        'tracking_code' => 'CR-1405-70PERCENT',
        'fee_paid_rials' => 1_000_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    $job = new SettleCaseFeeJob($case->id);
    $job->handle($this->ledger, $this->calculator);

    // Office receives 70% = 700,000, Platform receives 30% = 300,000
    expect($this->ledger->getBalanceRials($officePayable))->toBe(700_000)
        ->and($this->ledger->getBalanceRials($revenue))->toBe(300_000)
        ->and($this->ledger->getBalanceRials($escrow))->toBe(0);
});
