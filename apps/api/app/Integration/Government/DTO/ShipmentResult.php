<?php

declare(strict_types=1);

namespace App\Integration\Government\DTO;

final class ShipmentResult
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly ?string $barcode = null,
        public readonly ?string $trackingUrl = null,
        public readonly ?string $errorMessage = null
    ) {}
}
