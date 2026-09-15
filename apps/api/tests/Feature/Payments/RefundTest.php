<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\CaseWorkflow\Application\Actions\RejectCaseAction;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\Payments\Application\Actions\IssueRefundAction;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\Enums\RefundReason;
use App\Modules\Payments\Domain\Exceptions\RefundAlreadyProcessedException;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\LedgerTransaction;
use App\Modules\Payments\Jobs\RefundCaseFeeJob;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند تست استرداد';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    Role::firstOrCreate(['name' => 'office_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'office_operator', 'guard_name' => 'web']);

    $this->office = Office::create([
        'code' => 'REFUND01',
        'name' => 'دفتر آزمایشی استرداد',
        'province_code' => 'THR',
        'city' => 'تهران',
        'is_online' => true,
    ]);

    $this->manager = new Operator;
    $this->manager->office_id = $this->office->id;
    $this->manager->username = 'mgr_refund_test';
    $this->manager->password_hash = Hash::make('Secret123!');
    $this->manager->full_name = 'مدیر دفتر آزمایشی';
    $this->manager->national_id = '0010350899';
    $this->manager->mobile = '09120001122';
    $this->manager->role = OperatorRole::MANAGER;
    $this->manager->counter_number = 1;
    $this->manager->is_active = true;
    $this->manager->save();
    $this->manager->assignRole('office_manager');

    ServiceCategory::query()->firstOrCreate(
        ['id' => 'identity'],
        ['title' => 'سجلی', 'slug' => 'identity', 'icon' => 'card', 'display_order' => 1]
    );

    $this->service = Service::create([
        'id' => 'svc_refund_test',
        'category_id' => 'identity',
        'slug' => 'svc-refund-test',
        'title' => 'خدمت تست استرداد',
        'description' => 'تست استرداد',
        'tags' => ['in-person'],
        'estimated_days_min' => 3,
        'estimated_days_max' => 7,
        'fee_rials' => 3_400_000,
        'office_share_percent' => 50.0,
        'is_active' => true,
    ]);

    $this->ledger = app(LedgerService::class);
    $this->refundAction = app(IssueRefundAction::class);
});

test('E2E scenario E10: case is rejected -> refund is recorded -> wallet and ledger balance are correct', function (): void {
    // 1. Citizen charges wallet with 5,000,000 Rials
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $clearing = $this->ledger->getOrCreateAccount(LedgerOwnerType::GATEWAY, null, LedgerAccountKind::CLEARING);
    $escrow = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);

    $this->ledger->recordTransaction(
        reference: 'topup_init',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($clearing, LedgerDirection::DEBIT, 5_000_000),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, 5_000_000),
        ]
    );
    expect($this->ledger->getBalanceRials($wallet))->toBe(5_000_000);

    // 2. Case created and fee deducted (3,400,000 Rials from wallet to escrow)
    $this->ledger->recordTransaction(
        reference: 'case_fee_init',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 3_400_000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 3_400_000),
        ]
    );
    expect($this->ledger->getBalanceRials($wallet))->toBe(1_600_000);

    // 3. Case is in expert_review at office
    $case = CaseRequest::create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'office_id' => $this->office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::EXPERT_REVIEW,
        'tracking_code' => 'CR-1405-TEST-E10',
        'fee_paid_rials' => 3_400_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    // 4. Office manager rejects case
    $rejectAction = app(RejectCaseAction::class);
    $rejectAction->execute($this->manager, $case->id, 'عدم انطباق با مقررات سجلی');

    // 5. Execute Refund job
    $refundJob = new RefundCaseFeeJob($case->id, RefundReason::CASE_REJECTED);
    $refundJob->handle($this->refundAction);

    // 6. Verify refund results:
    // 70% refunded to citizen: 3,400,000 * 0.7 = 2,380,000
    // 30% retained platform revenue: 1,020,000
    // Citizen wallet balance: 1,600,000 + 2,380,000 = 3,980,000
    expect($this->ledger->getBalanceRials($wallet))->toBe(3_980_000);

    // Escrow balance should be back to 0
    expect($this->ledger->getBalanceRials($escrow))->toBe(0);

    // Revenue account balance should be 1,020,000
    $revenue = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::REVENUE);
    expect($this->ledger->getBalanceRials($revenue))->toBe(1_020_000);

    // Check double-entry invariant: SUM(debit) == SUM(credit)
    $totalDebits = (int) DB::table('ledger_entries')->where('direction', LedgerDirection::DEBIT->value)->sum('amount_rials');
    $totalCredits = (int) DB::table('ledger_entries')->where('direction', LedgerDirection::CREDIT->value)->sum('amount_rials');
    expect($totalDebits)->toBe($totalCredits);
});

test('duplicate refund prevention: cannot issue refund twice for same case', function (): void {
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);

    $this->ledger->recordTransaction(
        reference: 'fee_init_dup',
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
        'status' => CaseStatus::REJECTED,
        'tracking_code' => 'CR-1405-DUP-TEST',
        'fee_paid_rials' => 2_000_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    // First refund: success
    $tx1 = $this->refundAction->execute($case, RefundReason::CASE_REJECTED);
    expect($tx1)->toBeInstanceOf(LedgerTransaction::class);

    $initialTxCount = DB::table('ledger_transactions')->where('case_id', $case->id)->count();
    $balanceAfterFirstRefund = $this->ledger->getBalanceRials($wallet);

    // Second refund attempt via action directly: throws RefundAlreadyProcessedException
    expect(function () use ($case): void {
        $this->refundAction->execute($case, RefundReason::CASE_REJECTED);
    })->toThrow(RefundAlreadyProcessedException::class);

    // Second refund attempt via Job: gracefully catches and skips without throwing
    $job = new RefundCaseFeeJob($case->id, RefundReason::CASE_REJECTED);
    $job->handle($this->refundAction);

    // Balance and transactions must NOT have changed
    expect($this->ledger->getBalanceRials($wallet))->toBe($balanceAfterFirstRefund)
        ->and(DB::table('ledger_transactions')->where('case_id', $case->id)->count())->toBe($initialTxCount);
});

test('full refund for dispatch.exhausted returns 100% of fee paid', function (): void {
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);

    $this->ledger->recordTransaction(
        reference: 'fee_exhausted',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 3_000_000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 3_000_000),
        ]
    );

    $case = CaseRequest::create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::CANCELLED,
        'tracking_code' => 'CR-1405-EXHAUSTED',
        'fee_paid_rials' => 3_000_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    $this->refundAction->execute($case, RefundReason::DISPATCH_EXHAUSTED);

    // 100% refund = 3,000,000 Rials back to wallet
    expect($this->ledger->getBalanceRials($wallet))->toBe(0)
        ->and($this->ledger->getBalanceRials($escrow))->toBe(0);

    // Verify revenue received 0
    $revenue = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::REVENUE);
    expect($this->ledger->getBalanceRials($revenue))->toBe(0);
});

test('partial refund for deadline.expired returns 70% of fee paid and retains 30%', function (): void {
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);

    $this->ledger->recordTransaction(
        reference: 'fee_deadline',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 1_000_000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 1_000_000),
        ]
    );

    $case = CaseRequest::create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::CANCELLED,
        'tracking_code' => 'CR-1405-DEADLINE',
        'fee_paid_rials' => 1_000_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    $this->refundAction->execute($case, RefundReason::DEADLINE_EXPIRED);

    // 70% refund = 700,000 Rials back to wallet (wallet was -1M, now -300k)
    expect($this->ledger->getBalanceRials($wallet))->toBe(-300_000)
        ->and($this->ledger->getBalanceRials($escrow))->toBe(0);

    // 30% retained = 300,000 to revenue
    $revenue = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::REVENUE);
    expect($this->ledger->getBalanceRials($revenue))->toBe(300_000);
});

test('double-entry ledger invariant holds after every refund: SUM(debit) == SUM(credit)', function (): void {
    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);

    $amounts = [500_000, 1_200_000, 3_400_000, 7_800_000];

    foreach ($amounts as $i => $amount) {
        $this->ledger->recordTransaction(
            reference: 'inv_fee_'.$i,
            type: LedgerTransactionType::SERVICE_FEE,
            entries: [
                new LedgerEntryData($wallet, LedgerDirection::DEBIT, $amount),
                new LedgerEntryData($escrow, LedgerDirection::CREDIT, $amount),
            ]
        );

        $case = CaseRequest::create([
            'citizen_id' => $this->citizen->id,
            'service_id' => $this->service->id,
            'province_code' => 'THR',
            'status' => CaseStatus::CANCELLED,
            'tracking_code' => 'CR-INV-'.$i,
            'fee_paid_rials' => $amount,
            'delivery_mode' => 'in_person',
            'metadata' => [],
        ]);

        $reason = $i % 2 === 0 ? RefundReason::DISPATCH_EXHAUSTED : RefundReason::DEADLINE_EXPIRED;
        $this->refundAction->execute($case, $reason);

        $totalDebits = (int) DB::table('ledger_entries')->where('direction', LedgerDirection::DEBIT->value)->sum('amount_rials');
        $totalCredits = (int) DB::table('ledger_entries')->where('direction', LedgerDirection::CREDIT->value)->sum('amount_rials');

        expect($totalDebits)->toBe($totalCredits);
    }
});
