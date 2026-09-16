<?php

declare(strict_types=1);

namespace Tests\Feature\OfficeNetwork;

use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentAttendance;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentCompletion;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentReminderType;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentStatus;
use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Shared\Audit\AuditableAction;
use Carbon\Carbon;
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
        'code' => '8001',
        'name' => 'دفتر پیشخوان انقلاب',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
        'active_counters' => 2,
        'working_hours' => [
            'start' => '08:00',
            'end' => '18:00',
            'slot_capacity' => 2,
        ],
    ]);

    $this->officeB = Office::query()->create([
        'code' => '8002',
        'name' => 'دفتر پیشخوان آزادی',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
        'active_counters' => 2,
        'working_hours' => [
            'start' => '08:00',
            'end' => '18:00',
            'slot_capacity' => 2,
        ],
    ]);

    $this->operatorA = new Operator;
    $this->operatorA->office_id = $this->officeA->id;
    $this->operatorA->username = 'op_enqelab';
    $this->operatorA->password_hash = Hash::make('Secret123!');
    $this->operatorA->full_name = 'محمد رضایی';
    $this->operatorA->national_id = '0081234571';
    $this->operatorA->mobile = '09121110011';
    $this->operatorA->role = OperatorRole::OPERATOR;
    $this->operatorA->counter_number = 1;
    $this->operatorA->is_active = true;
    $this->operatorA->save();
    $this->operatorA->assignRole('office_operator');

    $this->operatorB = new Operator;
    $this->operatorB->office_id = $this->officeB->id;
    $this->operatorB->username = 'op_azadi';
    $this->operatorB->password_hash = Hash::make('Secret123!');
    $this->operatorB->full_name = 'رضا کریمی';
    $this->operatorB->national_id = '0081234572';
    $this->operatorB->mobile = '09121110012';
    $this->operatorB->role = OperatorRole::OPERATOR;
    $this->operatorB->counter_number = 2;
    $this->operatorB->is_active = true;
    $this->operatorB->save();
    $this->operatorB->assignRole('office_operator');

    $this->category = ServiceCategory::query()->create([
        'id' => 'cat-appt-attendance',
        'title' => 'خدمات سجلی و احراز هویت',
    ]);

    $this->service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $this->category->id,
        'title' => 'صدور کارت ملی هوشمند',
        'slug' => 'national-id-card-issue',
        'description' => 'مراجعه حضوری جهت انگشت‌نگاری و تصویربرداری چهره',
        'tags' => ['in-person', 'identity'],
        'fee_rials' => 250000,
        'office_share_percent' => 70,
        'is_active' => true,
    ]);

    $this->citizen = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09121113344'),
        'mobile_encrypted' => 'enc:09121113344',
        'national_id_hash' => hash('sha256', '0010350855'),
        'national_id_encrypted' => 'enc:0010350855',
        'full_name' => 'امیرحسین مرادی',
        'tier' => CitizenTier::BRONZE->value,
        'profile_completed' => true,
    ]);

    $this->appointmentDate = Carbon::now('Asia/Tehran')->addDay()->format('Y-m-d');

    $this->appointmentA = Appointment::query()->create([
        'id' => (string) Str::uuid(),
        'citizen_id' => $this->citizen->id,
        'office_id' => $this->officeA->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->appointmentDate,
        'time_slot' => '09:00 - 09:30',
        'tracking_code' => 'APT-2026-001',
        'status' => AppointmentStatus::Active,
        'attendance' => AppointmentAttendance::Pending,
        'completion' => AppointmentCompletion::Pending,
        'completion_reason' => null,
        'queue_number' => '1',
        'counter_number' => 0,
        'reminder_enabled' => true,
        'reminder_type' => AppointmentReminderType::All,
    ]);
});

test('GET /desk/appointments returns office appointments and supports filtering (TASK-100, §5.3)', function (): void {
    Sanctum::actingAs($this->operatorA, ['*']);

    // Create a second appointment in Office A
    Appointment::query()->create([
        'id' => (string) Str::uuid(),
        'citizen_id' => $this->citizen->id,
        'office_id' => $this->officeA->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->appointmentDate,
        'time_slot' => '09:30 - 10:00',
        'tracking_code' => 'APT-2026-002',
        'status' => AppointmentStatus::Active,
        'attendance' => AppointmentAttendance::Attended,
        'completion' => AppointmentCompletion::InProgress,
        'queue_number' => '2',
        'counter_number' => 1,
        'reminder_enabled' => true,
        'reminder_type' => AppointmentReminderType::All,
    ]);

    // Create an appointment in Office B (must NOT be visible to operator A)
    Appointment::query()->create([
        'id' => (string) Str::uuid(),
        'citizen_id' => $this->citizen->id,
        'office_id' => $this->officeB->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->appointmentDate,
        'time_slot' => '09:00 - 09:30',
        'tracking_code' => 'APT-2026-099',
        'status' => AppointmentStatus::Active,
        'attendance' => AppointmentAttendance::Pending,
        'completion' => AppointmentCompletion::Pending,
        'queue_number' => '1',
        'counter_number' => 2,
        'reminder_enabled' => true,
        'reminder_type' => AppointmentReminderType::All,
    ]);

    // 1. List without filters returns only Office A appointments (2 total)
    $response = $this->getJson('/api/v1/desk/appointments');
    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.office_id', $this->officeA->id);

    // 2. Filter by attendance = attended
    $filterAttended = $this->getJson('/api/v1/desk/appointments?attendance=attended');
    $filterAttended->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.tracking_code', 'APT-2026-002');

    // 3. Filter by search = APT-2026-001
    $searchRes = $this->getJson('/api/v1/desk/appointments?q=APT-2026-001');
    $searchRes->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.queue_number', '1');
});

test('GET /desk/appointments/{id} returns details for same office and 404 for cross-office operator (TASK-100, §7.3)', function (): void {
    // 1. Operator A views Appointment A -> 200 OK with accurate prototype mapping
    Sanctum::actingAs($this->operatorA, ['*']);
    $response = $this->getJson("/api/v1/desk/appointments/{$this->appointmentA->id}");
    $response->assertOk()
        ->assertJsonPath('data.id', $this->appointmentA->id)
        ->assertJsonPath('data.queue_number', '1')
        ->assertJsonPath('data.ticket_number', '1')
        ->assertJsonPath('data.tracking_code', 'APT-2026-001')
        ->assertJsonPath('data.citizen_name', 'امیرحسین مرادی')
        ->assertJsonPath('data.service_title', 'صدور کارت ملی هوشمند')
        ->assertJsonPath('data.service_category', 'خدمات سجلی و احراز هویت')
        ->assertJsonPath('data.attendance', 'pending')
        ->assertJsonPath('data.completion', 'pending');

    // 2. Operator B views Appointment A -> 404 NOT FOUND (horizontal isolation)
    Sanctum::actingAs($this->operatorB, ['*']);
    $crossResponse = $this->getJson("/api/v1/desk/appointments/{$this->appointmentA->id}");
    $crossResponse->assertNotFound();
});

test('POST /desk/appointments/{id}/attendance handles all 3 attendance states and logs audit (TASK-100, §5.3, §7.6)', function (): void {
    Sanctum::actingAs($this->operatorA, ['*']);

    // 1. State: attended with counter_number = 3
    $resAttended = $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/attendance", [
        'attendance' => 'attended',
        'counter_number' => 3,
    ]);

    $resAttended->assertOk()
        ->assertJsonPath('data.attendance', 'attended')
        ->assertJsonPath('data.attendance_label', 'حاضر در دفتر')
        ->assertJsonPath('data.counter_number', 3)
        ->assertJsonPath('data.status', 'active');

    $this->appointmentA->refresh();
    expect($this->appointmentA->attendance)->toBe(AppointmentAttendance::Attended)
        ->and($this->appointmentA->counter_number)->toBe(3)
        ->and($this->appointmentA->status)->toBe(AppointmentStatus::Active);

    // 2. State: pending
    $resPending = $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/attendance", [
        'attendance' => 'pending',
    ]);
    $resPending->assertOk()
        ->assertJsonPath('data.attendance', 'pending')
        ->assertJsonPath('data.attendance_label', 'در انتظار حضور');

    // 3. State: absent -> status transitions to completed
    $resAbsent = $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/attendance", [
        'attendance' => 'absent',
    ]);
    $resAbsent->assertOk()
        ->assertJsonPath('data.attendance', 'absent')
        ->assertJsonPath('data.attendance_label', 'عدم مراجعه (غایب)')
        ->assertJsonPath('data.status', 'completed');

    $this->appointmentA->refresh();
    expect($this->appointmentA->attendance)->toBe(AppointmentAttendance::Absent)
        ->and($this->appointmentA->status)->toBe(AppointmentStatus::Completed);

    // Verify all 3 audit logs in sequence
    $audits = DB::table('audit_logs')
        ->where('action', AuditableAction::APPOINTMENT_ATTENDANCE_UPDATED->value)
        ->where('subject_id', $this->appointmentA->id)
        ->get();

    expect($audits)->toHaveCount(3);

    // 1st audit: attended
    expect($audits[0]->actor_id)->toBe($this->operatorA->id)
        ->and(json_decode((string) $audits[0]->changes, true))->toMatchArray([
            'attendance' => 'attended',
            'counter_number' => 3,
            'status' => 'active',
        ]);

    // 2nd audit: pending
    expect(json_decode((string) $audits[1]->changes, true))->toMatchArray([
        'attendance' => 'pending',
    ]);

    // 3rd audit: absent
    expect(json_decode((string) $audits[2]->changes, true))->toMatchArray([
        'attendance' => 'absent',
        'status' => 'completed',
    ]);
});

test('POST /desk/appointments/{id}/completion handles all 4 completion states and enforces reason on not_completed (TASK-100, §5.3, §7.6)', function (): void {
    Sanctum::actingAs($this->operatorA, ['*']);

    // 1. State: in_progress
    $resProgress = $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/completion", [
        'completion' => 'in_progress',
    ]);
    $resProgress->assertOk()
        ->assertJsonPath('data.completion', 'in_progress')
        ->assertJsonPath('data.completion_label', 'در حال انجام خدمت')
        ->assertJsonPath('data.status', 'active');

    // 2. State: pending
    $resPending = $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/completion", [
        'completion' => 'pending',
    ]);
    $resPending->assertOk()
        ->assertJsonPath('data.completion', 'pending')
        ->assertJsonPath('data.status', 'active');

    // 3. State: not_completed without reason -> 422 Unprocessable Entity
    $resInvalid = $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/completion", [
        'completion' => 'not_completed',
    ]);
    $resInvalid->assertStatus(422)
        ->assertJsonValidationErrors(['completion_reason']);

    // 4. State: not_completed with reason -> 200 OK & status completed
    $resNotCompleted = $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/completion", [
        'completion' => 'not_completed',
        'completion_reason' => 'نقص مدارک هویتی و عدم همراه داشتن شناسنامه اصل',
    ]);
    $resNotCompleted->assertOk()
        ->assertJsonPath('data.completion', 'not_completed')
        ->assertJsonPath('data.completion_label', 'ناتمام / نیازمند پیگیری')
        ->assertJsonPath('data.completion_reason', 'نقص مدارک هویتی و عدم همراه داشتن شناسنامه اصل')
        ->assertJsonPath('data.status', 'completed');

    $this->appointmentA->refresh();
    expect($this->appointmentA->completion)->toBe(AppointmentCompletion::NotCompleted)
        ->and($this->appointmentA->status)->toBe(AppointmentStatus::Completed);

    // 5. State: completed -> 200 OK & status completed
    $resCompleted = $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/completion", [
        'completion' => 'completed',
    ]);
    $resCompleted->assertOk()
        ->assertJsonPath('data.completion', 'completed')
        ->assertJsonPath('data.completion_label', 'خدمت با موفقیت انجام شد')
        ->assertJsonPath('data.status', 'completed');

    $this->appointmentA->refresh();
    expect($this->appointmentA->completion)->toBe(AppointmentCompletion::Completed)
        ->and($this->appointmentA->status)->toBe(AppointmentStatus::Completed);

    // Verify all 4 audit logs in sequence
    $audits = DB::table('audit_logs')
        ->where('action', AuditableAction::APPOINTMENT_COMPLETION_UPDATED->value)
        ->where('subject_id', $this->appointmentA->id)
        ->get();

    expect($audits)->toHaveCount(4);

    // 1st audit: in_progress
    expect(json_decode((string) $audits[0]->changes, true))->toMatchArray([
        'completion' => 'in_progress',
        'status' => 'active',
    ]);

    // 2nd audit: pending
    expect(json_decode((string) $audits[1]->changes, true))->toMatchArray([
        'completion' => 'pending',
        'status' => 'active',
    ]);

    // 3rd audit: not_completed
    expect(json_decode((string) $audits[2]->changes, true))->toMatchArray([
        'completion' => 'not_completed',
        'completion_reason' => 'نقص مدارک هویتی و عدم همراه داشتن شناسنامه اصل',
        'status' => 'completed',
    ]);

    // 4th audit: completed
    expect(json_decode((string) $audits[3]->changes, true))->toMatchArray([
        'completion' => 'completed',
        'status' => 'completed',
    ]);
});

test('Cross-office operator receives 404 on attendance and completion updates (TASK-100, §7.3)', function (): void {
    Sanctum::actingAs($this->operatorB, ['*']);

    // Attempting to set attendance on Office A appointment
    $resAttendance = $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/attendance", [
        'attendance' => 'attended',
    ]);
    $resAttendance->assertNotFound();

    // Attempting to set completion on Office A appointment
    $resCompletion = $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/completion", [
        'completion' => 'completed',
    ]);
    $resCompletion->assertNotFound();
});

test('Citizen and unauthenticated users cannot access desk appointment routes (TASK-100, §7.3)', function (): void {
    // 1. Unauthenticated -> 401
    $this->getJson('/api/v1/desk/appointments')->assertStatus(401);
    $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/attendance", [
        'attendance' => 'attended',
    ])->assertStatus(401);

    // 2. Citizen user -> 403 Forbidden
    Sanctum::actingAs($this->citizen, ['*']);
    $this->getJson('/api/v1/desk/appointments')->assertStatus(403);
    $this->postJson("/api/v1/desk/appointments/{$this->appointmentA->id}/attendance", [
        'attendance' => 'attended',
    ])->assertStatus(403);
});
