<?php

declare(strict_types=1);

namespace App\Integration\Government\DTO;

final class PersonSummary
{
    public function __construct(
        public readonly bool $isAlive,
        public readonly string $nationalId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $fatherName,
        public readonly string $gender,
        public readonly ?string $birthDate = null,
        public readonly bool $isEligible = true,
        public readonly ?string $ineligibleReason = null
    ) {}
}
