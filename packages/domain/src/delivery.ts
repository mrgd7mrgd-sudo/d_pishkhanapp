// ۶ نوع سند تحویلی
export const DELIVERY_DOC_TYPES = [
  'smart_card',
  'identity_booklet',
  'official_certificate',
  'sealed_dossier',
  'business_license',
  'postal_packet',
] as const;

export type DeliveryDocType = (typeof DELIVERY_DOC_TYPES)[number];

export interface DeliveryDocTypeMeta {
  readonly code: DeliveryDocType;
  readonly label: string;
  readonly color: string;
}

export const DELIVERY_DOC_TYPE_META: Record<DeliveryDocType, DeliveryDocTypeMeta> = {
  smart_card: { code: 'smart_card', label: 'کارت هوشمند ملی / سوخت', color: 'blue' },
  identity_booklet: { code: 'identity_booklet', label: 'شناسنامه / گذرنامه', color: 'emerald' },
  official_certificate: { code: 'official_certificate', label: 'گواهی‌نامه رسمی / دانشنامه', color: 'indigo' },
  sealed_dossier: { code: 'sealed_dossier', label: 'پرونده پلمب‌شده محرمانه', color: 'amber' },
  business_license: { code: 'business_license', label: 'پروانه کسب / مجوز صنفی', color: 'teal' },
  postal_packet: { code: 'postal_packet', label: 'بسته پستی استاندارد مدارک', color: 'slate' },
} as const;

// ۵ وضعیت تحویل
export const DELIVERY_STATUSES = [
  'ready_for_dispatch',
  'courier_assigned',
  'in_transit',
  'delivered',
  'failed',
] as const;

export type DeliveryStatus = (typeof DELIVERY_STATUSES)[number];

export interface DeliveryStatusMeta {
  readonly code: DeliveryStatus;
  readonly label: string;
  readonly color: string;
}

export const DELIVERY_STATUS_META: Record<DeliveryStatus, DeliveryStatusMeta> = {
  ready_for_dispatch: { code: 'ready_for_dispatch', label: 'آماده تحویل به پیک', color: 'slate' },
  courier_assigned: { code: 'courier_assigned', label: 'سفیر اختصاص یافت', color: 'blue' },
  in_transit: { code: 'in_transit', label: 'در مسیر ارسال', color: 'amber' },
  delivered: { code: 'delivered', label: 'تحویل داده شد', color: 'emerald' },
  failed: { code: 'failed', label: 'تحویل ناموفق (برگشتی)', color: 'rose' },
} as const;

// ۳ نوع پیک
export const COURIER_TYPES = ['express_courier', 'special_post', 'registered_post'] as const;

export type CourierType = (typeof COURIER_TYPES)[number];

export interface CourierTypeMeta {
  readonly code: CourierType;
  readonly label: string;
  readonly color: string;
}

export const COURIER_TYPE_META: Record<CourierType, CourierTypeMeta> = {
  express_courier: { code: 'express_courier', label: 'پیک اختصاصی شهری (اکسپرس)', color: 'violet' },
  special_post: { code: 'special_post', label: 'پست ویژه (پیشتاز ۲۴ ساعته)', color: 'indigo' },
  registered_post: { code: 'registered_post', label: 'پست سفارشی سراسری', color: 'cyan' },
} as const;

// ۳ روش پرداخت تحویل
export const DELIVERY_PAYMENT_METHODS = ['cod', 'prepaid', 'office_wallet'] as const;

export type DeliveryPaymentMethod = (typeof DELIVERY_PAYMENT_METHODS)[number];

export interface DeliveryPaymentMethodMeta {
  readonly code: DeliveryPaymentMethod;
  readonly label: string;
  readonly color: string;
}

export const DELIVERY_PAYMENT_METHOD_META: Record<DeliveryPaymentMethod, DeliveryPaymentMethodMeta> = {
  cod: { code: 'cod', label: 'پرداخت در محل (POS پیک)', color: 'amber' },
  prepaid: { code: 'prepaid', label: 'پرداخت آنلاین پیش‌کرایه', color: 'emerald' },
  office_wallet: { code: 'office_wallet', label: 'کسر از کیف پول دفتر', color: 'blue' },
} as const;

export const getDeliveryDocTypeMeta = (type: DeliveryDocType): DeliveryDocTypeMeta => DELIVERY_DOC_TYPE_META[type];
export const getDeliveryStatusMeta = (status: DeliveryStatus): DeliveryStatusMeta => DELIVERY_STATUS_META[status];
export const getCourierTypeMeta = (type: CourierType): CourierTypeMeta => COURIER_TYPE_META[type];
export const getDeliveryPaymentMethodMeta = (method: DeliveryPaymentMethod): DeliveryPaymentMethodMeta =>
  DELIVERY_PAYMENT_METHOD_META[method];
