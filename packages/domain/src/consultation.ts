// ۶ دسته مشاوره
export const CONSULTATION_CATEGORIES = [
  'tax',
  'insurance_labor',
  'legal_registry',
  'tenders_permits',
  'municipal',
  'business_startup',
] as const;

export type ConsultationCategory = (typeof CONSULTATION_CATEGORIES)[number];

export interface ConsultationCategoryMeta {
  readonly code: ConsultationCategory;
  readonly label: string;
  readonly color: string;
  readonly description: string;
}

export const CONSULTATION_CATEGORY_META: Record<ConsultationCategory, ConsultationCategoryMeta> = {
  tax: {
    code: 'tax',
    label: 'مشاوره مالیاتی و دارایی',
    color: 'emerald',
    description: 'اظهارنامه عملکرد، مالیات بر ارزش افزوده، تراکنش‌های بانکی و پایانه‌های فروشگاهی',
  },
  insurance_labor: {
    code: 'insurance_labor',
    label: 'بیمه تامین اجتماعی و قانون کار',
    color: 'blue',
    description: 'دعاوی کارگری و کارفرمایی، مفاصاحساب ماده ۳۸، بازنشستگی و بیمه بیکاری',
  },
  legal_registry: {
    code: 'legal_registry',
    label: 'حقوقی، ثبت شرکت‌ها و برند',
    color: 'indigo',
    description: 'ثبت و تغییرات شرکت، علامت تجاری، کارت بازرگانی و تنظیم قراردادها',
  },
  tenders_permits: {
    code: 'tenders_permits',
    label: 'مناقصات، مزایدات و رتبه‌بندی',
    color: 'amber',
    description: 'سامانه ستاد ایران، اخذ گواهینامه صلاحیت پیمانکاری و مشاورین ساجات',
  },
  municipal: {
    code: 'municipal',
    label: 'شهرداری، کمیسیون ماده ۱۰۰ و املاک',
    color: 'rose',
    description: 'تخلفات ساختمانی ماده ۱۰۰، تغییر کاربری، پایان‌کار و عوارض نوسازی',
  },
  business_startup: {
    code: 'business_startup',
    label: 'مجوزهای کسب‌وکار و درگاه ملی مجوزها',
    color: 'cyan',
    description: 'اخذ پروانه کسب از درگاه ملی مجوزها، سامانه بهین‌یاب و تسهیلات اشتغال‌زایی',
  },
} as const;

// ۳ حالت مشاوره
export const CONSULTATION_MODES = ['text', 'call', 'case_review'] as const;

export type ConsultationMode = (typeof CONSULTATION_MODES)[number];

export interface ConsultationModeMeta {
  readonly code: ConsultationMode;
  readonly label: string;
  readonly color: string;
}

export const CONSULTATION_MODE_META: Record<ConsultationMode, ConsultationModeMeta> = {
  text: { code: 'text', label: 'چت متنی و پرسش و پاسخ', color: 'blue' },
  call: { code: 'call', label: 'تماس تلفنی یا صوتی مستقیم', color: 'emerald' },
  case_review: { code: 'case_review', label: 'بررسی تخصصی اوراق و لوایح پرونده', color: 'purple' },
} as const;

export const getConsultationCategoryMeta = (cat: ConsultationCategory): ConsultationCategoryMeta =>
  CONSULTATION_CATEGORY_META[cat];
export const getConsultationModeMeta = (mode: ConsultationMode): ConsultationModeMeta =>
  CONSULTATION_MODE_META[mode];
