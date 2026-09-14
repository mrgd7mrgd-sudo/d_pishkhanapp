<?php

declare(strict_types=1);

namespace App\Integration\Government\DTO;

final class ShipmentRequest
{
    public function __construct(
        public readonly string $caseId,
        public readonly string $originOfficeId,
        public readonly string $destinationPostalCode,
        public readonly string $destinationAddress,
        public readonly string $recipientName,
        public readonly string $recipientMobile,
        public readonly string $packageType = 'document'
    ) {}
}
