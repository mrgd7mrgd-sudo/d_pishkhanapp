export const TURN_OWNERS = ['citizen', 'office', 'government', 'postal', 'system'] as const;

export type TurnOwner = (typeof TURN_OWNERS)[number];

export interface TurnOwnerMeta {
  readonly code: TurnOwner;
  readonly label: string;
  readonly color: string;
  readonly description: string;
}

export const TURN_OWNER_META: Record<TurnOwner, TurnOwnerMeta> = {
  citizen: {
    code: 'citizen',
    label: 'شهروند (متقاضی)',
    color: 'orange',
    description: 'نوبت اقدام با متقاضی خدمت جهت رفع نقص یا بارگذاری مدرک',
  },
  office: {
    code: 'office',
    label: 'دفتر پیشخوان',
    color: 'blue',
    description: 'نوبت اقدام با کارشناس دفتر پیشخوان جهت بررسی و اقدام',
  },
  government: {
    code: 'government',
    label: 'دستگاه دولتی / سامانه مرجع',
    color: 'cyan',
    description: 'در انتظار پاسخ استعلام یا تاییدیه از وزارت‌خانه یا دستگاه اجرایی',
  },
  postal: {
    code: 'postal',
    label: 'پست / پیک تحویل',
    color: 'violet',
    description: 'در دست اقدام ناوگان توزیع و سفیر تحویل',
  },
  system: {
    code: 'system',
    label: 'سامانه هوشمند',
    color: 'slate',
    description: 'پردازش خودکار سروری، اساین هوشمند یا تسویه مالی',
  },
} as const;

export const getTurnOwnerMeta = (owner: TurnOwner): TurnOwnerMeta => {
  return TURN_OWNER_META[owner];
};
