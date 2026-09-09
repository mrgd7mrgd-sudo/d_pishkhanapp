<?php

declare(strict_types=1);

namespace Tests\Feature\ServiceCatalog;

use App\Modules\ServiceCatalog\Domain\Enums\ServiceTag;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use App\Modules\ServiceCatalog\Domain\Models\ServiceCategory;
use App\Shared\Text\PersianNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('normalizes Arabic spelling of Yeh and Kaf to Persian characters identically', function (): void {
    $persianText = 'کارت ملی هوشمند';
    $arabicText = 'كارت ملي هوشمند';

    $normalizedPersian = PersianNormalizer::normalize($persianText);
    $normalizedArabic = PersianNormalizer::normalize($arabicText);

    expect($normalizedArabic)->toBe($normalizedPersian)
        ->and($normalizedArabic)->toBe('کارت ملی هوشمند');
});

it('removes diacritics and converts half-spaces to regular space', function (): void {
    $rawText = 'پیش‌خوانِ هُوّشْمَنْدِ مِلّی';
    $normalized = PersianNormalizer::normalize($rawText);

    expect($normalized)->toBe('پیش خوان هوشمند ملی');
});

it('converts Persian and Arabic digits to ASCII digits', function (): void {
    $persianDigits = 'تلفن: ۰۲۱-۸۸۸۸۱۲۳۴';
    $arabicDigits = 'تلفن: ٠٢١-٨٨٨٨١٢٣٤';

    expect(PersianNormalizer::normalize($persianDigits))->toBe('تلفن: 021-88881234')
        ->and(PersianNormalizer::normalize($arabicDigits))->toBe('تلفن: 021-88881234');
});

it('matches no-space query with trigram similarity (کارتملی vs کارت ملی)', function (): void {
    $target = 'کارت ملی';
    $noSpaceQuery = 'کارتملی';

    $similarity = PersianNormalizer::trigramSimilarity($target, $noSpaceQuery);

    // Trigram similarity must be high (>= 0.5)
    expect($similarity)->toBeGreaterThanOrEqual(0.5);
});

it('matches typo with fuzzy trigram similarity (کارط ملی vs کارت ملی)', function (): void {
    $target = 'کارت ملی';
    $typoQuery = 'کارط ملی';

    $similarity = PersianNormalizer::trigramSimilarity($target, $typoQuery);

    // Trigram similarity for 1 typo character in 2-word phrase must be >= 0.5
    expect($similarity)->toBeGreaterThanOrEqual(0.5);

    // Completely different phrase should have low similarity (< 0.3)
    $unrelated = 'گواهینامه رانندگی';
    expect(PersianNormalizer::trigramSimilarity($target, $unrelated))->toBeLessThan(0.3);
});

it('verifies 20 sample strings normalization parity matching domain rules', function (): void {
    $samples = [
        ['raw' => 'كارت ملي', 'expected' => 'کارت ملی'],
        ['raw' => 'ثبتِ اَحْوَال', 'expected' => 'ثبت احوال'],
        ['raw' => 'پیش‌خوان', 'expected' => 'پیش خوان'],
        ['raw' => 'گذرنامهٔ فوری', 'expected' => 'گذرنامه فوری'],
        ['raw' => 'شناسنامه ۰۱۲۳۴۵۶۷۸۹', 'expected' => 'شناسنامه 0123456789'],
        ['raw' => 'ماليات بر ارزش افزوده', 'expected' => 'مالیات بر ارزش افزوده'],
        ['raw' => 'شماره پرونده: ٠١٢٣٤', 'expected' => 'شماره پرونده: 01234'],
        ['raw' => 'وكالت‌نامه رسمى', 'expected' => 'وکالت نامه رسمی'],
        ['raw' => 'شهردارى منطقه ۲', 'expected' => 'شهرداری منطقه 2'],
        ['raw' => 'پروانه كسب و كار', 'expected' => 'پروانه کسب و کار'],
        ['raw' => 'بيمه تأمين اجتماعى', 'expected' => 'بیمه تامین اجتماعی'],
        ['raw' => 'استعلامِ گواهينامه', 'expected' => 'استعلام گواهینامه'],
        ['raw' => 'سندِ ملكيِ تك‌برگ', 'expected' => 'سند ملکی تک برگ'],
        ['raw' => 'كارت هوشمند   سوخت  ', 'expected' => 'کارت هوشمند سوخت'],
        ['raw' => 'تعويض كارت پايان خدمت', 'expected' => 'تعویض کارت پایان خدمت'],
        ['raw' => 'نظام وظيفه عمومى', 'expected' => 'نظام وظیفه عمومی'],
        ['raw' => 'عوارضِ خودرو ١٤٠٣', 'expected' => 'عوارض خودرو 1403'],
        ['raw' => 'خلافي خودرو و موتور', 'expected' => 'خلافی خودرو و موتور'],
        ['raw' => 'تأییدیهٔ تحصیلی', 'expected' => 'تاییدیه تحصیلی'],
        ['raw' => 'صدورِ مجدد شناسنامه', 'expected' => 'صدور مجدد شناسنامه'],
    ];

    expect($samples)->toHaveCount(20);

    foreach ($samples as $index => $sample) {
        $actual = PersianNormalizer::normalize($sample['raw']);
        expect($actual)->toBe($sample['expected'], "Sample #{$index} failed: [{$sample['raw']}]");
    }
});

it('searches service catalog with Persian normalization in database queries', function (): void {
    $category = ServiceCategory::query()->create([
        'id' => 'civil-reg',
        'title' => 'ثبت احوال',
    ]);

    $service = Service::query()->create([
        'category_id' => $category->id,
        'slug' => 'national-smart-card',
        'title' => 'صدور کارت هوشمند ملی',
        'description' => 'درخواست صدور کارت هوشمند ملی برای تمامی شهروندان',
        'department' => 'سازمان ثبت احوال کشور',
        'tags' => [ServiceTag::ONLINE->value],
        'fee_rials' => 15000000,
    ]);

    $driver = config('database.connections.'.config('database.default').'.driver');

    if ($driver === 'pgsql') {
        // Test Full-Text Search with 'persian' configuration
        $results = DB::select("
            SELECT id, title
            FROM services
            WHERE search_vector @@ to_tsquery('persian', 'کارت & ملی')
        ");

        expect($results)->toHaveCount(1)
            ->and($results[0]->id)->toBe($service->id);

        // Test with Arabic spelling in query: to_tsquery with normalised text
        $normalizedQuery = PersianNormalizer::normalize('كارت ملي');
        $tsQuery = implode(' & ', explode(' ', $normalizedQuery));

        $arabicResults = DB::select("
            SELECT id, title
            FROM services
            WHERE search_vector @@ to_tsquery('persian', '{$tsQuery}')
        ");

        expect($arabicResults)->toHaveCount(1)
            ->and($arabicResults[0]->id)->toBe($service->id);
    } else {
        // SQLite fallback using PersianNormalizer search
        $searchQuery = 'كارت ملي'; // Arabic query
        $normalizedSearch = PersianNormalizer::normalize($searchQuery);
        $searchTokens = explode(' ', $normalizedSearch);

        $services = Service::query()->get();
        $matched = $services->filter(function (Service $s) use ($searchTokens): bool {
            $normTitle = PersianNormalizer::normalize($s->title);
            foreach ($searchTokens as $token) {
                if (! str_contains($normTitle, $token)) {
                    return false;
                }
            }

            return true;
        });

        expect($matched)->toHaveCount(1)
            ->and($matched->first()?->id)->toBe($service->id);
    }
});
