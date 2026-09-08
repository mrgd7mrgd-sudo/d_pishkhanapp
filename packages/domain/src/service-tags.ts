export const SERVICE_TAGS = ['online', 'semi-online', 'in-person'] as const;

export type ServiceTag = (typeof SERVICE_TAGS)[number];

export interface ServiceTagMeta {
  readonly code: ServiceTag;
  readonly label: string;
  readonly color: string;
  readonly description: string;
}

export const SERVICE_TAG_META: Record<ServiceTag, ServiceTagMeta> = {
  online: {
    code: 'online',
    label: 'تماماً آنلاین (غیرحضوری)',
    color: 'emerald',
    description: 'صفر تا صد فرایند ثبت، بررسی و صدور بدون نیاز به خروج از منزل انجام می‌شود',
  },
  'semi-online': {
    code: 'semi-online',
    label: 'نیمه‌حضوری (پیش‌ثبت‌نام آنلاین)',
    color: 'amber',
    description: 'مدارک و فرم‌ها آنلاین بررسی شده و صرفاً برای دریافت یا امضا مراجعه انجام می‌شود',
  },
  'in-person': {
    code: 'in-person',
    label: 'حضوری (نیازمند باجه)',
    color: 'blue',
    description: 'به دلیل الزامات هویتی یا بیومتریک، مراجعه به دفتر پیشخوان الزامی است',
  },
} as const;

export const getServiceTagMeta = (tag: ServiceTag): ServiceTagMeta => {
  return SERVICE_TAG_META[tag];
};
