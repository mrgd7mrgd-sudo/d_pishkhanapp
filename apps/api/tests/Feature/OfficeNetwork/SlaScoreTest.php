<?php

declare(strict_types=1);

namespace Tests\Feature\OfficeNetwork;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\Messaging\Jobs\SendSmsJob;
use App\Modules\OfficeNetwork\Application\Actions\RecordOfficeSlaEventAction;
use App\Modules\OfficeNetwork\Application\Queries\OfficeFinder;
use App\Modules\OfficeNetwork\Domain\Enums\OfficeMembershipStatus;
use App\Modules\OfficeNetwork\Domain\Events\OfficeSlaBreached;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSlaEvent;
use App\Modules\OfficeNetwork\Domain\SlaScoreCalculator;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Shared\Audit\AuditableAction;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    Role::firstOrCreate(['name' => 'office_operator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'office_manager', 'guard_name' => 'web']);

    $this->officeA = Office::query()->create([
        'code' => '8201',
        'name' => 'دفتر پیشخوان دولت صادقیه',
        'is_online' => true,
        'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE->value,
        'province_code' => 'THR',
        'city' => 'تهران',
        'location' => '35.7000,51.4000',
        'rating' => 5.0,
        'review_count' => 10,
        'current_waiting_queue' => 0,
        'active_counters' => 2,
        'sla_score' => 100.00,
    ]);

    $this->officeB = Office::query()->create([
        'code' => '8202',
        'name' => 'دفتر پیشخوان دولت تجریش',
        'is_online' => true,
        'membership_status' => OfficeMembershipStatus::REGISTERED_ONLINE->value,
        'province_code' => 'THR',
        'city' => 'تهران',
        'location' => '35.7000,51.4000',
        'rating' => 5.0,
        'review_count' => 10,
        'current_waiting_queue' => 0,
        'active_counters' => 2,
        'sla_score' => 100.00,
    ]);

    $this->managerA = new Operator;
    $this->managerA->office_id = $this->officeA->id;
    $this->managerA->username = 'mgr_sadeqiyeh';
    $this->managerA->password_hash = Hash::make('Secret123!');
    $this->managerA->full_name = 'مدیر دفتر صادقیه';
    $this->managerA->national_id = '0012345678';
    $this->managerA->mobile = '09121112233';
    $this->managerA->role = OperatorRole::MANAGER;
    $this->managerA->counter_number = 1;
    $this->managerA->is_active = true;
    $this->managerA->save();
    $this->managerA->assignRole('office_manager');

    $this->category = ServiceCategory::query()->create([
        'id' => (string) Str::uuid(),
        'title' => 'خدمات ثبت احوال',
        'code' => 'civil_status',
        'is_active' => true,
        'icon' => 'identity',
    ]);

    $this->service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'title' => 'تعویض کارت ملی',
        'slug' => 'national-card-replacement',
        'description' => 'تعویض کارت ملی هوشمند',
        'category_id' => $this->category->id,
        'fee_rials' => 500000,
        'office_share_percent' => 70,
        'tags' => ['in-person', 'identity'],
        'is_active' => true,
    ]);

    $this->citizen = Citizen::query()->create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09129998877'),
        'mobile_encrypted' => 'enc:09129998877',
        'national_id_hash' => hash('sha256', '0098765432'),
        'national_id_encrypted' => 'enc:0098765432',
        'full_name' => 'سهراب سپهری',
        'tier' => CitizenTier::BRONZE->value,
        'profile_completed' => true,
    ]);
});

it('creates office_sla_events on breach and dispatches alert notification to office manager (TASK-102, TASK-102-T)', function (): void {
    Queue::fake([SendSmsJob::class]);
    Event::fake([OfficeSlaBreached::class]);

    $action = app(RecordOfficeSlaEventAction::class);
    $event = $action->execute(
        officeId: $this->officeA->id,
        eventType: 'offer_declined',
        caseId: (string) Str::uuid(),
        penaltyPoints: 1,
        durationMinutes: null,
        isBreach: true,
    );

    expect($event)->toBeInstanceOf(OfficeSlaEvent::class)
        ->and($event->office_id)->toBe($this->officeA->id)
        ->and($event->event_type)->toBe('offer_declined')
        ->and($event->penalty_points)->toBe(1)
        ->and($event->is_breach)->toBeTrue();

    $this->assertDatabaseHas('office_sla_events', [
        'id' => $event->id,
        'office_id' => $this->officeA->id,
        'event_type' => 'offer_declined',
        'penalty_points' => 1,
    ]);

    Event::assertDispatched(OfficeSlaBreached::class, function (OfficeSlaBreached $e): bool {
        return $e->officeId === $this->officeA->id
            && $e->eventType === 'offer_declined'
            && $e->penaltyPoints === 1;
    });

    Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $job): bool {
        return $job->mobile === '09121112233'
            && $job->template === 'sla_breach_alert'
            && $job->params['office_id'] === $this->officeA->id
            && $job->params['penalty'] === '1';
    });

    $this->assertDatabaseHas('audit_logs', [
        'action' => AuditableAction::SLA_BREACH_RECORDED->value,
        'subject_id' => $event->id,
    ]);
});

it('recalculates office sla_score strictly matching formula (TASK-102, TASK-102-T)', function (): void {
    $calculator = app(SlaScoreCalculator::class);

    // Initial score with 0 penalties should be 100.00
    expect($calculator->calculateForOffice($this->officeA))->toBe(100.00);

    // 1st breach: offer_declined (1 point)
    OfficeSlaEvent::create([
        'office_id' => $this->officeA->id,
        'event_type' => 'offer_declined',
        'penalty_points' => 1,
        'is_breach' => true,
        'occurred_at' => CarbonImmutable::now(),
    ]);

    // 2nd breach: review_delayed (3 points)
    OfficeSlaEvent::create([
        'office_id' => $this->officeA->id,
        'event_type' => 'review_delayed',
        'penalty_points' => 3,
        'is_breach' => true,
        'occurred_at' => CarbonImmutable::now(),
    ]);

    // Manual formula: 100.00 - (1 + 3) = 96.00
    expect($calculator->calculateForOffice($this->officeA))->toBe(96.00);

    // Run artisan command to recalculate
    $this->artisan('pishkhan:recalculate-office-scores', ['--office-id' => $this->officeA->id])
        ->assertSuccessful();

    $this->officeA->refresh();
    expect((float) $this->officeA->sla_score)->toBe(96.00);

    // Add severe penalties (110 points total) -> clamped to 0.00
    OfficeSlaEvent::create([
        'office_id' => $this->officeA->id,
        'event_type' => 'major_violation',
        'penalty_points' => 110,
        'is_breach' => true,
        'occurred_at' => CarbonImmutable::now(),
    ]);

    expect($calculator->calculateForOffice($this->officeA))->toBe(0.00);

    // Test rolling window: breach 45 days ago is ignored in 30-day window
    $officeC = Office::query()->create([
        'code' => '8203',
        'name' => 'دفتر ونک',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
        'sla_score' => 100.00,
    ]);

    OfficeSlaEvent::create([
        'office_id' => $officeC->id,
        'event_type' => 'old_event',
        'penalty_points' => 20,
        'is_breach' => true,
        'occurred_at' => CarbonImmutable::now()->subDays(45),
    ]);

    expect($calculator->calculateForOffice($officeC, 30))->toBe(100.00);
});

it('verifies office with lower SLA gets lower rank and smart_score in OfficeFinder (§5.6 #4, §9.6, TASK-102-T)', function (): void {
    // Both offices have identical coordinates, rating (5.0), and queue (0)
    // Office A has sla_score = 100.00, Office B has sla_score = 60.00
    $this->officeA->update(['sla_score' => 100.00]);
    $this->officeB->update(['sla_score' => 60.00]);

    $finder = app(OfficeFinder::class);
    $results = $finder->findNearby(
        lat: 35.7000,
        lng: 51.4000,
        radiusKm: 10.0,
        limit: 10
    );

    expect(count($results))->toBeGreaterThanOrEqual(2);

    $first = $results[0];
    $second = $results[1];

    // Office A must rank #1 due to higher SLA score
    expect($first['office']->id)->toBe($this->officeA->id)
        ->and($second['office']->id)->toBe($this->officeB->id);

    // Verify smart_score formula contribution:
    // Distance factor = 1.0 (at user location) -> 0.45 * 1.0 = 0.45
    // Rating factor = 5.0 / 5.0 = 1.0 -> 0.30 * 1.0 = 0.30
    // Queue factor = 1.0 - (0 / 20) = 1.0 -> 0.15 * 1.0 = 0.15
    // Office A SLA factor = 100.0 / 100.0 = 1.0 -> 0.10 * 1.0 = 0.10 -> Total = 1.0000
    // Office B SLA factor = 60.0 / 100.0 = 0.6 -> 0.10 * 0.6 = 0.06 -> Total = 0.9600
    // Score difference must be exactly 0.04
    expect((float) $first['smart_score'])->toBe(1.0)
        ->and((float) $second['smart_score'])->toBe(0.96)
        ->and(round($first['smart_score'] - $second['smart_score'], 2))->toBe(0.04);
});

it('detects overdue cases and logs SLA breach events via CheckSlaBreachesCommand (TASK-102, §5.9)', function (): void {
    Queue::fake([SendSmsJob::class]);

    $case = CaseRequest::query()->create([
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'province_code' => 'THR',
        'office_id' => $this->officeA->id,
        'status' => CaseStatus::EXPERT_REVIEW->value,
        'turn_owner' => TurnOwner::OFFICE->value,
        'delivery_preference' => DeliveryPreference::IN_PERSON->value,
        'tracking_code' => 'CR-1405-SLA01',
        'base_fee' => 500000,
        'total_fee' => 500000,
        'sla_deadline_at' => CarbonImmutable::now()->subMinutes(40),
    ]);

    $this->artisan('pishkhan:check-sla-breaches')
        ->assertSuccessful();

    $this->assertDatabaseHas('office_sla_events', [
        'office_id' => $this->officeA->id,
        'case_id' => $case->id,
        'event_type' => 'review_delayed',
        'penalty_points' => 3,
        'is_breach' => true,
    ]);

    // Second run should NOT duplicate breach event for same case
    $this->artisan('pishkhan:check-sla-breaches')
        ->assertSuccessful();

    $eventCount = OfficeSlaEvent::query()
        ->where('office_id', $this->officeA->id)
        ->where('case_id', $case->id)
        ->where('event_type', 'review_delayed')
        ->count();

    expect($eventCount)->toBe(1);
});
