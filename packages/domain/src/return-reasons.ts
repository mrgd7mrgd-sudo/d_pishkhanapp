export const RETURN_REASON_CODES = [
  'DOC_BLUR',
  'DOC_CROP',
  'DOC_EXPIRED',
  'DOC_MISMATCH',
  'DOC_MISSING',
  'DOC_WRONG_TYPE',
  'FORM_INVALID',
  'INQUIRY_MISMATCH',
  'ELIGIBILITY_FAIL',
  'PRESENCE_REQUIRED',
] as const;

export type ReturnReasonCode = (typeof RETURN_REASON_CODES)[number];

export interface ReturnReasonMeta {
  readonly code: ReturnReasonCode;
  readonly label: string;
  readonly defaultMessage: string;
  readonly color: string;
}

export const RETURN_REASON_META: Record<ReturnReasonCode, ReturnReasonMeta> = {
  DOC_BLUR: {
    code: 'DOC_BLUR',
    label: 'تصویر تار / مندرجات ناخوانا',
    defaultMessage:
      'تصویر مدرک بارگذاری‌شده تار است و شماره سریال یا مندرجات خوانا نیست. لطفاً در نور کافی و بدون لرزش مجدداً عکس بگیرید.',
    color: 'amber',
  },
  DOC_CROP: {
    code: 'DOC_CROP',
    label: 'برش ناقص / لبه‌های سند بریده شده',
    defaultMessage:
      'لبه‌ها و کادر چهارگانه سند در تصویر مشخص نیست. کل سند باید کامل و بدون زاویه در کادر قرار گیرد.',
    color: 'amber',
  },
  DOC_EXPIRED: {
    code: 'DOC_EXPIRED',
    label: 'سند منقضی / بدون اعتبار زمانی',
    defaultMessage:
      'تاریخ اعتبار قانونی مدرک منقضی شده است. لطفاً نسخه تمدیدشده یا تاییدیه معتبر را بارگذاری نمایید.',
    color: 'rose',
  },
  DOC_MISMATCH: {
    code: 'DOC_MISMATCH',
    label: 'مغایرت مندرجات مدرک با فرم',
    defaultMessage: 'اطلاعات واردشده در فرم با مندرجات مدرک اسکن‌شده مغایرت دارد.',
    color: 'orange',
  },
  DOC_MISSING: {
    code: 'DOC_MISSING',
    label: 'نقص مدرک تکمیلی الزامی',
    defaultMessage: 'مدرک الزامی این خدمت ارائه نشده است. لطفاً پیوست نمایید.',
    color: 'orange',
  },
  DOC_WRONG_TYPE: {
    code: 'DOC_WRONG_TYPE',
    label: 'فایل یا سند نامرتبط',
    defaultMessage: 'فایل بارگذاری‌شده با عنوان مدرک درخواستی همخوانی ندارد.',
    color: 'rose',
  },
  FORM_INVALID: {
    code: 'FORM_INVALID',
    label: 'خطای ساختاری در فیلدهای فرم',
    defaultMessage: 'مقادیر ورودی با استانداردهای سازمانی مطابقت ندارد.',
    color: 'red',
  },
  INQUIRY_MISMATCH: {
    code: 'INQUIRY_MISMATCH',
    label: 'مغایرت با سامانه بالادست دولتی',
    defaultMessage:
      'اطلاعات با پایگاه داده سامانه مرجع همخوانی ندارد. ابتدا نسبت به اصلاح رکورد ثبتی اقدام نمایید.',
    color: 'red',
  },
  ELIGIBILITY_FAIL: {
    code: 'ELIGIBILITY_FAIL',
    label: 'عدم احراز شرایط قانونی خدمت',
    defaultMessage: 'شرایط قانونی دریافت این خدمت احراز نگردید.',
    color: 'rose',
  },
  PRESENCE_REQUIRED: {
    code: 'PRESENCE_REQUIRED',
    label: 'نیاز به مراجعه حضوری و احراز بیومتریک',
    defaultMessage:
      'این خدمت به دلیل الزامات هویتی نیازمند حضور شخص متقاضی جهت ثبت اثر انگشت و تطبیق چهره در باجه دفتر پیشخوان است.',
    color: 'indigo',
  },
} as const;

export const getReturnReasonMeta = (code: ReturnReasonCode): ReturnReasonMeta => {
  return RETURN_REASON_META[code];
};
