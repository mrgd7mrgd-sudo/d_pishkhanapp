<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Enums;

enum GovInquiryStatus: string
{
    case QUEUED = 'queued';
    case IN_PROGRESS = 'in_progress';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case MISMATCH = 'mismatch';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'در صف استعلام',
            self::IN_PROGRESS => 'در حال استعلام',
            self::SUCCEEDED => 'استعلام موفق',
            self::FAILED => 'خطا در استعلام',
            self::MISMATCH => 'عدم تطابق اطلاعات',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::SUCCEEDED, self::FAILED, self::MISMATCH], true);
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCEEDED;
    }
}
