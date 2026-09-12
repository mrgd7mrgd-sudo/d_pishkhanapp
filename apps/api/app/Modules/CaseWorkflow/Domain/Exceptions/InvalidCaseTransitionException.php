<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Exceptions;

use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Shared\Errors\ErrorCode;
use DomainException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class InvalidCaseTransitionException extends DomainException implements HttpExceptionInterface
{
    public function __construct(
        public readonly CaseStatus $from,
        public readonly CaseStatus $to,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $msg = $message !== ''
            ? $message
            : "گذار وضعیت پرونده از '{$from->value}' به '{$to->value}' مجاز نیست.";

        parent::__construct($msg, $code, $previous);
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

    public function getErrorCode(): ErrorCode
    {
        return ErrorCode::CASE_INVALID_TRANSITION;
    }
}
