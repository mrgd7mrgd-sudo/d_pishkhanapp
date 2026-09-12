<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Enums;

enum GovInquiryProvider: string
{
    case SHAHKAR = 'shahkar';
    case CIVIL_REGISTRY = 'civil_registry';
    case POST = 'post';

    public function label(): string
    {
        return match ($this) {
            self::SHAHKAR => 'شاهکار (تطبیق شماره موبایل و کدملی)',
            self::CIVIL_REGISTRY => 'ثبت احوال (اصالت هویت)',
            self::POST => 'شرکت ملی پست (تأییدیه کد پستی و آدرس)',
        };
    }
}
