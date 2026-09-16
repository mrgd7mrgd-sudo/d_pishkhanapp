<?php

declare(strict_types=1);

namespace App\Modules\Consultation\Domain\Enums;

enum ConsultationCategory: string
{
    case Tax = 'tax';
    case InsuranceLabor = 'insurance_labor';
    case LegalRegistry = 'legal_registry';
    case TendersPermits = 'tenders_permits';
    case Municipal = 'municipal';
    case BusinessStartup = 'business_startup';

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
            self::Tax => 'مشاوره مالیاتی و دارایی',
            self::InsuranceLabor => 'بیمه تامین اجتماعی و قانون کار',
            self::LegalRegistry => 'حقوقی، ثبت شرکت‌ها و برند',
            self::TendersPermits => 'مناقصات، مزایدات و رتبه‌بندی',
            self::Municipal => 'شهرداری، کمیسیون ماده ۱۰۰ و املاک',
            self::BusinessStartup => 'مجوزهای کسب‌وکار و درگاه ملی مجوزها',
        };
    }
}
