<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum SystemPermission: string
{
    case SERVICES_VIEW = 'services.view';
    case SERVICE_CATALOG_MANAGE = 'service_catalog.manage';
    case CASES_CREATE = 'cases.create';
    case CASES_VIEW = 'cases.view';
    case CASES_RETURN = 'cases.return';
    case CASES_REJECT = 'cases.reject';
    case CASES_COMPLETE = 'cases.complete';
    case DOCUMENTS_DOWNLOAD = 'documents.download';
    case DISPATCH_ACCEPT = 'dispatch.accept';
    case DELIVERIES_CREATE = 'deliveries.create';
    case DELIVERIES_CONFIRM_OTP = 'deliveries.confirm_otp';
    case FINANCES_VIEW = 'finances.view';
    case REVIEWS_REPLY = 'reviews.reply';
    case OFFICE_PROFILE_UPDATE = 'office.profile.update';
    case OFFICE_OPERATORS_MANAGE = 'office.operators.manage';
    case OFFICE_REGISTER_APPROVE = 'office.register.approve';
    case ADVISOR_REGISTER_APPROVE = 'advisor.register.approve';
    case CONSULTATIONS_CONDUCT = 'consultations.conduct';
    case DELEGATION_CREATE = 'delegation.create';
    case AUDIT_LOGS_VIEW = 'audit_logs.view';

    public function label(): string
    {
        return match ($this) {
            self::SERVICES_VIEW => 'مشاهده کاتالوگ خدمات',
            self::SERVICE_CATALOG_MANAGE => 'ویرایش کاتالوگ خدمات',
            self::CASES_CREATE => 'ثبت پرونده',
            self::CASES_VIEW => 'مشاهده پرونده',
            self::CASES_RETURN => 'بازگشت پرونده',
            self::CASES_REJECT => 'رد نهایی پرونده',
            self::CASES_COMPLETE => 'تکمیل پرونده',
            self::DOCUMENTS_DOWNLOAD => 'دانلود مدرک پرونده',
            self::DISPATCH_ACCEPT => 'پذیرش پیشنهاد ارجاع',
            self::DELIVERIES_CREATE => 'ایجاد درخواست پیک',
            self::DELIVERIES_CONFIRM_OTP => 'تأیید رمز تحویل',
            self::FINANCES_VIEW => 'مشاهده مالی دفتر',
            self::REVIEWS_REPLY => 'پاسخ به نظر شهروند',
            self::OFFICE_PROFILE_UPDATE => 'تغییر پروفایل و خدمات دفتر',
            self::OFFICE_OPERATORS_MANAGE => 'مدیریت اپراتورهای دفتر',
            self::OFFICE_REGISTER_APPROVE => 'تأیید ثبت‌نام دفتر',
            self::ADVISOR_REGISTER_APPROVE => 'تأیید ثبت‌نام مشاور',
            self::CONSULTATIONS_CONDUCT => 'برگزاری و مشاهده مشاوره',
            self::DELEGATION_CREATE => 'ایجاد نمایندگی',
            self::AUDIT_LOGS_VIEW => 'مشاهده وقایع‌نگاری بازرسی',
        };
    }
}
