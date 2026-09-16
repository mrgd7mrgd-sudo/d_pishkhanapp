<?php

declare(strict_types=1);

use App\Modules\Consultation\Domain\Enums\ConsultationCategory;
use App\Modules\Consultation\Domain\Enums\ConsultationMode;
use App\Modules\Consultation\Domain\Enums\ConsultationSessionStatus;
use App\Modules\Consultation\Domain\Enums\SessionMessageSenderType;
use App\Modules\Consultation\Domain\Enums\SubscriptionStatus;
use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Consultation\Domain\Models\ConsultationSession;
use App\Modules\Consultation\Domain\Models\QuotaUsage;
use App\Modules\Consultation\Domain\Models\SessionMessage;
use App\Modules\Consultation\Domain\Models\Subscription;
use App\Modules\Consultation\Domain\Models\SubscriptionPlan;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createSessionTestCitizen(string $mobile, string $nationalId, string $name): Citizen
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

it('verifies all 3 consultation modes match packages/domain exactly (§6.1, TASK-117, TASK-117-T)', function (): void {
    $tsPath = realpath(base_path('../../packages/domain/src/consultation.ts'));
    expect($tsPath)->not->toBeFalse();

    $content = file_get_contents((string) $tsPath);
    preg_match('/export const CONSULTATION_MODES = \[(.*?)\] as const;/s', $content, $matches);
    expect($matches)->not->toBeEmpty();

    preg_match_all("/'([^']+)'/", $matches[1], $modeMatches);
    $tsModes = $modeMatches[1];

    $phpModes = ConsultationMode::values();

    expect($phpModes)->toBe($tsModes)
        ->and(count($phpModes))->toBe(3)
        ->and($phpModes)->toContain('text', 'call', 'case_review');
});

it('verifies Invariant §5.3: quota_usages.used negative violates database constraint (§5.3, §6.1, TASK-117-T)', function (): void {
    $citizen = createSessionTestCitizen('09123334455', '0017778899', 'کاربر اشتراک');

    $plan = SubscriptionPlan::query()->create([
        'plan_key' => 'bronze_business',
        'title' => 'پلن برنزی کسب‌وکار',
        'price_monthly_rials' => 5000000,
        'target_audience' => 'کسب‌وکارهای نوپا',
        'features' => ['مشاوره متنی', 'بررسی اوراق'],
        'quota' => ['text_chats' => 5, 'deep_reviews' => 1],
    ]);

    $sub = Subscription::query()->create([
        'plan_id' => $plan->id,
        'citizen_id' => $citizen->id,
        'status' => SubscriptionStatus::Active,
        'started_on' => now()->toDateString(),
        'expires_on' => now()->addMonth()->toDateString(),
    ]);

    $quota = QuotaUsage::query()->create([
        'subscription_id' => $sub->id,
        'quota_key' => 'text_chats',
        'used' => 0,
        'limit' => 5,
        'period_start' => now()->startOfMonth()->toDateString(),
    ]);

    expect($quota->used)->toBe(0);

    // Negative used must violate Invariant §5.3
    expect(function () use ($sub): void {
        QuotaUsage::query()->create([
            'subscription_id' => $sub->id,
            'quota_key' => 'deep_reviews',
            'used' => -1, // VIOLATION of Invariant §5.3
            'limit' => 1,
            'period_start' => now()->startOfMonth()->toDateString(),
        ]);
    })->toThrow(\InvalidArgumentException::class);
});

it('verifies linked_service_id foreign key constraint connects consultation to service execution (§6.1, TASK-117-T)', function (): void {
    $advisorCitizen = createSessionTestCitizen('09121110011', '0016665544', 'مشاور مرتبط');
    $clientCitizen = createSessionTestCitizen('09122220022', '0016665555', 'متقاضی خدمت');

    $advisor = Advisor::query()->create([
        'citizen_id' => $advisorCitizen->id,
        'display_name' => 'مهندس قاسمی',
        'title' => 'مشاور ثبتی',
        'category' => ConsultationCategory::LegalRegistry,
        'license_number' => 'LIC-LEG-1405-77',
    ]);

    $cat = ServiceCategory::query()->create([
        'id' => (string) Str::uuid(),
        'title' => 'خدمات ثبت شرکت',
        'icon_name' => 'building',
    ]);

    $service = Service::query()->create([
        'id' => (string) Str::uuid(),
        'category_id' => $cat->id,
        'slug' => 'srv-reg-company',
        'title' => 'ثبت شرکت سهامی خاص',
        'description' => 'توضیحات ثبت شرکت',
        'tags' => ['شرکت', 'ثبت'],
        'fee_rials' => 20000000,
        'estimated_days_min' => 2,
        'estimated_days_max' => 5,
    ]);

    $session = ConsultationSession::query()->create([
        'advisor_id' => $advisor->id,
        'citizen_id' => $clientCitizen->id,
        'mode' => ConsultationMode::CaseReview,
        'status' => ConsultationSessionStatus::Active,
        'duration_seconds' => 900,
        'total_fee_rials' => 5000000,
        'tracking_code' => 'CS-1405-0001',
        'linked_service_id' => $service->id,
    ]);

    expect($session->linkedService)->not->toBeNull()
        ->and($session->linkedService->id)->toBe($service->id)
        ->and($session->linkedService->title)->toBe('ثبت شرکت سهامی خاص');

    // Invalid foreign key must fail
    expect(function () use ($advisor, $clientCitizen): void {
        ConsultationSession::query()->create([
            'advisor_id' => $advisor->id,
            'citizen_id' => $clientCitizen->id,
            'mode' => ConsultationMode::Text,
            'status' => ConsultationSessionStatus::Scheduled,
            'tracking_code' => 'CS-1405-0002',
            'linked_service_id' => (string) Str::uuid(), // Non-existent service ID!
        ]);
    })->toThrow(QueryException::class);
});

it('verifies session messages relationship and cascade deletion (§6.1, TASK-117-T)', function (): void {
    $advisorCitizen = createSessionTestCitizen('09121110033', '0016665566', 'مشاور چت');
    $clientCitizen = createSessionTestCitizen('09122220044', '0016665577', 'شهروند چت');

    $advisor = Advisor::query()->create([
        'citizen_id' => $advisorCitizen->id,
        'display_name' => 'خانم شفیعی',
        'title' => 'مشاور مالیاتی',
        'category' => ConsultationCategory::Tax,
        'license_number' => 'LIC-TAX-1405-88',
    ]);

    $session = ConsultationSession::query()->create([
        'advisor_id' => $advisor->id,
        'citizen_id' => $clientCitizen->id,
        'mode' => ConsultationMode::Text,
        'status' => ConsultationSessionStatus::Active,
        'tracking_code' => 'CS-1405-0003',
    ]);

    $msg1 = $session->messages()->create([
        'sender_type' => SessionMessageSenderType::Citizen,
        'sender_id' => $clientCitizen->id,
        'body' => 'سلام، آیا اظهارنامه تبصره ماده ۱۰۰ برای من مناسب است؟',
    ]);

    $msg2 = $session->messages()->create([
        'sender_type' => SessionMessageSenderType::Advisor,
        'sender_id' => $advisor->id,
        'body' => 'سلام، بستگی به حجم فروش در پایانه فروشگاهی شما دارد.',
    ]);

    expect($session->messages)->toHaveCount(2)
        ->and(SessionMessage::query()->count())->toBe(2);

    // Deleting session cascades and deletes messages
    $session->delete();
    expect(SessionMessage::query()->count())->toBe(0);
});
