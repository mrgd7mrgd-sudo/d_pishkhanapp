<?php

declare(strict_types=1);

use App\Modules\Consultation\Domain\Enums\AdvisorApplicationStatus;
use App\Modules\Consultation\Domain\Enums\ConsultationCategory;
use App\Modules\Consultation\Domain\Models\Advisor;
use App\Modules\Consultation\Domain\Models\AdvisorReview;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createConsultationCitizen(string $mobile, string $nationalId, string $name): Citizen
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

it('verifies all 6 consultation categories match packages/domain exactly (§6.1, §6.3, TASK-116, TASK-116-T)', function (): void {
    $tsPath = realpath(base_path('../../packages/domain/src/consultation.ts'));
    expect($tsPath)->not->toBeFalse();

    $content = file_get_contents((string) $tsPath);
    preg_match('/export const CONSULTATION_CATEGORIES = \[(.*?)\] as const;/s', $content, $matches);
    expect($matches)->not->toBeEmpty();

    preg_match_all("/'([^']+)'/", $matches[1], $catMatches);
    $tsCategories = $catMatches[1];

    $phpCategories = ConsultationCategory::values();

    expect($phpCategories)->toBe($tsCategories)
        ->and(count($phpCategories))->toBe(6)
        ->and($phpCategories)->toContain(
            'tax',
            'insurance_labor',
            'legal_registry',
            'tenders_permits',
            'municipal',
            'business_startup'
        );
});

it('verifies all three rates are positive integer Rials and persists correctly in database (§6.1, TASK-116-T)', function (): void {
    $citizen = createConsultationCitizen('09121112233', '0019998881', 'مشاور نمونه');

    $advisor = Advisor::query()->create([
        'citizen_id' => $citizen->id,
        'display_name' => 'دکتر رضا رضایی',
        'title' => 'مشاور ارشد مالیاتی',
        'category' => ConsultationCategory::Tax,
        'license_number' => 'LIC-TAX-1405-01',
        'experience_years' => 12,
        'price_text_chat_rials' => 1500000,
        'price_phone_per_minute_rials' => 50000,
        'price_deep_review_rials' => 10000000,
        'application_status' => AdvisorApplicationStatus::Approved,
    ]);

    expect($advisor->price_text_chat_rials)->toBe(1500000)
        ->and(is_int($advisor->price_text_chat_rials))->toBeTrue()
        ->and($advisor->price_phone_per_minute_rials)->toBe(50000)
        ->and(is_int($advisor->price_phone_per_minute_rials))->toBeTrue()
        ->and($advisor->price_deep_review_rials)->toBe(10000000)
        ->and(is_int($advisor->price_deep_review_rials))->toBeTrue()
        ->and($advisor->category)->toBe(ConsultationCategory::Tax)
        ->and($advisor->application_status)->toBe(AdvisorApplicationStatus::Approved);
});

it('verifies overall rating is derived from accuracy, eloquence, and patience (§6.1, §6.3, TASK-116-T)', function (): void {
    $citizenAdvisor = createConsultationCitizen('09121112244', '0019998882', 'مشاور حقوقی');
    $citizenReviewer = createConsultationCitizen('09121112255', '0019998883', 'شهروند نظردهنده');

    $advisor = Advisor::query()->create([
        'citizen_id' => $citizenAdvisor->id,
        'display_name' => 'مریم احمدی',
        'title' => 'وکیل پایه یک دادگستری',
        'category' => ConsultationCategory::LegalRegistry,
        'license_number' => 'LIC-LAW-1405-02',
    ]);

    // Review 1: Accuracy=4.5, Eloquence=5.0, Patience=4.0 -> Overall = (4.5 + 5.0 + 4.0)/3 = 4.50
    $overall1 = AdvisorReview::deriveOverallRating(4.5, 5.0, 4.0);
    expect($overall1)->toBe(4.50);

    AdvisorReview::query()->create([
        'advisor_id' => $advisor->id,
        'citizen_id' => $citizenReviewer->id,
        'rating_accuracy' => 4.5,
        'rating_eloquence' => 5.0,
        'rating_patience' => 4.0,
        'overall_rating' => $overall1,
        'comment' => 'مشاوره بسیار دقیق و عالی بود.',
    ]);

    // Review 2: Accuracy=4.0, Eloquence=4.0, Patience=4.0 -> Overall = 4.00
    $overall2 = AdvisorReview::deriveOverallRating(4.0, 4.0, 4.0);
    expect($overall2)->toBe(4.00);

    AdvisorReview::query()->create([
        'advisor_id' => $advisor->id,
        'citizen_id' => $citizenReviewer->id,
        'rating_accuracy' => 4.0,
        'rating_eloquence' => 4.0,
        'rating_patience' => 4.0,
        'overall_rating' => $overall2,
        'comment' => 'خوب بود و کارم راه افتاد.',
    ]);

    $advisor->recalculateRatings();
    $advisor->refresh();

    // Average accuracy: (4.5 + 4.0)/2 = 4.25
    // Average eloquence: (5.0 + 4.0)/2 = 4.50
    // Average patience: (4.0 + 4.0)/2 = 4.00
    // Overall: (4.25 + 4.50 + 4.00)/3 = 4.25
    expect($advisor->review_count)->toBe(2)
        ->and($advisor->rating_accuracy)->toBe(4.25)
        ->and($advisor->rating_eloquence)->toBe(4.50)
        ->and($advisor->rating_patience)->toBe(4.00)
        ->and($advisor->rating)->toBe(4.25);
});

it('verifies specialties relationship and unique license number constraint (§6.1, TASK-116-T)', function (): void {
    $citizen = createConsultationCitizen('09121112266', '0019998884', 'مشاور بیمه');

    $advisor = Advisor::query()->create([
        'citizen_id' => $citizen->id,
        'display_name' => 'مهندس کاظمی',
        'title' => 'کارشناس تامین اجتماعی',
        'category' => ConsultationCategory::InsuranceLabor,
        'license_number' => 'LIC-INS-1405-99',
    ]);

    $advisor->specialties()->create(['specialty_name' => 'دعاوی اداره کار']);
    $advisor->specialties()->create(['specialty_name' => 'مفاصاحساب ماده ۳۸']);

    expect($advisor->specialties)->toHaveCount(2)
        ->and($advisor->specialties->pluck('specialty_name')->toArray())
        ->toContain('دعاوی اداره کار', 'مفاصاحساب ماده ۳۸');

    // Duplicate license number must fail unique constraint
    expect(function () use ($citizen): void {
        Advisor::query()->create([
            'citizen_id' => $citizen->id,
            'display_name' => 'مشاور دیگر با شماره پروانه تکراری',
            'title' => 'عنوان',
            'category' => ConsultationCategory::InsuranceLabor,
            'license_number' => 'LIC-INS-1405-99', // Duplicate!
        ]);
    })->toThrow(QueryException::class);
});
