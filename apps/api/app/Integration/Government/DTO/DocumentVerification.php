<?php

declare(strict_types=1);

namespace App\Integration\Government\DTO;

final class DocumentVerification
{
    public function __construct(
        public readonly bool $isValid,
        public readonly string $nationalId,
        public readonly string $serial,
        public readonly ?string $status = null,
        public readonly ?string $message = null
    ) {}
}
