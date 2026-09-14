<?php

declare(strict_types=1);

namespace App\Integration\Government\DTO;

final class PostalAddress
{
    public function __construct(
        public readonly bool $isValid,
        public readonly string $postalCode,
        public readonly string $province,
        public readonly string $city,
        public readonly string $address,
        public readonly ?string $buildingNumber = null
    ) {}
}
