<?php

declare(strict_types=1);

namespace App\Integration\Government\DTO;

final class ShahkarResult
{
    public function __construct(
        public readonly bool $isMatched,
        public readonly string $nationalId,
        public readonly string $mobile,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $errorMessage = null
    ) {}
}
