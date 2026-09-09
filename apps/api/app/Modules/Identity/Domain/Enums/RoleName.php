<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum RoleName: string
{
    case CITIZEN = 'citizen';
    case CITIZEN_DELEGATE = 'citizen_delegate';
    case ADVISOR = 'advisor';
    case OFFICE_OPERATOR = 'office_operator';
    case OFFICE_MANAGER = 'office_manager';
    case SYSTEM_ADMIN = 'system_admin';
    case AUDITOR = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::CITIZEN => 'شهروند عادی',
            self::CITIZEN_DELEGATE => 'نماینده شهروند',
            self::ADVISOR => 'مشاور تأییدشده',
            self::OFFICE_OPERATOR => 'اپراتور دفتر پیشخوان',
            self::OFFICE_MANAGER => 'مدیر دفتر پیشخوان',
            self::SYSTEM_ADMIN => 'مدیر سامانه',
            self::AUDITOR => 'بازرس',
        };
    }
}
