<?php

declare(strict_types=1);

use App\Modules\Consultation\Domain\Enums\AdvisorApplicationStatus;
use App\Modules\Consultation\Domain\Enums\ConsultationCategory;
use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Enums\RoleName;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

function createTestCitizen(string $mobile, string $nationalId, string $name): Citizen
{
    return Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', $mobile),
        'mobile_encrypted' => 'enc:'.$mobile,
        'national_id_hash' => hash('sha256', $nationalId),
        'national_id_encrypted' => 'enc:'.$nationalId,
        'full_name' => $name,
        'tier' => CitizenTier::BRONZE->value,
        'profile_completed' => true,
    ]);
}

function createSystemAdmin(): Operator
{
    $admin = new Operator;
    $admin->office_id = null;
    $admin->username = 'sysadmin_test';
    $admin->password_hash = Hash::make('Secret123!');
    $admin->full_name = 'مدیر ارشد سامانه';
    $admin->national_id = '0011223344';
    $admin->mobile = '09121112233';
    $admin->counter_number = 1;
    $admin->role = OperatorRole::MANAGER;
    $admin->is_active = true;
    $admin->save();
    $admin->assignRole(RoleName::SYSTEM_ADMIN->value);

    return $admin;
}

it('verifies non-approved advisor is never listed in public endpoints (§5.3, §6.1, TASK-118, TASK-118-T)', function (): void {
    $citizen = createTestCitizen('09123450001', '0012345001', 'داوطلب مشاور');

    $advisor = Advisor::query()->create([
        'citizen_id' => $citizen->id,
        'display_name' => 'مهدی نصیری',
        'title' => 'مشاور بیمه',
        'category' => ConsultationCategory::InsuranceLabor,
        'license_number' => 'LIC-INS-001',
        'application_status' => AdvisorApplicationStatus::Pending,
        'is_verified' => false,
    ]);

    // Public list should NOT include pending advisor
    $res = $this->getJson('/api/v1/advisors')
        ->assertStatus(200);

    $items = $res->json('data');
    expect($items)->toBeEmpty();

    // Public show should 404 for unapproved advisor
    $this->getJson("/api/v1/advisors/{$advisor->id}")
        ->assertStatus(404);
});

it('verifies citizen can submit advisor application via POST /advisors/apply (TASK-118, TASK-118-T)', function (): void {
    $citizen = createTestCitizen('09123450002', '0012345002', 'متقاضی جدید');
    Sanctum::actingAs($citizen, ['*']);

    $payload = [
        'display_name' => 'دکتر احمد کاظمی',
        'title' => 'متخصص امور مالیاتی',
        'category' => 'tax',
        'license_number' => 'LIC-TAX-1405-99',
        'experience_years' => 10,
        'price_text_chat_rials' => 2500000,
        'price_phone_per_minute_rials' => 200000,
        'price_deep_review_rials' => 8000000,
        'bio' => 'سابقه ۱۰ سال مشاوره ارشد در حوزه مالیات مشاغل و اشخاص حقوقی',
        'specialties' => ['مالیات عملکرد', 'ماده ۱۰۰', 'اعتراضات مالیاتی'],
        'credentials_badge' => 'عضو جامعه حسابداران رسمی',
    ];

    $response = $this->postJson('/api/v1/advisors/apply', $payload)
        ->assertStatus(201)
        ->assertJsonPath('data.display_name', 'دکتر احمد کاظمی')
        ->assertJsonPath('data.category', 'tax')
        ->assertJsonPath('data.application_status', 'pending')
        ->assertJsonPath('data.is_verified', false);

    $advisorId = $response->json('data.id');
    $advisor = Advisor::query()->with('specialties')->find($advisorId);

    expect($advisor)->not->toBeNull()
        ->and($advisor->application_status)->toBe(AdvisorApplicationStatus::Pending)
        ->and($advisor->specialties)->toHaveCount(3);
});

it('verifies Invariant §5.3: duplicate license number is rejected (TASK-118, TASK-118-T)', function (): void {
    $citizen1 = createTestCitizen('09123450003', '0012345003', 'مشاور اول');
    $citizen2 = createTestCitizen('09123450004', '0012345004', 'مشاور دوم');

    Advisor::query()->create([
        'citizen_id' => $citizen1->id,
        'display_name' => 'مشاور ثبت شده',
        'title' => 'مشاور حقوقی',
        'category' => ConsultationCategory::LegalRegistry,
        'license_number' => 'DUPLICATE-LIC-100',
        'application_status' => AdvisorApplicationStatus::Approved,
        'is_verified' => true,
    ]);

    Sanctum::actingAs($citizen2, ['*']);

    $payload = [
        'display_name' => 'مشاور متقلب',
        'title' => 'مشاور ثبتی',
        'category' => 'legal_registry',
        'license_number' => 'DUPLICATE-LIC-100', // DUPLICATE
        'experience_years' => 5,
        'price_text_chat_rials' => 1000000,
        'price_phone_per_minute_rials' => 100000,
        'price_deep_review_rials' => 3000000,
        'bio' => 'توضیحات کوتاه',
        'specialties' => ['ثبت شرکت'],
    ];

    $this->postJson('/api/v1/advisors/apply', $payload)
        ->assertStatus(422)
        ->assertJsonPath('detail', 'شماره پروانه وارد شده قبلاً در سامانه ثبت شده است.');
});

it('verifies only system_admin can approve advisor, which grants advisor role and records audit log (§5.3, §7.3, §7.6, TASK-118, TASK-118-T)', function (): void {
    $citizen = createTestCitizen('09123450005', '0012345005', 'متقاضی تأیید');
    $regularCitizen = createTestCitizen('09123450006', '0012345006', 'شهروند عادی');

    $advisor = Advisor::query()->create([
        'citizen_id' => $citizen->id,
        'display_name' => 'استاد جلالی',
        'title' => 'مشاور شهرداری و ساخت‌وساز',
        'category' => ConsultationCategory::Municipal,
        'license_number' => 'LIC-MUN-2026',
        'application_status' => AdvisorApplicationStatus::Pending,
        'is_verified' => false,
    ]);

    expect($citizen->hasRole(RoleName::ADVISOR->value))->toBeFalse();

    // Regular citizen cannot approve
    Sanctum::actingAs($regularCitizen, ['*']);
    $this->postJson("/api/v1/admin/advisors/{$advisor->id}/approve")
        ->assertStatus(403);

    // System Admin approves
    $admin = createSystemAdmin();
    Sanctum::actingAs($admin, ['*']);

    $this->postJson("/api/v1/admin/advisors/{$advisor->id}/approve")
        ->assertStatus(200)
        ->assertJsonPath('data.application_status', 'approved')
        ->assertJsonPath('data.is_verified', true);

    $advisor->refresh();
    $citizen->refresh();

    // Verify Invariant §5.3 & §7.3:
    expect($advisor->application_status)->toBe(AdvisorApplicationStatus::Approved)
        ->and($advisor->is_verified)->toBeTrue()
        ->and($citizen->hasRole(RoleName::ADVISOR->value))->toBeTrue();

    // Verify Audit Log was recorded (§7.6)
    $audit = DB::table('audit_logs')
        ->where('action', 'advisor.approved')
        ->where('subject_id', $advisor->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($admin->id);

    // Now advisor appears in public listing
    $this->getJson('/api/v1/advisors')
        ->assertStatus(200)
        ->assertJsonPath('data.0.id', $advisor->id)
        ->assertJsonPath('data.0.display_name', 'استاد جلالی');
});
