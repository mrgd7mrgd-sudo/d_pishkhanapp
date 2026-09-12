<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\ReturnReasonCode;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseReturn;
use App\Modules\CaseWorkflow\Domain\SlaClock;
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
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\ReturnReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(null);
    CarbonImmutable::setTestNow(null);
    $this->seed(ProvinceSeeder::class);
    $this->seed(ReturnReasonSeeder::class);
    Cache::flush();

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
        'fee_rials' => 500_000,
        'estimated_days_max' => 2,
        'tags' => ['in-person'],
    ]);

    $this->ledgerService = App::make(LedgerService::class);
    $this->slaClock = App::make(SlaClock::class);
});

afterEach(function (): void {
    Carbon::setTestNow(null);
    CarbonImmutable::setTestNow(null);
});

/**
 * Helper to seed a case in action_required state with escrow ledger entries.
 */
function createActionRequiredCase(Citizen $citizen, Service $service, LedgerService $ledgerService, CarbonImmutable $createdAt): CaseRequest
{
    $case = CaseRequest::query()->create([
        'tracking_code' => 'CR-1405-'.Str::random(5),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::ACTION_REQUIRED,
        'turn_owner' => TurnOwner::CITIZEN,
        'current_step' => 2,
        'total_steps' => 6,
        'fee_paid_rials' => 500_000,
        'office_share_rials' => 400_000,
        'platform_share_rials' => 100_000,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);

    CaseReturn::query()->create([
        'id' => (string) Str::uuid(),
        'case_id' => $case->id,
        'reason_code' => ReturnReasonCode::DOC_BLUR,
        'operator_note' => 'تصویر شناسنامه تار است',
        'deadline_at' => $createdAt->addHours(72),
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);

    // Setup initial ledger state: Citizen tops up wallet 1,000,000 rials, then pays 500,000 rials into Escrow
    $wallet = $ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $citizen->id, LedgerAccountKind::WALLET);
    $escrow = $ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);
    $clearing = $ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::CLEARING);

    $ledgerService->recordTransaction(
        reference: 'TOPUP-'.$case->tracking_code,
        type: LedgerTransactionType::TOPUP,
        entries: [
            new LedgerEntryData($clearing, LedgerDirection::DEBIT, 1_000_000),
            new LedgerEntryData($wallet, LedgerDirection::CREDIT, 1_000_000),
        ],
        description: 'شارژ کیف پول شهروند'
    );

    $ledgerService->recordTransaction(
        reference: 'INIT-PAY-'.$case->tracking_code,
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($wallet, LedgerDirection::DEBIT, 500_000),
            new LedgerEntryData($escrow, LedgerDirection::CREDIT, 500_000),
        ],
        description: 'پرداخت اولیه پرونده'
    );

    return $case;
}

it('cancels action_required case after 73 hours and posts partial ledger refund (DoD)', function (): void {
    $t0 = CarbonImmutable::parse('2026-09-08 10:00:00');
    Carbon::setTestNow($t0);
    CarbonImmutable::setTestNow($t0);

    $case = createActionRequiredCase($this->citizen, $this->service, $this->ledgerService, $t0);

    // Verify initial state
    expect($case->status)->toBe(CaseStatus::ACTION_REQUIRED)
        ->and($this->slaClock->isActionRequiredExpired($case))->toBeFalse();

    // Fast-forward 73 hours
    $t73 = $t0->addHours(73);
    Carbon::setTestNow($t73);
    CarbonImmutable::setTestNow($t73);

    expect($this->slaClock->isActionRequiredExpired($case))->toBeTrue();

    // Run the scheduler command
    $this->artisan('pishkhan:expire-action-required-cases')
        ->assertExitCode(0);

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::CANCELLED)
        ->and($case->turn_owner)->toBe(TurnOwner::SYSTEM);

    // Verify partial ledger refund: 70% (350,000) to citizen wallet, 30% (150,000) to platform revenue
    $wallet = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::CITIZEN, $this->citizen->id, LedgerAccountKind::WALLET);
    $escrow = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);
    $revenue = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::REVENUE);

    $walletBalance = $this->ledgerService->getBalance($wallet);
    $escrowBalance = $this->ledgerService->getBalance($escrow);
    $revenueBalance = $this->ledgerService->getBalance($revenue);

    // Initial wallet was 1,000,000, debited 500,000 (balance 500,000), now credited 350,000 => balance is 850,000
    expect($walletBalance->getAmountRials())->toBe(850_000)
        ->and($escrowBalance->getAmountRials())->toBe(0) // Fully cleared
        ->and($revenueBalance->getAmountRials())->toBe(150_000); // 30% retained fee

    // Verify two-sided double entry invariant: SUM(debit) == SUM(credit)
    $totalDebits = (int) DB::table('ledger_entries')->sum('debit');
    $totalCredits = (int) DB::table('ledger_entries')->sum('credit');
    expect($totalDebits)->toBe($totalCredits);
});

it('leaves case untouched when less than 72 hours have elapsed (e.g. 71 hours) (DoD)', function (): void {
    $t0 = CarbonImmutable::parse('2026-09-08 10:00:00');
    Carbon::setTestNow($t0);
    CarbonImmutable::setTestNow($t0);

    $case = createActionRequiredCase($this->citizen, $this->service, $this->ledgerService, $t0);

    // Fast-forward 71 hours (1 hour before deadline)
    $t71 = $t0->addHours(71);
    Carbon::setTestNow($t71);
    CarbonImmutable::setTestNow($t71);

    expect($this->slaClock->isActionRequiredExpired($case))->toBeFalse();

    // Run the scheduler command
    $this->artisan('pishkhan:expire-action-required-cases')
        ->assertExitCode(0);

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::ACTION_REQUIRED)
        ->and($case->turn_owner)->toBe(TurnOwner::CITIZEN);

    // Verify escrow balance is still full 500,000 (untouched)
    $escrow = $this->ledgerService->getOrCreateAccount(LedgerOwnerType::PLATFORM, null, LedgerAccountKind::ESCROW);
    expect($this->ledgerService->getBalance($escrow)->getAmountRials())->toBe(500_000);
});

it('enforces single execution via Redis distributed lock during concurrent runs (DoD)', function (): void {
    $t0 = CarbonImmutable::parse('2026-09-08 10:00:00');
    Carbon::setTestNow($t0);
    CarbonImmutable::setTestNow($t0);

    $case = createActionRequiredCase($this->citizen, $this->service, $this->ledgerService, $t0);

    $t73 = $t0->addHours(73);
    Carbon::setTestNow($t73);
    CarbonImmutable::setTestNow($t73);

    // Simulate another concurrent worker holding the distributed lock
    $lock = Cache::lock('lock:sched:expire_action_required_cases', 300);
    expect($lock->get())->toBeTrue();

    // Run command while lock is acquired by another instance
    $this->artisan('pishkhan:expire-action-required-cases')
        ->assertExitCode(0);

    // Case was NOT modified because second instance gracefully exited due to lock
    $case->refresh();
    expect($case->status)->toBe(CaseStatus::ACTION_REQUIRED);

    // Release lock and run again
    $lock->release();

    $this->artisan('pishkhan:expire-action-required-cases')
        ->assertExitCode(0);

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::CANCELLED);
});

it('calculates remaining seconds, breach status and dynamic deadline in SlaClock', function (): void {
    $now = CarbonImmutable::parse('2026-09-08 12:00:00');
    Carbon::setTestNow($now);
    CarbonImmutable::setTestNow($now);

    $case = CaseRequest::query()->create([
        'tracking_code' => 'CR-SLA-'.Str::random(5),
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'province_code' => 'THR',
        'status' => CaseStatus::SEARCHING_OFFICE,
        'turn_owner' => TurnOwner::SYSTEM,
        'current_step' => 1,
        'total_steps' => 6,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Service sla_hours is 48
    expect($this->slaClock->getRemainingSeconds($case))->toBe(48 * 3600)
        ->and($this->slaClock->isBreached($case))->toBeFalse();

    // Advance 10 hours
    Carbon::setTestNow($now->addHours(10));
    CarbonImmutable::setTestNow($now->addHours(10));
    expect($this->slaClock->getRemainingSeconds($case))->toBe(38 * 3600)
        ->and($this->slaClock->isBreached($case))->toBeFalse();

    // Advance 49 hours (breached)
    Carbon::setTestNow($now->addHours(49));
    CarbonImmutable::setTestNow($now->addHours(49));
    expect($this->slaClock->getRemainingSeconds($case))->toBe(0)
        ->and($this->slaClock->isBreached($case))->toBeTrue();
});
