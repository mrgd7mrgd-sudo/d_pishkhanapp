<?php

declare(strict_types=1);

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\DeliveryPreference;
use App\Modules\CaseWorkflow\Domain\Enums\GovInquiryProvider;
use App\Modules\CaseWorkflow\Domain\Enums\GovInquiryStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TurnOwner;
use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\CaseWorkflow\Domain\Models\GovInquiry;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Shared\Security\PiiRedactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $citizen = Citizen::create([
        'id' => (string) Str::uuid(),
        'mobile_hash' => hash('sha256', '09121112233'),
        'mobile_encrypted' => 'enc:09121112233',
        'national_id_hash' => hash('sha256', '0081234567'),
        'national_id_encrypted' => 'enc:0081234567',
        'full_name' => 'تست‌کننده استعلام',
        'tier' => 'bronze',
        'profile_completed' => true,
    ]);

    $category = ServiceCategory::query()->create([
        'id' => 'cat-inquiry-'.Str::random(5),
        'title' => 'خدمات آزمایشی استعلام',
    ]);

    $service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $category->id,
        'title' => 'خدمت تست استعلام',
        'slug' => 'gov-test-service-'.Str::random(6),
        'description' => 'توضیحات تست استعلام',
        'tags' => ['online'],
        'fee_rials' => 500000,
        'office_share_percent' => 70,
        'is_active' => true,
    ]);

    $this->case = CaseRequest::create([
        'id' => (string) Str::uuid(),
        'tracking_code' => 'CS-'.strtoupper(Str::random(8)),
        'citizen_id' => $citizen->id,
        'service_id' => $service->id,
        'office_id' => null,
        'province_code' => 'THR',
        'status' => CaseStatus::GOVERNMENT_INQUIRY,
        'turn_owner' => TurnOwner::GOVERNMENT,
        'delivery_preference' => DeliveryPreference::IN_PERSON,
        'current_step' => 2,
        'total_steps' => 5,
        'fee_paid_rials' => 500000,
        'office_share_rials' => 350000,
        'platform_share_rials' => 150000,
    ]);
});

test('gov_inquiries table has correct columns and indexes', function (): void {
    expect(Schema::hasTable('gov_inquiries'))->toBeTrue();

    expect(Schema::hasColumns('gov_inquiries', [
        'id',
        'case_id',
        'provider',
        'status',
        'request_snapshot',
        'response_snapshot',
        'attempts',
        'last_error',
        'completed_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

test('gov inquiry defaults attempts to zero and status to queued', function (): void {
    $inquiry = GovInquiry::create([
        'id' => (string) Str::uuid(),
        'case_id' => $this->case->id,
        'provider' => GovInquiryProvider::SHAHKAR,
        'request_snapshot' => ['mobile' => '09121112233'],
    ]);

    expect($inquiry->attempts)->toBe(0)
        ->and($inquiry->status)->toBe(GovInquiryStatus::QUEUED)
        ->and($inquiry->provider)->toBe(GovInquiryProvider::SHAHKAR)
        ->and($inquiry->last_error)->toBeNull()
        ->and($inquiry->completed_at)->toBeNull();

    // Test incrementing attempts
    $inquiry->recordAttempt('خطای موقت در اتصال به شاهکار');
    $inquiry->refresh();

    expect($inquiry->attempts)->toBe(1)
        ->and($inquiry->last_error)->toBe('خطای موقت در اتصال به شاهکار');

    $inquiry->recordAttempt();
    $inquiry->refresh();

    expect($inquiry->attempts)->toBe(2);
});

test('request_snapshot and response_snapshot never contain raw national id or raw sensitive PII', function (): void {
    $rawNationalId = '0010350802';
    $rawMobile = '09121112233';
    $rawCard = '6037991812345678';

    $inquiry = GovInquiry::create([
        'id' => (string) Str::uuid(),
        'case_id' => $this->case->id,
        'provider' => GovInquiryProvider::CIVIL_REGISTRY,
        'request_snapshot' => [
            'national_id' => $rawNationalId,
            'mobile' => $rawMobile,
            'card' => $rawCard,
            'note' => "درخواست استعلام کدملی {$rawNationalId}",
        ],
    ]);

    // Inspect database representation directly to ensure no raw PII in stored JSON
    $rawRow = DB::table('gov_inquiries')->where('id', $inquiry->id)->first();
    expect($rawRow)->not->toBeNull();

    $storedRequestJson = (string) $rawRow->request_snapshot;

    // Scan regex for raw 10-digit national id, 16-digit card, and full mobile
    expect(preg_match('/\b0010350802\b/', $storedRequestJson))->toBe(0)
        ->and(preg_match('/\b6037991812345678\b/', $storedRequestJson))->toBe(0)
        ->and(preg_match('/\b09121112233\b/', $storedRequestJson))->toBe(0);

    // Verify masked pattern is present
    expect($storedRequestJson)->toContain(PiiRedactor::maskNationalId($rawNationalId));

    // Test response snapshot redaction
    $inquiry->markSucceeded([
        'inquiry_ref' => 'REF-9988',
        'citizen_national_id' => $rawNationalId,
        'verified' => true,
    ]);

    $rawRowUpdated = DB::table('gov_inquiries')->where('id', $inquiry->id)->first();
    $storedResponseJson = (string) $rawRowUpdated->response_snapshot;

    expect(preg_match('/\b0010350802\b/', $storedResponseJson))->toBe(0)
        ->and($storedResponseJson)->toContain(PiiRedactor::maskNationalId($rawNationalId));
});

test('gov inquiry status transitions and lifecycle helpers work correctly', function (): void {
    $inquiry = GovInquiry::create([
        'id' => (string) Str::uuid(),
        'case_id' => $this->case->id,
        'provider' => GovInquiryProvider::POST,
        'request_snapshot' => ['postal_code' => '1234567890'],
    ]);

    expect($inquiry->status->isTerminal())->toBeFalse()
        ->and($inquiry->status->isSuccessful())->toBeFalse();

    // Mismatch scenario
    $inquiry->markMismatch('عدم تطابق کد پستی با آدرس ثبتی', ['code' => 'MISMATCH']);
    $inquiry->refresh();

    expect($inquiry->status)->toBe(GovInquiryStatus::MISMATCH)
        ->and($inquiry->status->isTerminal())->toBeTrue()
        ->and($inquiry->status->isSuccessful())->toBeFalse()
        ->and($inquiry->last_error)->toBe('عدم تطابق کد پستی با آدرس ثبتی')
        ->and($inquiry->completed_at)->not->toBeNull();

    // Succeeded scenario
    $inquiry->markSucceeded(['status' => 'OK']);
    $inquiry->refresh();

    expect($inquiry->status)->toBe(GovInquiryStatus::SUCCEEDED)
        ->and($inquiry->status->isTerminal())->toBeTrue()
        ->and($inquiry->status->isSuccessful())->toBeTrue()
        ->and($inquiry->last_error)->toBeNull();

    // Failed scenario
    $inquiry->markFailed('خطای سرور ثبت‌احوال');
    $inquiry->refresh();

    expect($inquiry->status)->toBe(GovInquiryStatus::FAILED)
        ->and($inquiry->status->isTerminal())->toBeTrue()
        ->and($inquiry->last_error)->toBe('خطای سرور ثبت‌احوال');
});

test('case request has many gov inquiries relationship', function (): void {
    GovInquiry::create([
        'id' => (string) Str::uuid(),
        'case_id' => $this->case->id,
        'provider' => GovInquiryProvider::SHAHKAR,
        'request_snapshot' => ['check' => 'mobile'],
    ]);

    GovInquiry::create([
        'id' => (string) Str::uuid(),
        'case_id' => $this->case->id,
        'provider' => GovInquiryProvider::CIVIL_REGISTRY,
        'request_snapshot' => ['check' => 'identity'],
    ]);

    expect($this->case->govInquiries)->toHaveCount(2)
        ->and($this->case->govInquiries->first()->caseRequest->id)->toBe($this->case->id);
});
