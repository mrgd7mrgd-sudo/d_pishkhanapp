<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Exceptions;

use DomainException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class SlotCapacityExceededException extends DomainException implements HttpExceptionInterface
{
    public function __construct(
        string $message = 'ظرفیت این بازه زمانی تکمیل شده است.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return 409;
    }

    /**
     * @return array<string, string|list<string>>
     */
    public function getHeaders(): array
    {
        return [];
    }

    public function getErrorCode(): string
    {
        return 'SLOT_CAPACITY_EXCEEDED';
    }
}
