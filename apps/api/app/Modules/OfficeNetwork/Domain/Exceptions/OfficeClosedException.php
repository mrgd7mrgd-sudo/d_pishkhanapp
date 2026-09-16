<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Domain\Exceptions;

use DomainException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class OfficeClosedException extends DomainException implements HttpExceptionInterface
{
    public function __construct(
        string $message = 'دفتر در تاریخ انتخابی تعطیل است.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return 422;
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
        return 'OFFICE_CLOSED';
    }
}
