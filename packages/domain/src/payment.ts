/**
 * Payment & Payout Enums & Types (Architecture §6.1, §8.2, TASK-084).
 */

export const PAYMENT_INTENT_STATUSES = [
  'created',
  'redirected',
  'paid',
  'failed',
  'expired',
  'reconciled',
] as const;
export type PaymentIntentStatus = (typeof PAYMENT_INTENT_STATUSES)[number];

export const PAYMENT_GATEWAYS = ['zarinpal', 'zibal', 'fake'] as const;
export type PaymentGateway = (typeof PAYMENT_GATEWAYS)[number];

export const PAYOUT_STATUSES = [
  'pending',
  'processing',
  'completed',
  'failed',
] as const;
export type PayoutStatus = (typeof PAYOUT_STATUSES)[number];

export interface PaymentIntentStatusMeta {
  code: PaymentIntentStatus;
  label: string;
}

export const PAYMENT_INTENT_STATUS_DEFINITIONS: Record<PaymentIntentStatus, PaymentIntentStatusMeta> = {
  created: { code: 'created', label: 'ایجاد شده' },
  redirected: { code: 'redirected', label: 'هدایت به درگاه' },
  paid: { code: 'paid', label: 'پرداخت موفق' },
  failed: { code: 'failed', label: 'پرداخت ناموفق' },
  expired: { code: 'expired', label: 'منقضی شده' },
  reconciled: { code: 'reconciled', label: 'مغایرت‌گیری شده' },
};

export interface PayoutStatusMeta {
  code: PayoutStatus;
  label: string;
}

export const PAYOUT_STATUS_DEFINITIONS: Record<PayoutStatus, PayoutStatusMeta> = {
  pending: { code: 'pending', label: 'در انتظار تسویه' },
  processing: { code: 'processing', label: 'در حال پردازش' },
  completed: { code: 'completed', label: 'تکمیل شده' },
  failed: { code: 'failed', label: 'ناموفق' },
};
