<?php

declare(strict_types=1);

namespace Tests\Feature\Realtime;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\DispatchOfferStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Events\CaseStatusChanged;
use App\Modules\CaseWorkflow\Domain\Events\CaseTimelineAppended;
use App\Modules\CaseWorkflow\Domain\Events\DispatchOfferCreated;
use App\Modules\CaseWorkflow\Domain\Events\DispatchOfferTaken;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\CaseTimelineStep;
use App\Modules\CaseWorkflow\Domain\Models\DispatchOffer;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Domain\Events\QueueUpdated;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Carbon\CarbonImmutable;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ProvinceSeeder::class);

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350802';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'شهروند تست پی‌لود';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->province_code = 'THR';
    $this->citizen->save();

    $category = ServiceCategory::query()->firstOrCreate(
        ['id' => 'cat_rt_payload'],
        ['title' => 'خدمات پی‌لود', 'slug' => 'rt-payload-test', 'icon' => 'bolt', 'display_order' => 1]
    );

    $this->service = Service::query()->create([
        'category_id' => $category->id,
        'slug' => 'svc_payload_'.Str::random(5),
        'title' => 'خدمت تست پی‌لود',
        'description' => 'تست محتوای رویدادهای برودکست',
        'tags' => ['online'],
        'fee_rials' => 1000000,
        'office_share_percent' => 70.0,
        'is_active' => true,
    ]);

    $this->office = Office::query()->create([
        'code' => '7101',
        'name' => 'دفتر تست پی‌لود',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    $this->case = CaseRequest::create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-PL-'.strtoupper(Str::random(6)),
        'citizen_id' => $this->citizen->id,
        'service_id' => $this->service->id,
        'office_id' => $this->office->id,
        'province_code' => 'THR',
        'city' => 'تهران',
        'status' => CaseStatus::EXPERT_REVIEW,
        'turn_owner' => TurnOwner::OFFICE,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'current_step' => 2,
        'total_steps' => 5,
        'fee_paid_rials' => 1000000,
        'office_share_rials' => 700000,
        'platform_share_rials' => 300000,
    ]);

    $this->offer = DispatchOffer::create([
        'id' => (string) Str::uuid(),
        'case_id' => $this->case->id,
        'office_id' => $this->office->id,
        'round' => 1,
        'status' => DispatchOfferStatus::PENDING,
        'expires_at' => CarbonImmutable::now()->addSeconds(90),
    ]);

    $this->timelineStep = CaseTimelineStep::create([
        'id' => (string) Str::uuid(),
        'case_id' => $this->case->id,
        'sequence' => 1,
        'title' => 'بررسی کارشناس دفتر',
        'description' => 'پرونده در دست بررسی کارشناس است.',
        'status' => TimelineStepStatus::CURRENT,
        'turn_owner' => TurnOwner::OFFICE,
        'turn_owner_label' => 'دفتر پیشخوان',
        'actor_type' => TimelineActorType::OPERATOR,
        'occurred_at' => Carbon::now(),
    ]);
});

/**
 * Helper to recursively scan array for PII patterns.
 */
function assertNoPiiInPayload(array $payload): void
{
    $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    // Iranian mobile pattern: 09\d{9} or +989\d{9}
    expect(preg_match('/(?:\+98|0)?9\d{9}/', $json))->toBe(0, 'Payload contains raw mobile number');

    // 10-digit national ID pattern (strict check)
    expect(preg_match('/\b\d{10}\b/', $json))->toBe(0, 'Payload contains 10-digit national ID');

    // Sensitive address keys
    expect(array_key_exists('address', $payload))->toBeFalse()
        ->and(array_key_exists('postal_address', $payload))->toBeFalse()
        ->and(array_key_exists('national_id', $payload))->toBeFalse()
        ->and(array_key_exists('mobile', $payload))->toBeFalse();
}

test('CaseStatusChanged payload strictly matches §5.7 structure, <4KB, and contains zero PII (TASK-071-T)', function (): void {
    $context = new TransitionContext(
        title: 'مدرک شما نیاز به اصلاح دارد',
        description: 'تصویر کارت ملی ناخواناست',
        stepStatus: TimelineStepStatus::CURRENT,
        actorType: TimelineActorType::OPERATOR,
        actorId: (string) Str::uuid(),
        reasonCode: 'DOC_BLUR',
    );

    $event = new CaseStatusChanged(
        case: $this->case,
        from: CaseStatus::EXPERT_REVIEW,
        to: CaseStatus::ACTION_REQUIRED,
        context: $context,
    );

    $payload = $event->broadcastWith();

    expect($payload['case_id'])->toBe($this->case->id)
        ->and($payload['tracking_code'])->toBe($this->case->tracking_code)
        ->and($payload['from'])->toBe('expert_review')
        ->and($payload['to'])->toBe('action_required')
        ->and($payload['reason_code'])->toBe('DOC_BLUR')
        ->and($payload['headline'])->toBe('مدرک شما نیاز به اصلاح دارد')
        ->and($payload['at'])->not->toBeEmpty();

    // Check payload size < 4KB (4096 bytes)
    $payloadBytes = strlen((string) json_encode($payload));
    expect($payloadBytes)->toBeLessThan(4096);

    // Zero PII
    assertNoPiiInPayload($payload);

    // Broadcast channels check
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(2)
        ->and($channels[0]->name)->toBe("private-case.{$this->case->id}")
        ->and($channels[1]->name)->toBe("private-citizen.{$this->case->citizen_id}");
});

test('CaseTimelineAppended payload <4KB, contains zero PII, and targets correct channel (TASK-071-T)', function (): void {
    $event = new CaseTimelineAppended($this->timelineStep);
    $payload = $event->broadcastWith();

    expect($payload['case_id'])->toBe($this->case->id)
        ->and($payload['step_id'])->toBe($this->timelineStep->id)
        ->and($payload['sequence'])->toBe(1)
        ->and($payload['title'])->toBe('بررسی کارشناس دفتر')
        ->and($payload['status'])->toBe('current');

    $payloadBytes = strlen((string) json_encode($payload));
    expect($payloadBytes)->toBeLessThan(4096);

    assertNoPiiInPayload($payload);

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1)
        ->and($channels[0]->name)->toBe("private-case.{$this->case->id}");
});

test('DispatchOfferCreated payload <4KB, contains zero PII, and has countdown info (TASK-071-T)', function (): void {
    $event = new DispatchOfferCreated($this->offer);
    $payload = $event->broadcastWith();

    expect($payload['offer_id'])->toBe($this->offer->id)
        ->and($payload['case_id'])->toBe($this->case->id)
        ->and($payload['office_id'])->toBe($this->office->id)
        ->and($payload['round'])->toBe(1)
        ->and($payload['status'])->toBe('pending')
        ->and($payload['remaining_seconds'])->toBeGreaterThan(0);

    $payloadBytes = strlen((string) json_encode($payload));
    expect($payloadBytes)->toBeLessThan(4096);

    assertNoPiiInPayload($payload);

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1)
        ->and($channels[0]->name)->toBe("private-office.{$this->office->id}");
});

test('DispatchOfferTaken payload <4KB and contains zero PII (TASK-071-T)', function (): void {
    $event = new DispatchOfferTaken(
        offerId: $this->offer->id,
        caseId: $this->case->id,
        officeId: $this->office->id,
        status: 'taken',
    );

    $payload = $event->broadcastWith();

    expect($payload['offer_id'])->toBe($this->offer->id)
        ->and($payload['case_id'])->toBe($this->case->id)
        ->and($payload['status'])->toBe('taken');

    $payloadBytes = strlen((string) json_encode($payload));
    expect($payloadBytes)->toBeLessThan(4096);

    assertNoPiiInPayload($payload);

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1)
        ->and($channels[0]->name)->toBe("private-office.{$this->office->id}");
});

test('QueueUpdated payload <4KB, contains zero PII, and debounces 2-second bursts (§9.2, TASK-071-T)', function (): void {
    Event::fake([QueueUpdated::class]);
    Cache::flush();

    // First call: must dispatch
    $dispatchedFirst = QueueUpdated::dispatchDebounced(
        officeId: $this->office->id,
        waitingQueue: 5,
        activeCounters: 2,
        debounceSeconds: 2
    );
    expect($dispatchedFirst)->toBeTrue();

    // Immediate second call (within 2 seconds): must be debounced / suppressed
    $dispatchedSecond = QueueUpdated::dispatchDebounced(
        officeId: $this->office->id,
        waitingQueue: 6,
        activeCounters: 2,
        debounceSeconds: 2
    );
    expect($dispatchedSecond)->toBeFalse();

    // Third call also debounced
    $dispatchedThird = QueueUpdated::dispatchDebounced(
        officeId: $this->office->id,
        waitingQueue: 7,
        activeCounters: 2,
        debounceSeconds: 2
    );
    expect($dispatchedThird)->toBeFalse();

    // Exactly 1 event was dispatched during the storm
    Event::assertDispatched(QueueUpdated::class, 1);

    // Verify event payload structure
    $event = new QueueUpdated(
        officeId: $this->office->id,
        waitingQueue: 5,
        activeCounters: 2,
    );
    $payload = $event->broadcastWith();

    expect($payload['office_id'])->toBe($this->office->id)
        ->and($payload['waiting_queue'])->toBe(5)
        ->and($payload['active_counters'])->toBe(2);

    $payloadBytes = strlen((string) json_encode($payload));
    expect($payloadBytes)->toBeLessThan(4096);

    assertNoPiiInPayload($payload);
});
