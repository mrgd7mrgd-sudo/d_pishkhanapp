<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Exceptions;

use DomainException;

/**
 * Thrown when a double-entry ledger imbalance or corruption is detected (Architecture §8.2, TASK-090).
 */
final class LedgerDiscrepancyException extends DomainException
{
    public function __construct(
        public readonly int $totalDebits,
        public readonly int $totalCredits,
        public readonly int $discrepancy,
        string $message = ''
    ) {
        $msg = $message !== ''
            ? $message
            : "مغایرت بحرانی در تراز کل دفتر کل: مجموع بدهکار ({$totalDebits} ریال) با مجموع بستانکار ({$totalCredits} ریال) برابر نیست (اختلاف: {$discrepancy} ریال).";

        parent::__construct($msg);
    }
}
