<?php

declare(strict_types=1);

namespace Tests\Feature\OfficeNetwork;

use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OperatorRole;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Events\QueueUpdated;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Infrastructure\Cache\OfficeQueueCache;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    Role::firstOrCreate(['name' => 'office_manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'office_operator', 'guard_name' => 'web']);

    $this->office = Office::query()->create([
        'code' => '7001',
        'name' => 'دفتر پیشخوان فاطمی',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
        'active_counters' => 2,
        'current_waiting_queue' => 0,
    ]);

    $this->operator = new Operator;
    $this->operator->office_id = $this->office->id;
    $this->operator->username = 'op_fatemi';
    $this->operator->password_hash = Hash::make('Secret123!');
    $this->operator->full_name = 'حامد کاظمی';
    $this->operator->national_id = '0081234561';
    $this->operator->mobile = '09121110001';
    $this->operator->role = OperatorRole::OPERATOR;
    $this->operator->counter_number = 1;
    $this->operator->is_active = true;
    $this->operator->save();
    $this->operator->assignRole('office_operator');

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350800';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'متقاضی صف';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    $this->category = ServiceCategory::query()->create([
        'id' => 'cat-queue-test',
        'title' => 'خدمات عمومی',
    ]);

    $this->service = Service::query()->create([
        'slug' => 'queue-test-service',
        'title' => 'خدمت تست صف',
        'description' => 'توضیحات تست صف',
        'tags' => ['in-person'],
        'category_id' => $this->category->id,
        'fee_rials' => 200_000,
    ]);
});

function createQueueTestCase(Citizen $citizen, Service $service, Office $office, CaseStatus $status): CaseRequest
{
    return CaseRequest::query()->create([
        'tracking_code' => 'CR-Q-'.Str::upper(Str::random(6)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'office_id' => $office->id,
        'province_code' => 'THR',
        'status' => $status,
        'turn_owner' => TurnOwner::OFFICE,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'fee_paid_rials' => 200_000,
    ]);
}

it('returns live queue data for the authenticated operator office via GET /desk/queue', function (): void {
    createQueueTestCase($this->citizen, $this->service, $this->office, CaseStatus::ASSIGNED_TO_OFFICE);
    createQueueTestCase($this->citizen, $this->service, $this->office, CaseStatus::EXPERT_REVIEW);

    Sanctum::actingAs($this->operator, ['*']);

    $response = $this->getJson('/api/v1/desk/queue');

    $response->assertOk()
        ->assertJsonPath('data.office_id', $this->office->id)
        ->assertJsonPath('data.waiting_queue', 2)
        ->assertJsonPath('data.active_counters', 2)
        ->assertJsonPath('data.estimated_wait_minutes', 15); // ceil(2 * 15 / 2) = 15
});

it('increases queue after case acceptance and decreases after case completion', function (): void {
    $stateMachine = app(CaseStateMachine::class);
    $cache = app(OfficeQueueCache::class);

    // Initial state: 0 cases
    $initial = $cache->calculate($this->office->id);
    expect($initial['waiting_queue'])->toBe(0)
        ->and($initial['estimated_wait_minutes'])->toBe(0);

    // 1. Case is assigned to office (e.g. Offer accepted)
    $case = createQueueTestCase($this->citizen, $this->service, $this->office, CaseStatus::ASSIGNED_TO_OFFICE);

    $afterAccept = $cache->calculate($this->office->id);
    expect($afterAccept['waiting_queue'])->toBe(1)
        ->and($afterAccept['estimated_wait_minutes'])->toBe(8); // ceil(1 * 15 / 2) = 8

    // 2. Transition through workflow to ready_for_issue
    $stateMachine->transition(
        $case,
        CaseStatus::EXPERT_REVIEW,
        new TransitionContext(title: 'بررسی', stepStatus: TimelineStepStatus::CURRENT, actorType: TimelineActorType::OPERATOR)
    );
    $stateMachine->transition(
        $case,
        CaseStatus::GOVERNMENT_INQUIRY,
        new TransitionContext(title: 'استعلام', stepStatus: TimelineStepStatus::CURRENT, actorType: TimelineActorType::OPERATOR)
    );
    $stateMachine->transition(
        $case,
        CaseStatus::READY_FOR_ISSUE,
        new TransitionContext(title: 'آماده تحویل', stepStatus: TimelineStepStatus::CURRENT, actorType: TimelineActorType::SYSTEM)
    );

    // 3. Complete case -> queue count must decrease to 0!
    $stateMachine->transition(
        $case,
        CaseStatus::COMPLETED,
        new TransitionContext(title: 'تکمیل خدمت', stepStatus: TimelineStepStatus::DONE, actorType: TimelineActorType::OPERATOR)
    );

    $afterComplete = $cache->calculate($this->office->id);
    expect($afterComplete['waiting_queue'])->toBe(0)
        ->and($afterComplete['estimated_wait_minutes'])->toBe(0);
});

it('resiliently recalculates queue from database when Redis is completely flushed (RedisFlushResilienceTest §6.7)', function (): void {
    createQueueTestCase($this->citizen, $this->service, $this->office, CaseStatus::ASSIGNED_TO_OFFICE);
    createQueueTestCase($this->citizen, $this->service, $this->office, CaseStatus::EXPERT_REVIEW);
    createQueueTestCase($this->citizen, $this->service, $this->office, CaseStatus::ACTION_REQUIRED);

    Sanctum::actingAs($this->operator, ['*']);

    // First call populates cache
    $res1 = $this->getJson('/api/v1/desk/queue');
    $res1->assertOk()->assertJsonPath('data.waiting_queue', 3);

    // Completely flush Redis Cache
    Cache::flush();

    // Cache is empty
    $queueCache = app(OfficeQueueCache::class);
    expect($queueCache->get($this->office->id))->toBeNull();

    // Second call: must rebuild from database transparently without data loss!
    $res2 = $this->getJson('/api/v1/desk/queue');
    $res2->assertOk()
        ->assertJsonPath('data.office_id', $this->office->id)
        ->assertJsonPath('data.waiting_queue', 3)
        ->assertJsonPath('data.active_counters', 2)
        ->assertJsonPath('data.estimated_wait_minutes', 23); // ceil(3 * 15 / 2) = 23
});

it('debounces QueueUpdated broadcast to prevent event storm (§5.7, §9.2)', function (): void {
    Event::fake([QueueUpdated::class]);

    // First call within debounce window succeeds and dispatches event
    $first = QueueUpdated::dispatchDebounced($this->office->id, 5, 2, 2);
    expect($first)->toBeTrue();

    Event::assertDispatched(QueueUpdated::class, 1);

    // Immediate second call within 2 seconds is suppressed
    $second = QueueUpdated::dispatchDebounced($this->office->id, 6, 2, 2);
    expect($second)->toBeFalse();

    Event::assertDispatched(QueueUpdated::class, 1); // still only 1 event dispatched
});

it('returns 401 for unauthenticated and 403 for non-operator requests', function (): void {
    $this->getJson('/api/v1/desk/queue')->assertStatus(401);

    Sanctum::actingAs($this->citizen, ['*']);
    $this->getJson('/api/v1/desk/queue')->assertStatus(403);
});
