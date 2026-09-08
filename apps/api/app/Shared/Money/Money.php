<?php

declare(strict_types=1);

namespace App\Shared\Money;

use InvalidArgumentException;

final class Money
{
    private readonly int $amountRials;

    public function __construct(int $amountRials)
    {
        if ($amountRials < 0) {
            throw new InvalidArgumentException('Money amount in rials cannot be negative.');
        }

        $this->amountRials = $amountRials;
    }

    public static function fromRials(int $amountRials): self
    {
        return new self($amountRials);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function getAmountRials(): int
    {
        return $this->amountRials;
    }

    public function add(self $other): self
    {
        return new self($this->amountRials + $other->amountRials);
    }

    public function subtract(self $other): self
    {
        if ($this->amountRials < $other->amountRials) {
            throw new InvalidArgumentException('Cannot subtract larger money amount resulting in negative balance.');
        }

        return new self($this->amountRials - $other->amountRials);
    }

    public function percentage(int $basisPoints): self
    {
        if ($basisPoints < 0) {
            throw new InvalidArgumentException('Basis points cannot be negative.');
        }

        $result = intdiv(($this->amountRials * $basisPoints) + 5000, 10000);

        return new self($result);
    }

    public function format(): string
    {
        return number_format($this->amountRials).' ریال';
    }

    public function formatTomans(): string
    {
        $tomans = intdiv($this->amountRials, 10);

        return number_format($tomans).' تومان';
    }

    public function equals(self $other): bool
    {
        return $this->amountRials === $other->amountRials;
    }
}
