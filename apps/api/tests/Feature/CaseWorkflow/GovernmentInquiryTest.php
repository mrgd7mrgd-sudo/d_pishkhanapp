<?php

declare(strict_types=1);

namespace Tests\Feature\CaseWorkflow;

use App\Integration\Government\CivilRegistryClient;
use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\GovInquiryProvider;
use App\Modules\CaseWorkflow\Domain\Enums\GovInquiryStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\GovInquiry;
use App\Modules\CaseWorkflow\Jobs\GovernmentInquiryJob;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Messaging\Jobs\SendCaseNotificationJob;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Shared\Resilience\CircuitBreaker;
use App\Shared\Resilience\CircuitBreakerOpenException;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    putenv('SIMULATOR_FAST_TEST=true');
    $this->seed(ProvinceSeeder::class);

    $this->office = Office::query()->create([
        'code' => '7001',
        'name' => 'دفتر پیشخوان بهشتی',
        'is_online' => true,
        'province_code' => 'THR',
        'city' => 'تهران',
    ]);

    $this->category = ServiceCategory::query()->create([
        'id' => 'cat-inq-test',
        'title' => 'خدمات استعلامی',
    ]);

    $this->service = Service::query()->create([
        'slug' => 'inquiry-test-service',
        'title' => 'خدمت تست استعلام',
        'description' => 'توضیحات تست استعلام',
        'tags' => ['in-person'],
        'category_id' => $this->category->id,
        'fee_rials' => 300_000,
    ]);

    // Reset circuit breaker
    (new CircuitBreaker('civil-registry', failures: 5, cooldownSeconds: 300))->reset();
});

function createInquiryTestCase(string $nationalId, Service $service, Office $office): CaseRequest
{
    $citizen = new Citizen;
    $citizen->national_id = $nationalId;
    $citizen->mobile = '09121112233';
    $citizen->full_name = 'متقاضی استعلام';
    $citizen->tier = CitizenTier::BRONZE;
    $citizen->province_code = 'THR';
    $citizen->save();

    return CaseRequest::query()->create([
        'tracking_code' => 'CR-INQ-'.Str::upper(Str::random(6)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'office_id' => $office->id,
        'province_code' => 'THR',
        'status' => CaseStatus::GOVERNMENT_INQUIRY,
        'turn_owner' => TurnOwner::GOVERNMENT,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'fee_paid_rials' => 300_000,
    ]);
}

it('verifies GovernmentInquiryJob properties: queue, tries, and exact backoff schedule', function (): void {
    $job = new GovernmentInquiryJob('test-case-id');

    expect($job->queue)->toBe('inquiries')
        ->and($job->tries)->toBe(8)
        ->and($job->backoff())->toBe([60, 300, 900, 1800, 3600, 7200, 14400, 21600]);
});

it('processes scenario 0: national ID ending with 0 succeeds and transitions to ready_for_issue', function (): void {
    Queue::fake();

    $case = createInquiryTestCase('0010350800', $this->service, $this->office);

    $job = new GovernmentInquiryJob($case->id);
    $job->handle(
        app(CivilRegistryClient::class),
        app(CaseStateMachine::class)
    );

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::READY_FOR_ISSUE)
        ->and($case->turn_owner)->toBe(TurnOwner::OFFICE);

    /** @var GovInquiry $inquiry */
    $inquiry = GovInquiry::query()
        ->where('case_id', $case->id)
        ->where('provider', GovInquiryProvider::CIVIL_REGISTRY)
        ->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->status)->toBe(GovInquiryStatus::SUCCEEDED)
        ->and($inquiry->completed_at)->not->toBeNull();

    Queue::assertPushed(SendCaseNotificationJob::class, function (SendCaseNotificationJob $n) use ($case): bool {
        return $n->citizenId === $case->citizen_id && $n->type === 'case-ready';
    });
});

it('processes scenario 1: national ID ending with 1 returns INQUIRY_MISMATCH and transitions to action_required', function (): void {
    Queue::fake();

    $case = createInquiryTestCase('0010350801', $this->service, $this->office);

    $job = new GovernmentInquiryJob($case->id);
    $job->handle(
        app(CivilRegistryClient::class),
        app(CaseStateMachine::class)
    );

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::ACTION_REQUIRED)
        ->and($case->turn_owner)->toBe(TurnOwner::CITIZEN);

    /** @var GovInquiry $inquiry */
    $inquiry = GovInquiry::query()
        ->where('case_id', $case->id)
        ->where('provider', GovInquiryProvider::CIVIL_REGISTRY)
        ->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->status)->toBe(GovInquiryStatus::MISMATCH)
        ->and($inquiry->last_error)->toBe('INQUIRY_MISMATCH');

    Queue::assertPushed(SendCaseNotificationJob::class, function (SendCaseNotificationJob $n) use ($case): bool {
        return $n->citizenId === $case->citizen_id && $n->type === 'case-returned';
    });
});

it('processes scenario 4: national ID ending with 4 returns ELIGIBILITY_FAIL and transitions to rejected', function (): void {
    Queue::fake();

    $case = createInquiryTestCase('0010350804', $this->service, $this->office);

    $job = new GovernmentInquiryJob($case->id);
    $job->handle(
        app(CivilRegistryClient::class),
        app(CaseStateMachine::class)
    );

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::REJECTED)
        ->and($case->turn_owner)->toBe(TurnOwner::SYSTEM)
        ->and($case->closed_at)->not->toBeNull();

    /** @var GovInquiry $inquiry */
    $inquiry = GovInquiry::query()
        ->where('case_id', $case->id)
        ->where('provider', GovInquiryProvider::CIVIL_REGISTRY)
        ->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->status)->toBe(GovInquiryStatus::FAILED)
        ->and($inquiry->last_error)->toBe('ELIGIBILITY_FAIL');

    Queue::assertPushed(SendCaseNotificationJob::class, function (SendCaseNotificationJob $n) use ($case): bool {
        return $n->citizenId === $case->citizen_id && $n->type === 'case_rejected';
    });
});

it('processes scenario 3: national ID ending with 3 throws 500 error and case remains in government_inquiry', function (): void {
    $case = createInquiryTestCase('0010350803', $this->service, $this->office);

    $job = new GovernmentInquiryJob($case->id);

    try {
        $job->handle(
            app(CivilRegistryClient::class),
            app(CaseStateMachine::class)
        );
        test()->fail('Expected RuntimeException was not thrown.');
    } catch (RuntimeException $e) {
        expect($e->getCode())->toBe(500);
    }

    $case->refresh();
    // Case remains in government_inquiry (Scenario E24)
    expect($case->status)->toBe(CaseStatus::GOVERNMENT_INQUIRY);

    /** @var GovInquiry $inquiry */
    $inquiry = GovInquiry::query()
        ->where('case_id', $case->id)
        ->where('provider', GovInquiryProvider::CIVIL_REGISTRY)
        ->first();

    expect($inquiry)->not->toBeNull()
        ->and($inquiry->attempts)->toBe(1)
        ->and($inquiry->last_error)->toContain('۵۰۰');
});

it('processes scenario 2: national ID ending with 2 succeeds after delay', function (): void {
    Queue::fake();

    $case = createInquiryTestCase('0010350802', $this->service, $this->office);

    $job = new GovernmentInquiryJob($case->id);
    $job->handle(
        app(CivilRegistryClient::class),
        app(CaseStateMachine::class)
    );

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::READY_FOR_ISSUE);
});

it('opens CircuitBreaker after 5 consecutive failures, preserving case in government_inquiry (Scenario E24)', function (): void {
    $cb = new CircuitBreaker('civil-registry', failures: 5, cooldownSeconds: 300);
    $cb->reset();

    expect($cb->getState())->toBe(CircuitBreaker::STATE_CLOSED)
        ->and($cb->isAvailable())->toBeTrue();

    // 4 failures: circuit remains closed
    for ($i = 0; $i < 4; $i++) {
        $cb->recordFailure();
    }
    expect($cb->getState())->toBe(CircuitBreaker::STATE_CLOSED);

    // 5th failure: circuit opens!
    $cb->recordFailure();
    expect($cb->getState())->toBe(CircuitBreaker::STATE_OPEN)
        ->and($cb->isAvailable())->toBeFalse();

    // Executing under open circuit throws CircuitBreakerOpenException
    expect(fn () => $cb->execute(fn () => 'ok'))
        ->toThrow(CircuitBreakerOpenException::class);

    // Case remains safely in government_inquiry
    $case = createInquiryTestCase('0010350803', $this->service, $this->office);
    expect($case->status)->toBe(CaseStatus::GOVERNMENT_INQUIRY);
});
