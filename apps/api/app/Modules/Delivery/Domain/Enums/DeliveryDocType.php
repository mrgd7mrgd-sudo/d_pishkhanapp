<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Domain\Enums;

/**
 * DeliveryDocType Enum (Architecture §6.1, §6.3, TASK-094).
 */
enum DeliveryDocType: string
{
    case SMART_CARD = 'smart_card';
    case IDENTITY_BOOKLET = 'identity_booklet';
    case OFFICIAL_CERTIFICATE = 'official_certificate';
    case SEALED_DOSSIER = 'sealed_dossier';
    case BUSINESS_LICENSE = 'business_license';
    case POSTAL_PACKET = 'postal_packet';

    public function label(): string
    {
        return match ($this) {
            self::SMART_CARD => 'کارت هوشمند ملی / سوخت',
            self::IDENTITY_BOOKLET => 'شناسنامه / گذرنامه',
            self::OFFICIAL_CERTIFICATE => 'گواهی‌نامه رسمی / دانشنامه',
            self::SEALED_DOSSIER => 'پرونده پلمب‌شده محرمانه',
            self::BUSINESS_LICENSE => 'پروانه کسب / مجوز صنفی',
            self::POSTAL_PACKET => 'بسته پستی استاندارد مدارک',
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
