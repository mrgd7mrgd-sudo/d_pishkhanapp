<?php

declare(strict_types=1);

use App\Shared\Money\Money;

it('creates money instance from integer rials', function (): void {
    $money = Money::fromRials(500000);

    expect($money->getAmountRials())->toBe(500000)
        ->and(is_int($money->getAmountRials()))->toBeTrue();
});

it('creates zero money instance', function (): void {
    $zero = Money::zero();

    expect($zero->getAmountRials())->toBe(0);
});

it('disallows negative money amounts on construction', function (): void {
    expect(fn () => new Money(-100))
        ->toThrow(InvalidArgumentException::class, 'Money amount in rials cannot be negative.');
});

it('adds money objects correctly without float conversion', function (): void {
    $m1 = Money::fromRials(150000);
    $m2 = Money::fromRials(250000);

    $sum = $m1->add($m2);

    expect($sum->getAmountRials())->toBe(400000)
        ->and(is_int($sum->getAmountRials()))->toBeTrue();
});

it('subtracts money objects correctly', function (): void {
    $m1 = Money::fromRials(500000);
    $m2 = Money::fromRials(200000);

    $diff = $m1->subtract($m2);

    expect($diff->getAmountRials())->toBe(300000);
});

it('disallows subtraction that results in negative money', function (): void {
    $m1 = Money::fromRials(100000);
    $m2 = Money::fromRials(200000);

    expect(fn () => $m1->subtract($m2))
        ->toThrow(InvalidArgumentException::class, 'Cannot subtract larger money amount resulting in negative balance.');
});

it('calculates basis points percentages correctly with integer rounding', function (): void {
    $total = Money::fromRials(1000000); // 1,000,000 Rials

    // 9% tax = 900 basis points
    $tax = $total->percentage(900);
    expect($tax->getAmountRials())->toBe(90000);

    // 15.5% share = 1550 basis points
    $share = $total->percentage(1550);
    expect($share->getAmountRials())->toBe(155000);
});

it('disallows negative basis points', function (): void {
    $m = Money::fromRials(100000);

    expect(fn () => $m->percentage(-1))
        ->toThrow(InvalidArgumentException::class, 'Basis points cannot be negative.');
});

it('formats money as Persian Rials', function (): void {
    $money = Money::fromRials(1250000);

    expect($money->format())->toBe('1,250,000 ریال');
});

it('formats money as Persian Tomans', function (): void {
    $money = Money::fromRials(1250000);

    expect($money->formatTomans())->toBe('125,000 تومان');
});

it('checks equality between money objects', function (): void {
    $m1 = Money::fromRials(300000);
    $m2 = Money::fromRials(300000);
    $m3 = Money::fromRials(400000);

    expect($m1->equals($m2))->toBeTrue()
        ->and($m1->equals($m3))->toBeFalse();
});
