<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Exceptions;

use App\Shared\Errors\ErrorCode;
use DomainException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Thrown when a legal delegation cannot authorize the requested operation
 * (not found, not owned by the delegate, expired, service outside its scope,
 * or the amount exceeds the delegation cap).
 *
 * Carries the domain ErrorCode only; the central exception Handler renders
 * the RFC 7807 HTTP response.
 */
final class DelegationNotUsableException extends DomainException implements HttpExceptionInterface
{
    public function __construct(
        public readonly ErrorCode $errorCode,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message !== '' ? $message : $errorCode->defaultDetail(), $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->errorCode->httpStatus();
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
        return $this->errorCode;
    }
}
