export const DELEGATION_STATUSES = [
  'pending_otp',
  'active',
  'revoked',
  'expired',
] as const;

export type DelegationStatus = (typeof DELEGATION_STATUSES)[number];

export interface DelegationStatusMeta {
  readonly code: DelegationStatus;
  readonly label: string;
  readonly color: string;
  readonly description: string;
}

export const DELEGATION_STATUS_META: Record<DelegationStatus, DelegationStatusMeta> = {
  pending_otp: {
    code: 'pending_otp',
    label: 'در انتظار تأیید پیامکی موکل',
    color: 'amber',
    description: 'کد تایید OTP به شماره همراه موکل ارسال شده و هنوز ثبت نهایی نشده است',
  },
  active: {
    code: 'active',
    label: 'فعال و معتبر',
    color: 'emerald',
    description: 'نمایندگی قانونی تایید شده و وکیل مجاز به ثبت و پیگیری پرونده است',
  },
  revoked: {
    code: 'revoked',
    label: 'عزل / ابطال شده توسط موکل',
    color: 'rose',
    description: 'اختیارات نمایندگی پیش از موعد توسط موکل لغو شده است',
  },
  expired: {
    code: 'expired',
    label: 'منقضی‌شده',
    color: 'slate',
    description: 'مدت اعتبار زمانی تعیین‌شده برای این نمایندگی به پایان رسیده است',
  },
} as const;

export const getDelegationStatusMeta = (status: DelegationStatus): DelegationStatusMeta => {
  return DELEGATION_STATUS_META[status];
};
