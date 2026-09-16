<?php

declare(strict_types=1);

namespace Tests\Feature\OfficeNetwork;

use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentAttendance;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentCompletion;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentStatus;
use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Carbon\Carbon;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->office = Office::query()->create([
        'code' => '9101',
        'name' => 'دفتر پیشخوان دولت شریعتی',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
        'active_counters' => 1,
        'working_hours' => [
            'start' => '08:00',
            'end' => '18:00',
            'slot_capacity' => 2,
        ],
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-appt-test',
        'title' => 'خدمات احراز هویت حضوری',
    ]);

    $this->service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $category->id,
        'title' => 'اسکن بیومتریک کارت هوشمند ملی',
        'slug' => 'biometric-scan-card',
        'description' => 'حضور در دفتر جهت ثبت اثر انگشت و اسکن چهره',
        'tags' => ['in-person', 'identity'],
        'fee_rials' => 250000,
        'office_share_percent' => 70,
        'is_active' => true,
    ]);

    $this->citizen1 = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09121112233'),
        'mobile_encrypted' => 'enc:09121112233',
        'national_id_hash' => hash('sha256', '0010350811'),
        'national_id_encrypted' => 'enc:0010350811',
        'full_name' => 'حمیدرضا کمالی',
        'tier' => CitizenTier::BRONZE->value,
        'profile_completed' => true,
    ]);

    $this->citizen2 = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09124445566'),
        'mobile_encrypted' => 'enc:09124445566',
        'national_id_hash' => hash('sha256', '0010350812'),
        'national_id_encrypted' => 'enc:0010350812',
        'full_name' => 'سارا ناصری',
        'tier' => CitizenTier::SILVER->value,
        'profile_completed' => true,
    ]);

    // Next open day (Saturday..Wednesday)
    $target = Carbon::now('Asia/Tehran')->addDay();
    while ($target->dayOfWeek === Carbon::FRIDAY || $target->dayOfWeek === Carbon::THURSDAY) {
        $target->addDay();
    }
    $this->openDate = $target->format('Y-m-d');

    // Next Friday (weekend)
    $friday = Carbon::now('Asia/Tehran')->addDay();
    while ($friday->dayOfWeek !== Carbon::FRIDAY) {
        $friday->addDay();
    }
    $this->fridayDate = $friday->format('Y-m-d');
});

test('GET /offices/{id}/slots returns available slots for open day and empty for Friday (TASK-099, §5.3)', function (): void {
    // 1. Regular open day
    $response = $this->getJson("/api/v1/offices/{$this->office->id}/slots?date={$this->openDate}");

    $response->assertOk()
        ->assertJsonPath('data.office_id', $this->office->id)
        ->assertJsonPath('data.date', $this->openDate)
        ->assertJsonPath('data.is_open', true)
        ->assertJsonPath('data.capacity_per_slot', 2);

    $slots = $response->json('data.slots');
    expect($slots)->not->toBeEmpty()
        ->and($slots[0]['time_slot'])->toBe('08:00-08:30')
        ->and($slots[0]['capacity'])->toBe(2)
        ->and($slots[0]['booked_count'])->toBe(0)
        ->and($slots[0]['available_capacity'])->toBe(2)
        ->and($slots[0]['is_available'])->toBeTrue();

    // 2. Friday (holiday)
    $fridayResponse = $this->getJson("/api/v1/offices/{$this->office->id}/slots?date={$this->fridayDate}");
    $fridayResponse->assertOk()
        ->assertJsonPath('data.is_open', false)
        ->assertJsonPath('data.slots', []);
});

test('citizen can book appointment, receiving sequential queue_number and tracking_code (TASK-099)', function (): void {
    Sanctum::actingAs($this->citizen1);

    $payload = [
        'office_id' => $this->office->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->openDate,
        'time_slot' => '09:00-09:30',
        'reminder_type' => 'all',
        'reminder_enabled' => true,
    ];

    $response = $this->postJson('/api/v1/appointments', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.citizen_id', $this->citizen1->id)
        ->assertJsonPath('data.office_id', $this->office->id)
        ->assertJsonPath('data.service_id', $this->service->id)
        ->assertJsonPath('data.appointment_date', $this->openDate)
        ->assertJsonPath('data.time_slot', '09:00-09:30')
        ->assertJsonPath('data.queue_number', '1')
        ->assertJsonPath('data.status', AppointmentStatus::Active->value)
        ->assertJsonPath('data.attendance', AppointmentAttendance::Pending->value)
        ->assertJsonPath('data.completion', AppointmentCompletion::Pending->value);

    $trackingCode = $response->json('data.tracking_code');
    expect($trackingCode)->toBeString()
        ->and($trackingCode)->toStartWith('APT-');

    // Second booking on same day gets sequential queue_number 2
    Sanctum::actingAs($this->citizen2);

    $payload2 = [
        'office_id' => $this->office->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->openDate,
        'time_slot' => '09:00-09:30',
    ];

    $response2 = $this->postJson('/api/v1/appointments', $payload2);
    $response2->assertCreated()
        ->assertJsonPath('data.queue_number', '2');
});

test('concurrency: 10 parallel booking requests for a slot accept only permitted capacity (§5.3, TASK-099-T)', function (): void {
    // Office slot capacity is 2
    // We simulate 10 citizen booking attempts for the exact same slot
    $citizens = [];
    for ($i = 0; $i < 10; $i++) {
        $citizens[] = Citizen::query()->create([
            'id' => (string) Str::uuid(),
            'mobile_hash' => hash('sha256', "0912888000{$i}"),
            'mobile_encrypted' => "enc:0912888000{$i}",
            'national_id_hash' => hash('sha256', "001035090{$i}"),
            'national_id_encrypted' => "enc:001035090{$i}",
            'full_name' => "کاربر تست همزمانی {$i}",
            'tier' => CitizenTier::BRONZE->value,
            'profile_completed' => true,
        ]);
    }

    $successCount = 0;
    $conflictCount = 0;

    foreach ($citizens as $citizen) {
        Sanctum::actingAs($citizen);

        $res = $this->postJson('/api/v1/appointments', [
            'office_id' => $this->office->id,
            'service_id' => $this->service->id,
            'appointment_date' => $this->openDate,
            'time_slot' => '10:00-10:30',
        ]);

        if ($res->status() === 201) {
            $successCount++;
        } elseif ($res->status() === 409) {
            $conflictCount++;
            $res->assertJsonPath('code', 'SLOT_CAPACITY_EXCEEDED');
        }
    }

    expect($successCount)->toBe(2)
        ->and($conflictCount)->toBe(8);

    // Exactly 2 active appointments in DB
    $dbCount = Appointment::where('office_id', $this->office->id)
        ->where('appointment_date', $this->openDate)
        ->where('time_slot', '10:00-10:30')
        ->where('status', AppointmentStatus::Active)
        ->count();

    expect($dbCount)->toBe(2);
});

test('unique queue_number per office and appointment_date invariant triggers QueryException on duplicate (§5.3, §6.4)', function (): void {
    Appointment::query()->create([
        'id' => (string) Str::uuid(),
        'citizen_id' => $this->citizen1->id,
        'office_id' => $this->office->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->openDate,
        'time_slot' => '11:00-11:30',
        'tracking_code' => 'APT-TEST-001',
        'status' => AppointmentStatus::Active,
        'attendance' => AppointmentAttendance::Pending,
        'completion' => AppointmentCompletion::Pending,
        'queue_number' => '99',
        'counter_number' => 1,
        'reminder_enabled' => true,
        'reminder_type' => 'all',
    ]);

    // Inserting identical (office_id, appointment_date, queue_number) must fail
    expect(function (): void {
        Appointment::query()->create([
            'id' => (string) Str::uuid(),
            'citizen_id' => $this->citizen2->id,
            'office_id' => $this->office->id,
            'service_id' => $this->service->id,
            'appointment_date' => $this->openDate,
            'time_slot' => '11:30-12:00',
            'tracking_code' => 'APT-TEST-002',
            'status' => AppointmentStatus::Active,
            'attendance' => AppointmentAttendance::Pending,
            'completion' => AppointmentCompletion::Pending,
            'queue_number' => '99',
            'counter_number' => 1,
            'reminder_enabled' => true,
            'reminder_type' => 'all',
        ]);
    })->toThrow(QueryException::class);
});

test('booking on office holiday or closed day is rejected (TASK-099-T)', function (): void {
    Sanctum::actingAs($this->citizen1);

    // 1. Booking on Friday
    $res = $this->postJson('/api/v1/appointments', [
        'office_id' => $this->office->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->fridayDate,
        'time_slot' => '09:00-09:30',
    ]);

    $res->assertStatus(422)
        ->assertJsonPath('code', 'OFFICE_CLOSED');

    // 2. Booking on custom holiday
    $holidayDate = Carbon::parse($this->openDate)->addDays(7)->format('Y-m-d');
    $this->office->working_hours = [
        'start' => '08:00',
        'end' => '18:00',
        'holidays' => [$holidayDate],
    ];
    $this->office->save();

    $resHoliday = $this->postJson('/api/v1/appointments', [
        'office_id' => $this->office->id,
        'service_id' => $this->service->id,
        'appointment_date' => $holidayDate,
        'time_slot' => '09:00-09:30',
    ]);

    $resHoliday->assertStatus(422)
        ->assertJsonPath('code', 'OFFICE_CLOSED');
});

test('cancellation frees up slot capacity allowing new booking (TASK-099-T)', function (): void {
    // Fill slot to capacity (capacity = 2)
    Sanctum::actingAs($this->citizen1);
    $res1 = $this->postJson('/api/v1/appointments', [
        'office_id' => $this->office->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->openDate,
        'time_slot' => '14:00-14:30',
    ]);
    $res1->assertCreated();
    $appt1Id = $res1->json('data.id');

    Sanctum::actingAs($this->citizen2);
    $res2 = $this->postJson('/api/v1/appointments', [
        'office_id' => $this->office->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->openDate,
        'time_slot' => '14:00-14:30',
    ]);
    $res2->assertCreated();

    // 3rd citizen cannot book full slot
    $citizen3 = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09127778899'),
        'mobile_encrypted' => 'enc:09127778899',
        'national_id_hash' => hash('sha256', '0010350813'),
        'national_id_encrypted' => 'enc:0010350813',
        'full_name' => 'امیرحسین رضایی',
        'tier' => CitizenTier::BRONZE->value,
        'profile_completed' => true,
    ]);

    Sanctum::actingAs($citizen3);
    $res3 = $this->postJson('/api/v1/appointments', [
        'office_id' => $this->office->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->openDate,
        'time_slot' => '14:00-14:30',
    ]);
    $res3->assertStatus(409)
        ->assertJsonPath('code', 'SLOT_CAPACITY_EXCEEDED');

    // Citizen 1 cancels appointment
    Sanctum::actingAs($this->citizen1);
    $cancelRes = $this->deleteJson("/api/v1/appointments/{$appt1Id}", [
        'reason' => 'تغییر برنامه شخصی',
    ]);
    $cancelRes->assertOk()
        ->assertJsonPath('data.status', AppointmentStatus::Cancelled->value)
        ->assertJsonPath('data.cancellation_reason', 'تغییر برنامه شخصی');

    // Now citizen 3 can book the freed slot!
    Sanctum::actingAs($citizen3);
    $res3Retry = $this->postJson('/api/v1/appointments', [
        'office_id' => $this->office->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->openDate,
        'time_slot' => '14:00-14:30',
    ]);
    $res3Retry->assertCreated();
});

test('citizen can view their appointments and authorization isolates other citizens (TASK-099)', function (): void {
    Sanctum::actingAs($this->citizen1);

    $bookRes = $this->postJson('/api/v1/appointments', [
        'office_id' => $this->office->id,
        'service_id' => $this->service->id,
        'appointment_date' => $this->openDate,
        'time_slot' => '15:00-15:30',
    ]);
    $bookRes->assertCreated();
    $apptId = $bookRes->json('data.id');

    // Citizen 1 lists appointments
    $listRes = $this->getJson('/api/v1/appointments');
    $listRes->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $apptId);

    // Citizen 1 views own appointment
    $showRes = $this->getJson("/api/v1/appointments/{$apptId}");
    $showRes->assertOk()
        ->assertJsonPath('data.id', $apptId);

    // Citizen 2 cannot view Citizen 1's appointment (403)
    Sanctum::actingAs($this->citizen2);
    $showOther = $this->getJson("/api/v1/appointments/{$apptId}");
    $showOther->assertStatus(403);

    // Citizen 2 cannot cancel Citizen 1's appointment (403)
    $cancelOther = $this->deleteJson("/api/v1/appointments/{$apptId}");
    $cancelOther->assertStatus(403);
});
