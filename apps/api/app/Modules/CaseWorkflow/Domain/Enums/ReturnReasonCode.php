<?php

declare(strict_types=1);

namespace App\Modules\CaseWorkflow\Domain\Enums;

/**
 * Return Reason Code Enum (Architecture §6.1, §6.3, TASK-051).
 * Exactly 10 standardized rejection/return reason codes.
 */
enum ReturnReasonCode: string
{
    case DOC_BLUR = 'DOC_BLUR';
    case DOC_CROP = 'DOC_CROP';
    case DOC_EXPIRED = 'DOC_EXPIRED';
    case DOC_MISMATCH = 'DOC_MISMATCH';
    case DOC_MISSING = 'DOC_MISSING';
    case DOC_WRONG_TYPE = 'DOC_WRONG_TYPE';
    case FORM_INVALID = 'FORM_INVALID';
    case INQUIRY_MISMATCH = 'INQUIRY_MISMATCH';
    case ELIGIBILITY_FAIL = 'ELIGIBILITY_FAIL';
    case PRESENCE_REQUIRED = 'PRESENCE_REQUIRED';

    public function label(): string
    {
        return match ($this) {
            self::DOC_BLUR => 'تصویر تار / مندرجات ناخوانا',
            self::DOC_CROP => 'برش ناقص / لبه‌های سند بریده شده',
            self::DOC_EXPIRED => 'سند منقضی / بدون اعتبار زمانی',
            self::DOC_MISMATCH => 'مغایرت مندرجات مدرک با فرم',
            self::DOC_MISSING => 'نقص مدرک تکمیلی الزامی',
            self::DOC_WRONG_TYPE => 'فایل یا سند نامرتبط',
            self::FORM_INVALID => 'خطای ساختاری در فیلدهای فرم',
            self::INQUIRY_MISMATCH => 'مغایرت با سامانه بالادست دولتی',
            self::ELIGIBILITY_FAIL => 'عدم احراز شرایط قانونی خدمت',
            self::PRESENCE_REQUIRED => 'نیاز به مراجعه حضوری و احراز بیومتریک',
        };
    }

    public function defaultMessage(): string
    {
        return match ($this) {
            self::DOC_BLUR => 'تصویر مدرک بارگذاری‌شده تار است و شماره سریال یا مندرجات خوانا نیست. لطفاً در نور کافی و بدون لرزش مجدداً عکس بگیرید.',
            self::DOC_CROP => 'لبه‌ها و کادر چهارگانه سند در تصویر مشخص نیست. کل سند باید کامل و بدون زاویه در کادر قرار گیرد.',
            self::DOC_EXPIRED => 'تاریخ اعتبار قانونی مدرک منقضی شده است. لطفاً نسخه تمدیدشده یا تاییدیه معتبر را بارگذاری نمایید.',
            self::DOC_MISMATCH => 'اطلاعات واردشده در فرم با مندرجات مدرک اسکن‌شده مغایرت دارد.',
            self::DOC_MISSING => 'مدرک الزامی این خدمت ارائه نشده است. لطفاً پیوست نمایید.',
            self::DOC_WRONG_TYPE => 'فایل بارگذاری‌شده با عنوان مدرک درخواستی همخوانی ندارد.',
            self::FORM_INVALID => 'مقادیر ورودی با استانداردهای سازمانی مطابقت ندارد.',
            self::INQUIRY_MISMATCH => 'اطلاعات با پایگاه داده سامانه مرجع همخوانی ندارد. ابتدا نسبت به اصلاح رکورد ثبتی اقدام نمایید.',
            self::ELIGIBILITY_FAIL => 'شرایط قانونی دریافت این خدمت احراز نگردید.',
            self::PRESENCE_REQUIRED => 'این خدمت به دلیل الزامات هویتی نیازمند حضور شخص متقاضی جهت ثبت اثر انگشت و تطبیق چهره در باجه دفتر پیشخوان است.',
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
