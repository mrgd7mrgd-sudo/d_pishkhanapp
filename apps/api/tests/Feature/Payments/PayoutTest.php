<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\Payments\Application\Queries\OfficeSettlementReport;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\Enums\PayoutStatus;
use App\Modules\Payments\Domain\Exceptions\LedgerDiscrepancyException;
use App\Modules\Payments\Domain\FeeSplitCalculator;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\Payout;
use App\Modules\Payments\Jobs\SettleCaseFeeJob;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Shared\Audit\AuditableAction;
use Carbon\Carbon;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند تست تسویه روزانه';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    $this->office = Office::create([
        'code' => 'OFF-PAY-01',
        'name' => 'دفتر پیشخوان تست تسویه روزانه',
        'province_code' => 'THR',
        'city' => 'تهران',
        'is_online' => true,
    ]);

    ServiceCategory::query()->firstOrCreate(
        ['id' => 'identity'],
        ['title' => 'سجلی', 'slug' => 'identity', 'icon' => 'card', 'display_order' => 1]
    );

    $this->service = Service::create([
        'id' => 'svc_payout_test',
        'category_id' => 'identity',
        'slug' => 'svc-payout-test',
        'title' => 'خدمت تست تسویه روزانه',
        'description' => 'تست تسویه روزانه',
        'tags' => ['in-person'],
        'estimated_days_min' => 1,
        'estimated_days_max' => 3,
        'fee_rials' => 2_000_000,
        'office_share_percent' => 50.0,
        'is_active' => true,
    ]);

    $this->ledger = app(LedgerService::class);
    $this->calculator = app(FeeSplitCalculator::class);
    $this->reportQuery = app(OfficeSettlementReport::class);
});

test('daily office payout amount strictly equals sum of ledger entries for that office in settlement period', function (): void {
    $targetDate = Carbon::yesterday();
    $periodStart = $targetDate->copy()->startOfDay();
    $periodEnd = $targetDate->copy()->endOfDay();

    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);
    $officePayable = $this->ledger->getOrCreateAccount(LedgerOwnerType::OFFICE, $this->office->id, LedgerAccountKind::PAYABLE);

    // Initial funding to wallet & escrow for 2 cases
    $this->ledger->recordTransaction(
        reference: 'topup_payout_test',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::CLEARING), LedgerDirection::DEBIT, 4_000_000),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, 4_000_000),
        ]
    );

    $this->ledger->recordTransaction(
        reference: 'fee_payout_cases',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 4_000_000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 4_000_000),
        ]
    );

    // Create 2 completed cases for this office
    $case1 = CaseRequest::create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'office_id' => $this->office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::COMPLETED,
        'tracking_code' => 'CR-1405-PAY01',
        'fee_paid_rials' => 2_000_000,
        'office_share_rials' => 1_000_000,
        'platform_share_rials' => 1_000_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    $case2 = CaseRequest::create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'office_id' => $this->office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::COMPLETED,
        'tracking_code' => 'CR-1405-PAY02',
        'fee_paid_rials' => 2_000_000,
        'office_share_rials' => 1_000_000,
        'platform_share_rials' => 1_000_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    // Settle both cases using SettleCaseFeeJob
    (new SettleCaseFeeJob($case1->id))->handle($this->ledger, $this->calculator);
    (new SettleCaseFeeJob($case2->id))->handle($this->ledger, $this->calculator);

    // Backdate transaction timestamps so they fall in target period
    DB::table('ledger_transactions')
        ->whereIn('case_id', [$case1->id, $case2->id])
        ->update(['created_at' => $targetDate->copy()->setHour(14)]);

    // Run GenerateOfficePayoutsCommand for target date
    $exitCode = Artisan::call('payments:generate-office-payouts', [
        '--date' => $targetDate->toDateString(),
        '--office' => $this->office->id,
    ]);

    expect($exitCode)->toBe(0);

    // Query generated payout
    /** @var Payout|null $payout */
    $payout = Payout::query()
        ->where('office_id', $this->office->id)
        ->where('period_start', $periodStart)
        ->first();

    expect($payout)->not->toBeNull()
        ->and($payout->amount_rials)->toBe(2_000_000)
        ->and($payout->total_cases_count)->toBe(2)
        ->and($payout->status)->toBe(PayoutStatus::PENDING)
        ->and($payout->reference_number)->toBe('PAYOUT-'.$this->office->code.'-'.$periodStart->format('Ymd'));

    // Verify exact equality between payout amount and office payable ledger credits
    $ledgerCredits = (int) DB::table('ledger_entries')
        ->join('ledger_transactions', 'ledger_entries.transaction_id', '=', 'ledger_transactions.id')
        ->where('ledger_entries.account_id', $officePayable->id)
        ->where('ledger_entries.direction', LedgerDirection::CREDIT->value)
        ->whereBetween('ledger_transactions.created_at', [$periodStart, $periodEnd])
        ->sum('ledger_entries.amount_rials');

    expect($payout->amount_rials)->toBe($ledgerCredits);

    // Verify audit log entry
    $audit = DB::table('audit_logs')
        ->where('action', AuditableAction::PAYOUT_GENERATED->value)
        ->where('subject_id', $payout->id)
        ->first();

    expect($audit)->not->toBeNull();
    $changes = json_decode((string) $audit->changes, true);
    expect($changes['amount_rials'])->toBe(2_000_000)
        ->and($changes['office_id'])->toBe($this->office->id);
});

test('idempotency: running GenerateOfficePayoutsCommand multiple times does not create duplicate payouts', function (): void {
    $targetDate = Carbon::yesterday();

    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);

    $this->ledger->recordTransaction(
        reference: 'topup_idem_payout',
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($this->ledger->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::CLEARING), LedgerDirection::DEBIT, 1_000_000),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, 1_000_000),
        ]
    );

    $this->ledger->recordTransaction(
        reference: 'fee_idem_payout',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 1_000_000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 1_000_000),
        ]
    );

    $case = CaseRequest::create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'office_id' => $this->office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::COMPLETED,
        'tracking_code' => 'CR-1405-IDEMP01',
        'fee_paid_rials' => 1_000_000,
        'office_share_rials' => 500_000,
        'platform_share_rials' => 500_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    (new SettleCaseFeeJob($case->id))->handle($this->ledger, $this->calculator);

    DB::table('ledger_transactions')
        ->where('case_id', $case->id)
        ->update(['created_at' => $targetDate->copy()->setHour(12)]);

    // Run first time
    Artisan::call('payments:generate-office-payouts', [
        '--date' => $targetDate->toDateString(),
        '--office' => $this->office->id,
    ]);

    $payoutCountFirst = Payout::query()->where('office_id', $this->office->id)->count();
    expect($payoutCountFirst)->toBe(1);

    // Run second time (should be completely idempotent and skip)
    Artisan::call('payments:generate-office-payouts', [
        '--date' => $targetDate->toDateString(),
        '--office' => $this->office->id,
    ]);

    $payoutCountSecond = Payout::query()->where('office_id', $this->office->id)->count();
    expect($payoutCountSecond)->toBe(1);
});

test('critical alert: unbalanced manual ledger entry triggers LedgerDiscrepancyException and halts payouts', function (): void {
    Log::spy();

    $wallet = $this->ledger->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);

    // Intentionally corrupt ledger by inserting an unbalanced entry (single debit without matching credit)
    $txId = (string) Str::uuid();
    DB::table('ledger_transactions')->insert([
        'id' => $txId,
        'reference' => 'CORRUPT-UNBALANCED-TX',
        'type' => LedgerTransactionType::SERVICE_FEE->value,
        'description' => 'Unbalanced manual tampering test',
        'posted_at' => now(),
        'created_at' => now(),
    ]);

    DB::table('ledger_entries')->insert([
        'id' => (string) Str::uuid(),
        'transaction_id' => $txId,
        'account_id' => $wallet->id,
        'direction' => LedgerDirection::DEBIT->value,
        'amount_rials' => 999_999_999, // Unbalanced 999M debit
        'created_at' => now(),
    ]);

    // Running the report query must detect the double-entry imbalance and throw LedgerDiscrepancyException
    expect(fn () => $this->reportQuery->assertGlobalLedgerBalanced())
        ->toThrow(LedgerDiscrepancyException::class);

    // Running the Artisan command must also throw LedgerDiscrepancyException
    expect(fn () => Artisan::call('payments:generate-office-payouts'))
        ->toThrow(LedgerDiscrepancyException::class);

    // Verify critical alert was logged
    Log::shouldHaveReceived('critical')->atLeast()->once();
});

test('report query generates accurate settlement data matching double-entry ledger without loss', function (): void {
    $yesterday = Carbon::yesterday();
    $periodStart = $yesterday->copy()->startOfDay();
    $periodEnd = $yesterday->copy()->endOfDay();

    $report = $this->reportQuery->generate($this->office->id, $periodStart, $periodEnd);

    expect($report->officeId)->toBe($this->office->id)
        ->and($report->totalCasesCount)->toBe(0)
        ->and($report->periodCreditsRials)->toBe(0)
        ->and($report->discrepancyRials)->toBe(0)
        ->and($report->isOfficeLedgerConsistent)->toBeTrue()
        ->and($report->isGlobalLedgerBalanced)->toBeTrue();
});
