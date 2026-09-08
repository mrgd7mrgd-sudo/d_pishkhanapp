<?php

declare(strict_types=1);

namespace App\Shared\Errors;

enum ErrorCode: string
{
    case AUTH_OTP_INVALID = 'AUTH_OTP_INVALID';
    case AUTH_OTP_EXPIRED = 'AUTH_OTP_EXPIRED';
    case AUTH_OTP_TOO_MANY = 'AUTH_OTP_TOO_MANY';
    case AUTH_NATIONAL_ID_INVALID = 'AUTH_NATIONAL_ID_INVALID';
    case CASE_INVALID_TRANSITION = 'CASE_INVALID_TRANSITION';
    case CASE_NOT_YOUR_TURN = 'CASE_NOT_YOUR_TURN';
    case CASE_DEADLINE_EXPIRED = 'CASE_DEADLINE_EXPIRED';
    case DISPATCH_NO_OFFICE_AVAILABLE = 'DISPATCH_NO_OFFICE_AVAILABLE';
    case OFFER_EXPIRED = 'OFFER_EXPIRED';
    case OFFER_ALREADY_TAKEN = 'OFFER_ALREADY_TAKEN';
    case WALLET_INSUFFICIENT_BALANCE = 'WALLET_INSUFFICIENT_BALANCE';
    case PAYMENT_GATEWAY_UNAVAILABLE = 'PAYMENT_GATEWAY_UNAVAILABLE';
    case DOC_TYPE_NOT_ALLOWED = 'DOC_TYPE_NOT_ALLOWED';
    case DOC_TOO_LARGE = 'DOC_TOO_LARGE';
    case DELIVERY_OTP_INVALID = 'DELIVERY_OTP_INVALID';
    case DELEGATION_EXPIRED = 'DELEGATION_EXPIRED';
    case DELEGATION_AMOUNT_EXCEEDED = 'DELEGATION_AMOUNT_EXCEEDED';
    case OFFICE_OFFLINE = 'OFFICE_OFFLINE';
    case AI_PROVIDER_UNAVAILABLE = 'AI_PROVIDER_UNAVAILABLE';
    case RATE_LIMITED = 'RATE_LIMITED';

    public function title(): string
    {
        return match ($this) {
            self::AUTH_OTP_INVALID => 'کد یک‌بارمصرف نامعتبر است',
            self::AUTH_OTP_EXPIRED => 'کد یک‌بارمصرف منقضی شده است',
            self::AUTH_OTP_TOO_MANY => 'درخواست بیش از حد مجاز',
            self::AUTH_NATIONAL_ID_INVALID => 'کد ملی وارد شده نامعتبر است',
            self::CASE_INVALID_TRANSITION => 'گذار وضعیت پرونده مجاز نیست',
            self::CASE_NOT_YOUR_TURN => 'در حال حاضر نوبت اقدام شما نیست',
            self::CASE_DEADLINE_EXPIRED => 'مهلت اقدام برای این پرونده به پایان رسیده است',
            self::DISPATCH_NO_OFFICE_AVAILABLE => 'هیچ دفتری برای پذیرش پرونده در دسترس نیست',
            self::OFFER_EXPIRED => 'مهلت پیشنهاد پذیرش به پایان رسیده است',
            self::OFFER_ALREADY_TAKEN => 'این پرونده توسط دفتر دیگری پذیرفته شده است',
            self::WALLET_INSUFFICIENT_BALANCE => 'موجودی کیف پول کافی نیست',
            self::PAYMENT_GATEWAY_UNAVAILABLE => 'درگاه پرداخت در دسترس نیست',
            self::DOC_TYPE_NOT_ALLOWED => 'فرمت مدرک ارسالی مجاز نیست',
            self::DOC_TOO_LARGE => 'حجم فایل بیش از سقف مجاز است',
            self::DELIVERY_OTP_INVALID => 'کد تحویل مرسوله نامعتبر است',
            self::DELEGATION_EXPIRED => 'وکالت یا نمایندگی منقضی شده است',
            self::DELEGATION_AMOUNT_EXCEEDED => 'مبلغ بیش از سقف اختیارات نماینده است',
            self::OFFICE_OFFLINE => 'دفتر منتخب در حال حاضر غیرفعال است',
            self::AI_PROVIDER_UNAVAILABLE => 'سرویس هوش مصنوعی موقتاً در دسترس نیست',
            self::RATE_LIMITED => 'تعداد درخواست‌ها بیش از سقف مجاز است',
        };
    }

    public function defaultDetail(): string
    {
        return match ($this) {
            self::AUTH_OTP_INVALID => 'کد وارد شده صحیح نیست. لطفاً مجدداً بررسی نمایید.',
            self::AUTH_OTP_EXPIRED => 'کد دریافتی منقضی شده است؛ لطفاً درخواست کد جدید دهید.',
            self::AUTH_OTP_TOO_MANY => 'به دلیل ارسال‌های مکرر، لطفاً ۱۵ دقیقه دیگر تلاش فرمایید.',
            self::AUTH_NATIONAL_ID_INVALID => 'رقم کنترلی یا طول کد ملی صحیح نمی‌باشد.',
            self::CASE_INVALID_TRANSITION => 'عملیات درخواستی با وضعیت فعلی پرونده همخوانی ندارد.',
            self::CASE_NOT_YOUR_TURN => 'اقدام بعدی نیازمند بررسی توسط باجه یا ارگان مربوطه است.',
            self::CASE_DEADLINE_EXPIRED => 'فرصت قانونی تکمیل مدارک به اتمام رسیده است.',
            self::DISPATCH_NO_OFFICE_AVAILABLE => 'دفتر فعالی در محدوده مکانی شما با ظرفیت خالی یافت نشد.',
            self::OFFER_EXPIRED => 'زمان قانونی ۳۰ ثانیه‌ای برای پذیرش این پرونده به اتمام رسید.',
            self::OFFER_ALREADY_TAKEN => 'یک همکار دیگر در این دفتر یا دفتر همجوار پیشنهاد را پذیرفت.',
            self::WALLET_INSUFFICIENT_BALANCE => 'لطفاً ابتدا کیف پول خود را شارژ نمایید.',
            self::PAYMENT_GATEWAY_UNAVAILABLE => 'خطا در ارتباط با درگاه بانکی؛ لطفاً دقایقی دیگر تلاش کنید.',
            self::DOC_TYPE_NOT_ALLOWED => 'تنها پسوندهای مجاز اعلام‌شده قابل بارگذاری می‌باشند.',
            self::DOC_TOO_LARGE => 'حداکثر حجم مجاز فایل ۱۰ مگابایت می‌باشد.',
            self::DELIVERY_OTP_INVALID => 'کد پیامک‌شده به گیرنده را به درستی وارد فرمایید.',
            self::DELEGATION_EXPIRED => 'تاریخ اعتبار نمایندگی منقضی گردیده است.',
            self::DELEGATION_AMOUNT_EXCEEDED => 'تراکنش درخواستی فراتر از سقف ریالی وکیل است.',
            self::OFFICE_OFFLINE => 'ساعات کاری دفتر به پایان رسیده یا وضعیت آن آفلاین است.',
            self::AI_PROVIDER_UNAVAILABLE => 'پاسخ هوشمند موقتاً آماده نشد؛ جستجوی سنتی در دسترس است.',
            self::RATE_LIMITED => 'سرعت ارسال درخواست‌ها بیش از حد مجاز سامانه است.',
        };
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::AUTH_OTP_INVALID, self::AUTH_NATIONAL_ID_INVALID, self::DOC_TYPE_NOT_ALLOWED => 422,
            self::AUTH_OTP_EXPIRED, self::CASE_DEADLINE_EXPIRED, self::OFFER_EXPIRED => 410,
            self::AUTH_OTP_TOO_MANY, self::RATE_LIMITED => 429,
            self::WALLET_INSUFFICIENT_BALANCE => 402,
            self::CASE_NOT_YOUR_TURN, self::DELEGATION_EXPIRED, self::DELEGATION_AMOUNT_EXCEEDED => 403,
            self::OFFER_ALREADY_TAKEN, self::CASE_INVALID_TRANSITION => 409,
            self::DOC_TOO_LARGE => 413,
            self::PAYMENT_GATEWAY_UNAVAILABLE, self::AI_PROVIDER_UNAVAILABLE, self::DISPATCH_NO_OFFICE_AVAILABLE, self::OFFICE_OFFLINE => 503,
            self::DELIVERY_OTP_INVALID => 400,
        };
    }
}
