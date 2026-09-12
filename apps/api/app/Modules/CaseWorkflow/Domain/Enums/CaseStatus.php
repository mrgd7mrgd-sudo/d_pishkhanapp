<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Enums;

/**
 * Case Status Enum (Architecture §3.5, §5.4, §6.1, D-08, TASK-049).
 * Single source of truth for the 11 case lifecycle statuses.
 */
enum CaseStatus: string
{
    case DRAFT = 'draft';
    case SEARCHING_OFFICE = 'searching_office';
    case ASSIGNED_TO_OFFICE = 'assigned_to_office';
    case EXPERT_REVIEW = 'expert_review';
    case ACTION_REQUIRED = 'action_required';
    case GOVERNMENT_INQUIRY = 'government_inquiry';
    case READY_FOR_ISSUE = 'ready_for_issue';
    case DELIVERING = 'delivering';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'پیش‌نویس اولیه',
            self::SEARCHING_OFFICE => 'در جستجوی دفتر پیشخوان',
            self::ASSIGNED_TO_OFFICE => 'واگذار شده به دفتر',
            self::EXPERT_REVIEW => 'در حال بررسی کارشناس',
            self::ACTION_REQUIRED => 'نیازمند اقدام شهروند (نقص مدرک)',
            self::GOVERNMENT_INQUIRY => 'در انتظار استعلام دولتی',
            self::READY_FOR_ISSUE => 'آماده صدور و تحویل',
            self::DELIVERING => 'در حال ارسال با پیک/پست',
            self::COMPLETED => 'تکمیل و تحویل شده',
            self::REJECTED => 'رد شده قطعی',
            self::CANCELLED => 'لغو شده',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::REJECTED, self::CANCELLED => true,
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
