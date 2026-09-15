<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Modules\Payments\Domain\FeeSplitCalculator;
use InvalidArgumentException;

beforeEach(function (): void {
    $this->calculator = new FeeSplitCalculator;
});

test('architecture example §8.2: 3,400,000 rials split 50/50 yields exactly 1,700,000 office and 1,700,000 platform', function (): void {
    $split = $this->calculator->calculateServiceFeeSplit(3_400_000, 50.0);

    expect($split['office_share_rials'])->toBe(1_700_000)
        ->and($split['platform_share_rials'])->toBe(1_700_000)
        ->and($split['office_share_rials'] + $split['platform_share_rials'])->toBe(3_400_000);
});

test('custom service office share percentages (e.g. 60%, 75%, 33.3%) strictly balance without loss', function (): void {
    // 60% office share
    $split60 = $this->calculator->calculateServiceFeeSplit(1_000_000, 60.0);
    expect($split60['office_share_rials'])->toBe(600_000)
        ->and($split60['platform_share_rials'])->toBe(400_000)
        ->and($split60['office_share_rials'] + $split60['platform_share_rials'])->toBe(1_000_000);

    // Odd amount with fractional split (e.g. 33.33%)
    $splitOdd = $this->calculator->calculateServiceFeeSplit(1_234_567, 33.33);
    expect($splitOdd['office_share_rials'] + $splitOdd['platform_share_rials'])->toBe(1_234_567);

    // Zero fee
    $splitZero = $this->calculator->calculateServiceFeeSplit(0, 50.0);
    expect($splitZero['office_share_rials'])->toBe(0)
        ->and($splitZero['platform_share_rials'])->toBe(0);
});

test('consultation split: 80% to consultant, 20% to platform (§8.2)', function (): void {
    $split = $this->calculator->calculateConsultationSplit(5_000_000);

    expect($split['consultant_share_rials'])->toBe(4_000_000)
        ->and($split['platform_share_rials'])->toBe(1_000_000)
        ->and($split['consultant_share_rials'] + $split['platform_share_rials'])->toBe(5_000_000);

    // Zero consultation fee
    $splitZero = $this->calculator->calculateConsultationSplit(0);
    expect($splitZero['consultant_share_rials'])->toBe(0)
        ->and($splitZero['platform_share_rials'])->toBe(0);
});

test('delivery split: 100% to courier operator (§8.2)', function (): void {
    $split = $this->calculator->calculateDeliverySplit(450_000);

    expect($split['courier_share_rials'])->toBe(450_000);
});

test('invalid arguments throw InvalidArgumentException', function (): void {
    expect(fn () => $this->calculator->calculateServiceFeeSplit(-100, 50.0))
        ->toThrow(InvalidArgumentException::class, 'هزینه خدمت نمی‌تواند منفی باشد.');

    expect(fn () => $this->calculator->calculateServiceFeeSplit(100_000, 105.0))
        ->toThrow(InvalidArgumentException::class, 'درصد سهم دفتر باید بین ۰ تا ۱۰۰ باشد.');

    expect(fn () => $this->calculator->calculateServiceFeeSplit(100_000, -5.0))
        ->toThrow(InvalidArgumentException::class, 'درصد سهم دفتر باید بین ۰ تا ۱۰۰ باشد.');

    expect(fn () => $this->calculator->calculateConsultationSplit(-1))
        ->toThrow(InvalidArgumentException::class, 'هزینه مشاوره نمی‌تواند منفی باشد.');

    expect(fn () => $this->calculator->calculateDeliverySplit(-1))
        ->toThrow(InvalidArgumentException::class, 'هزینه ارسال نمی‌تواند منفی باشد.');
});

test('property-based test: 1000 random amounts and percentages strictly satisfy SUM(shares) === total_fee with zero lost rials', function (): void {
    for ($i = 0; $i < 1000; $i++) {
        $fee = random_int(1, 100_000_000);
        $percent = random_int(0, 10000) / 100.0; // 0.00% to 100.00%

        $split = $this->calculator->calculateServiceFeeSplit($fee, $percent);

        // Invariant: sum of integer shares MUST exactly equal total fee
        expect($split['office_share_rials'] + $split['platform_share_rials'])->toBe($fee);
        expect($split['office_share_rials'])->toBeGreaterThanOrEqual(0);
        expect($split['platform_share_rials'])->toBeGreaterThanOrEqual(0);

        // Consultation invariant test
        $consultationFee = random_int(1, 50_000_000);
        $cSplit = $this->calculator->calculateConsultationSplit($consultationFee);
        expect($cSplit['consultant_share_rials'] + $cSplit['platform_share_rials'])->toBe($consultationFee);
    }
});
