<?php

declare(strict_types=1);

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\Payments\Domain\Enums\LedgerAccountKind;
use App\Modules\Payments\Domain\Enums\LedgerDirection;
use App\Modules\Payments\Domain\Enums\LedgerOwnerType;
use App\Modules\Payments\Domain\Enums\LedgerTransactionType;
use App\Modules\Payments\Domain\Enums\PayoutStatus;
use App\Modules\Payments\Domain\LedgerEntryData;
use App\Modules\Payments\Domain\LedgerService;
use App\Modules\Payments\Domain\Models\Payout;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Carbon\Carbon;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->seed(ProvinceSeeder::class);

    $this->officeA = Office::query()->create([
        'code' => '1001',
        'name' => 'دفتر پیشخوان بهارستان',
        'is_online' => true,
    ]);

    $this->officeB = Office::query()->create([
        'code' => '1002',
        'name' => 'دفتر پیشخوان صادقیه',
        'is_online' => true,
    ]);

    // Operator in Office A (not a manager)
    $this->operatorA = Operator::query()->create([
        'office_id' => $this->officeA->id,
        'username' => 'operator_a',
        'password_hash' => 'hash_op',
        'national_id_hash' => hash_hmac('sha256', '0011111111', 'pepper'),
        'mobile_hash' => hash_hmac('sha256', '09121111111', 'pepper'),
        'full_name' => 'اپراتور باجه الف',
        'role' => OperatorRole::OPERATOR,
        'counter_number' => 1,
    ]);
    $this->operatorA->assignRole('office_operator');

    // Manager in Office A
    $this->managerA = Operator::query()->create([
        'office_id' => $this->officeA->id,
        'username' => 'manager_a',
        'password_hash' => 'hash_mgr',
        'national_id_hash' => hash_hmac('sha256', '0022222222', 'pepper'),
        'mobile_hash' => hash_hmac('sha256', '09122222222', 'pepper'),
        'full_name' => 'مدیر دفتر الف',
        'role' => OperatorRole::MANAGER,
        'counter_number' => 2,
    ]);
    $this->managerA->assignRole('office_manager');

    // Manager in Office B
    $this->managerB = Operator::query()->create([
        'office_id' => $this->officeB->id,
        'username' => 'manager_b',
        'password_hash' => 'hash_mgr_b',
        'national_id_hash' => hash_hmac('sha256', '0033333333', 'pepper'),
        'mobile_hash' => hash_hmac('sha256', '09123333333', 'pepper'),
        'full_name' => 'مدیر دفتر ب',
        'role' => OperatorRole::MANAGER,
        'counter_number' => 1,
    ]);
    $this->managerB->assignRole('office_manager');
});

test('unauthenticated request to /desk/finance returns 401', function (): void {
    $response = $this->getJson('/api/v1/desk/finance');

    $response->assertStatus(401);
});

test('office_operator receives 403 FORBIDDEN when accessing /desk/finance', function (): void {
    $response = $this->actingAs($this->operatorA, 'operator')
        ->getJson('/api/v1/desk/finance');

    $response->assertStatus(403)
        ->assertJsonPath('code', 'FORBIDDEN_NOT_OFFICE_MANAGER')
        ->assertJsonPath('detail', 'دسترسی غیرمجاز — این بخش فقط مختص مدیر دفتر است.');
});

test('office_manager receives 200 OK and accurate finance data for own office', function (): void {
    /** @var LedgerService $ledger */
    $ledger = app(LedgerService::class);

    $officeAccount = $ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::OFFICE,
        ownerId: $this->officeA->id,
        kind: LedgerAccountKind::PAYABLE,
        currency: 'IRR'
    );

    $platformAccount = $ledger->getOrCreateAccount(
        ownerType: LedgerOwnerType::PLATFORM,
        ownerId: null,
        kind: LedgerAccountKind::REVENUE,
        currency: 'IRR'
    );

    ServiceCategory::query()->firstOrCreate(
        ['id' => 'identity'],
        ['title' => 'سجلی', 'slug' => 'identity', 'icon' => 'card', 'display_order' => 1]
    );

    $service = Service::query()->create([
        'id' => 'svc_finance_test',
        'category_id' => 'identity',
        'slug' => 'svc-finance-test',
        'title' => 'خدمت تست مالی',
        'description' => 'تست مالی',
        'tags' => ['in-person'],
        'estimated_days_min' => 3,
        'estimated_days_max' => 7,
        'fee_rials' => 5_000_000,
        'office_share_percent' => 50.0,
        'is_active' => true,
    ]);

    $citizen = new Citizen;
    $citizen->national_id = '0010350802';
    $citizen->mobile = '09121112233';
    $citizen->full_name = 'شهروند تست مالی';
    $citizen->tier = CitizenTier::BRONZE;
    $citizen->province_code = 'THR';
    $citizen->save();

    $case = CaseRequest::query()->create([
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'office_id' => $this->officeA->id,
        'province_code' => 'THR',
        'status' => CaseStatus::COMPLETED,
        'tracking_code' => 'CR-1405-FIN01',
        'fee_paid_rials' => 5_000_000,
        'office_share_rials' => 2_500_000,
        'platform_share_rials' => 2_500_000,
        'delivery_mode' => 'in_person',
        'metadata' => [],
    ]);

    // Record a balanced settlement transaction
    $ledger->recordTransaction(
        reference: 'SETTLE-CR-1405-FIN01',
        type: LedgerTransactionType::SERVICE_FEE,
        entries: [
            new LedgerEntryData($officeAccount, LedgerDirection::CREDIT, 2_500_000),
            new LedgerEntryData($platformAccount, LedgerDirection::DEBIT, 2_500_000),
        ],
        caseId: $case->id
    );

    // Create a past payout for Office A
    Payout::query()->create([
        'office_id' => $this->officeA->id,
        'amount_rials' => 10_000_000,
        'status' => PayoutStatus::COMPLETED,
        'period_start' => Carbon::now()->subDays(5),
        'period_end' => Carbon::now()->subDays(4),
        'total_cases_count' => 12,
        'reference_number' => 'PAYOUT-REF-1001',
        'generated_at' => Carbon::now()->subDays(4),
        'processed_at' => Carbon::now()->subDays(4),
    ]);

    $response = $this->actingAs($this->managerA, 'operator')
        ->getJson('/api/v1/desk/finance');

    $response->assertStatus(200)
        ->assertJsonPath('data.office.id', $this->officeA->id)
        ->assertJsonPath('data.office.code', '1001')
        ->assertJsonPath('data.office.name', 'دفتر پیشخوان بهارستان')
        ->assertJsonPath('data.payable_balance_rials', 2_500_000)
        ->assertJsonPath('data.ledger_integrity.is_global_ledger_balanced', true)
        ->assertJsonPath('data.ledger_integrity.discrepancy_rials', 0)
        ->assertJsonCount(1, 'data.recent_payouts')
        ->assertJsonPath('data.recent_payouts.0.amount_rials', 10_000_000)
        ->assertJsonPath('data.recent_payouts.0.reference_number', 'PAYOUT-REF-1001');
});

test('manager of office B receives finance data only for office B and not office A', function (): void {
    $response = $this->actingAs($this->managerB, 'operator')
        ->getJson('/api/v1/desk/finance');

    $response->assertStatus(200)
        ->assertJsonPath('data.office.id', $this->officeB->id)
        ->assertJsonPath('data.office.code', '1002')
        ->assertJsonPath('data.office.name', 'دفتر پیشخوان صادقیه')
        ->assertJsonPath('data.payable_balance_rials', 0);
});
