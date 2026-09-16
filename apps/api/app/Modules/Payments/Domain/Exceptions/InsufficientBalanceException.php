<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use App\Shared\Errors\ErrorCode;
use DomainException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Thrown when a citizen wallet balance is insufficient for the requested operation.
 *
 * Carries domain data only (required/available rials); the central exception
 * Handler (App\Exceptions\Handler) renders the RFC 7807 HTTP response.
 */
final class InsufficientBalanceException extends DomainException implements HttpExceptionInterface
{
    public function __construct(
        public readonly int $requiredRials,
        public readonly int $availableRials,
    ) {
        parent::__construct('موجودی کیف پول برای این عملیات کافی نیست.');
    }

    public function getStatusCode(): int
    {
        return ErrorCode::WALLET_INSUFFICIENT_BALANCE->httpStatus();
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
        return ErrorCode::WALLET_INSUFFICIENT_BALANCE;
    }

    /**
     * Supplementary RFC 7807 meta rendered by the exception Handler.
     *
     * @return array<string, int|string>
     */
    public function getMeta(): array
    {
        return [
            'required_rials' => $this->requiredRials,
            'available_rials' => $this->availableRials,
            'shortfall_rials' => max(0, $this->requiredRials - $this->availableRials),
            'topup_url' => '/api/v1/wallet/topup',
        ];
    }
}
