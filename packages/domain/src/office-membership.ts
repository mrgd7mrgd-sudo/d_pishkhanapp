export const OFFICE_MEMBERSHIP_STATUSES = [
  'registered_online',
  'registered_offline',
  'unregistered',
] as const;

export type OfficeMembershipStatus = (typeof OFFICE_MEMBERSHIP_STATUSES)[number];

export interface OfficeMembershipStatusMeta {
  readonly code: OfficeMembershipStatus;
  readonly label: string;
  readonly color: string;
  readonly description: string;
}

export const OFFICE_MEMBERSHIP_STATUS_META: Record<OfficeMembershipStatus, OfficeMembershipStatusMeta> = {
  registered_online: {
    code: 'registered_online',
    label: 'دفتر عضو — آنلاین و فعال در سامانه',
    color: 'emerald',
    description: 'دفتر دارای اتصال آنلاین، پشتیبانی از اساین خودکار و ثبت پرونده لحظه‌ای',
  },
  registered_offline: {
    code: 'registered_offline',
    label: 'دفتر عضو — نوبت‌دهی حضوری',
    color: 'amber',
    description: 'دفتر عضو شبکه نوبت‌دهی حضوری بدون کارتابل ارجاع آنلاین پرونده',
  },
  unregistered: {
    code: 'unregistered',
    label: 'دفتر غیرعضو (اطلاعات مرجع)',
    color: 'slate',
    description: 'صرفاً اطلاعات جغرافیایی و آدرس در نقشه به عنوان مرجع عمومی ثبت است',
  },
} as const;

export const getOfficeMembershipStatusMeta = (
  status: OfficeMembershipStatus
): OfficeMembershipStatusMeta => {
  return OFFICE_MEMBERSHIP_STATUS_META[status];
};
