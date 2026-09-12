<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use DomainException;

final class UnbalancedTransactionException extends DomainException
{
    public function __construct(
        public readonly int $totalDebits = 0,
        public readonly int $totalCredits = 0,
        string $message = ''
    ) {
        $msg = $message !== ''
            ? $message
            : "تراکنش دفتر کل نامتوازن است: مجموع بدهکار ({$totalDebits} ریال) با مجموع بستانکار ({$totalCredits} ریال) برابر نیست.";

        parent::__construct($msg);
    }
}
