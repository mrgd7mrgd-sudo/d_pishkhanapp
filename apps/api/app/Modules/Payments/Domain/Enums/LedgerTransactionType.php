<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain\Enums;

enum LedgerTransactionType: string
{
    case TOPUP = 'topup';
    case SERVICE_FEE = 'service_fee';
    case REFUND = 'refund';
    case PAYOUT = 'payout';
    case CASHBACK = 'cashback';
    case CONSULTATION_FEE = 'consultation_fee';
    case SHIPPING_FEE = 'shipping_fee';

    public function label(): string
    {
        return match ($this) {
            self::TOPUP => 'شارژ کیف پول',
            self::SERVICE_FEE => 'کارمزد ثبت و اجرای پرونده خدمت',
            self::REFUND => 'استرداد وجه به کیف پول',
            self::PAYOUT => 'تسویه حساب و واریز به شبا',
            self::CASHBACK => 'پاداش نقدی و کش‌بک',
            self::CONSULTATION_FEE => 'حق‌المشاوره تخصصی',
            self::SHIPPING_FEE => 'هزینه ارسال و پیک',
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
