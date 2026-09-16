<?php

declare(strict_types=1);

namespace Tests\Feature\OfficeNetwork;

use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Application\Queries\OfficeFinder;
use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeAnnouncement;
use App\Modules\OfficeNetwork\Domain\Models\OfficeServiceCoverage;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSpecialty;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Shared\Audit\AuditableAction;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    Role::firstOrCreate(['name' => 'office_operator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'office_manager', 'guard_name' => 'web']);

    $this->officeA = Office::query()->create([
        'code' => '9101',
        'name' => 'دفتر پیشخوان دولت شریعتی',
        'is_online' => true,
        'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE->value,
        'province_code' => 'THR',
        'city' => 'تهران',
        'location' => '35.7500,51.4200',
        'address' => 'تهران، خیابان شریعتی، پلاک ۱۰۰',
        'phone' => '02122334455',
        'active_counters' => 3,
        'current_waiting_queue' => 0,
        'sla_score' => 100.00,
        'rating' => 4.8,
        'review_count' => 15,
    ]);

    $this->category1 = ServiceCategory::query()->create([
        'id' => 'cat-civil-reg',
        'title' => 'خدمات ثبت احوال و هویت',
        'short_title' => 'هویتی',
        'icon_name' => 'user-check',
        'is_active' => true,
    ]);

    $this->category2 = ServiceCategory::query()->create([
        'id' => 'cat-taxation',
        'title' => 'خدمات مالیاتی و اصناف',
        'short_title' => 'مالیاتی',
        'icon_name' => 'receipt',
        'is_active' => true,
    ]);

    $this->managerA = new Operator;
    $this->managerA->office_id = $this->officeA->id;
    $this->managerA->username = 'mgr_shariati';
    $this->managerA->password_hash = Hash::make('Secret123!');
    $this->managerA->full_name = 'سهراب مرادی';
    $this->managerA->national_id = '0089876541';
    $this->managerA->mobile = '09123456781';
    $this->managerA->counter_number = 1;
    $this->managerA->role = OperatorRole::MANAGER;
    $this->managerA->is_active = true;
    $this->managerA->save();
    $this->managerA->assignRole('office_manager');

    $this->operatorA = new Operator;
    $this->operatorA->office_id = $this->officeA->id;
    $this->operatorA->username = 'op_shariati';
    $this->operatorA->password_hash = Hash::make('Secret123!');
    $this->operatorA->full_name = 'کیوان بهرامی';
    $this->operatorA->national_id = '0089876542';
    $this->operatorA->mobile = '09123456782';
    $this->operatorA->counter_number = 2;
    $this->operatorA->role = OperatorRole::OPERATOR;
    $this->operatorA->is_active = true;
    $this->operatorA->save();
    $this->operatorA->assignRole('office_operator');
});

test('office_operator cannot access or modify office profile (403 forbidden) (TASK-105, §7.3, TASK-105-T)', function (): void {
    Sanctum::actingAs($this->operatorA, ['*']);

    $this->getJson('/api/v1/desk/profile')
        ->assertStatus(403)
        ->assertJsonPath('code', 'FORBIDDEN_NOT_OFFICE_MANAGER');

    $this->patchJson('/api/v1/desk/profile/info', ['phone' => '02199887766'])
        ->assertStatus(403)
        ->assertJsonPath('code', 'FORBIDDEN_NOT_OFFICE_MANAGER');

    $this->putJson('/api/v1/desk/profile/coverages', ['coverages' => []])
        ->assertStatus(403)
        ->assertJsonPath('code', 'FORBIDDEN_NOT_OFFICE_MANAGER');

    $this->postJson('/api/v1/desk/profile/operators', [
        'username' => 'new_op',
        'full_name' => 'کاربر جدید',
        'counter_number' => 3,
        'password' => 'Secret123!',
    ])->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN_NOT_OFFICE_MANAGER');
});

test('office_manager can view full office profile, update info, and audit is recorded (TASK-105, §4.4, §7.3, TASK-105-T)', function (): void {
    Sanctum::actingAs($this->managerA, ['*']);

    $res = $this->getJson('/api/v1/desk/profile');
    $res->assertOk()
        ->assertJsonPath('data.office.name', 'دفتر پیشخوان دولت شریعتی')
        ->assertJsonPath('data.office.code', '9101');

    $updateRes = $this->patchJson('/api/v1/desk/profile/info', [
        'phone' => '02188776655',
        'address' => 'تهران، خیابان شریعتی، روبروی مترو، پلاک ۱۲۰',
        'active_counters' => 5,
    ]);

    $updateRes->assertOk();
    $this->officeA->refresh();
    expect($this->officeA->phone)->toBe('02188776655')
        ->and($this->officeA->active_counters)->toBe(5);

    // Verify audit log
    $audit = DB::table('audit_logs')
        ->where('action', AuditableAction::OFFICE_PROFILE_UPDATED->value)
        ->latest('created_at')
        ->first();

    expect($audit)->not->toBeNull();
});

test('office_manager updating category coverages immediately impacts OfficeFinder (TASK-105, §6.1, §7.3, TASK-105-T)', function (): void {
    $finder = app(OfficeFinder::class);

    // Initial check: Office has no coverage for category2, so nearby search for category2 should return empty
    $initialResults = $finder->findNearby(35.7500, 51.4200, 10.0, (string) $this->category2->id, 10, true);
    expect($initialResults)->toBeEmpty();

    // Manager activates coverage for category2
    Sanctum::actingAs($this->managerA, ['*']);
    $covRes = $this->putJson('/api/v1/desk/profile/coverages', [
        'coverages' => [
            [
                'category_id' => (string) $this->category2->id,
                'is_active' => true,
                'daily_capacity' => 150,
            ],
        ],
    ]);
    $covRes->assertOk();

    // Query finder again for category2 -> Office A MUST appear immediately!
    $updatedResults = $finder->findNearby(35.7500, 51.4200, 10.0, (string) $this->category2->id, 10, true);
    expect($updatedResults)->not->toBeEmpty()
        ->and($updatedResults[0]['office']->id)->toBe($this->officeA->id);

    // Deactivate coverage
    $this->putJson('/api/v1/desk/profile/coverages', [
        'coverages' => [
            [
                'category_id' => (string) $this->category2->id,
                'is_active' => false,
                'daily_capacity' => 0,
            ],
        ],
    ])->assertOk();

    $deactivatedResults = $finder->findNearby(35.7500, 51.4200, 10.0, (string) $this->category2->id, 10, true);
    expect($deactivatedResults)->toBeEmpty();

    // Verify audit log
    $audit = DB::table('audit_logs')
        ->where('action', AuditableAction::OFFICE_COVERAGES_UPDATED->value)
        ->latest('created_at')
        ->first();

    expect($audit)->not->toBeNull();
});

test('office_manager can manage specialties, announcements, and operators with audit logs (TASK-105, §4.4, §7.3, TASK-105-T)', function (): void {
    Sanctum::actingAs($this->managerA, ['*']);

    // 1. Specialties
    $specRes = $this->putJson('/api/v1/desk/profile/specialties', [
        'specialties' => ['کارت هوشمند ملی', 'گذرنامه فوری', 'امور مالیاتی اصناف'],
    ]);
    $specRes->assertOk();
    expect(OfficeSpecialty::query()->where('office_id', $this->officeA->id)->count())->toBe(3);

    // 2. Announcements
    $annRes = $this->postJson('/api/v1/desk/profile/announcements', [
        'title' => 'قطعی موقت سامانه ثبت اسناد',
        'content' => 'به دلیل بروزرسانی سراسری سرورها، باجه شماره ۱ تا ساعت ۱۲ ارائه خدمت ندارد.',
        'priority' => 'important',
    ]);
    $annRes->assertStatus(201);
    $annId = $annRes->json('data.id');

    $this->deleteJson("/api/v1/desk/profile/announcements/{$annId}")->assertOk();
    expect(OfficeAnnouncement::query()->find($annId))->toBeNull();

    // 3. Operators
    $opRes = $this->postJson('/api/v1/desk/profile/operators', [
        'username' => 'op_new_counter',
        'full_name' => 'مهدی کاظمی',
        'counter_number' => 4,
        'password' => 'Password1234!',
        'role' => 'operator',
    ]);
    $opRes->assertStatus(201);
    $newOpId = $opRes->json('data.id');

    $toggleRes = $this->postJson("/api/v1/desk/profile/operators/{$newOpId}/toggle");
    $toggleRes->assertOk()->assertJsonPath('data.is_active', false);

    // Verify all audit logs exist
    $actions = [
        AuditableAction::OFFICE_SPECIALTIES_UPDATED->value,
        AuditableAction::OFFICE_ANNOUNCEMENT_CREATED->value,
        AuditableAction::OFFICE_ANNOUNCEMENT_DELETED->value,
        AuditableAction::OPERATOR_CREATED->value,
        AuditableAction::OPERATOR_DISABLED->value,
    ];

    foreach ($actions as $act) {
        expect(DB::table('audit_logs')->where('action', $act)->exists())->toBeTrue();
    }
});
