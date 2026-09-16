<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Enums;

enum ConsultationMode: string
{
    case Text = 'text';
    case Call = 'call';
    case CaseReview = 'case_review';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function labelFarsi(): string
    {
        return match ($this) {
            self::Text => 'چت متنی و پرسش و پاسخ',
            self::Call => 'تماس تلفنی یا صوتی مستقیم',
            self::CaseReview => 'بررسی تخصصی اوراق و لوایح پرونده',
        };
    }
}
