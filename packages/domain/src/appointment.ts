// وضعیت نوبت حضوری
export const APPOINTMENT_STATUSES = [
  'active',
  'completed',
  'cancelled',
] as const;

export type AppointmentStatus = (typeof APPOINTMENT_STATUSES)[number];

export interface AppointmentStatusMeta {
  readonly code: AppointmentStatus;
  readonly label: string;
  readonly color: string;
}

export const APPOINTMENT_STATUS_META: Record<AppointmentStatus, AppointmentStatusMeta> = {
  active: { code: 'active', label: 'فعال / در انتظار مراجعه', color: 'blue' },
  completed: { code: 'completed', label: 'انجام‌شده', color: 'emerald' },
  cancelled: { code: 'cancelled', label: 'لغو‌شده', color: 'rose' },
} as const;

// وضعیت حضور و غیاب
export const APPOINTMENT_ATTENDANCES = [
  'pending',
  'attended',
  'absent',
] as const;

export type AppointmentAttendance = (typeof APPOINTMENT_ATTENDANCES)[number];

export interface AppointmentAttendanceMeta {
  readonly code: AppointmentAttendance;
  readonly label: string;
  readonly color: string;
}

export const APPOINTMENT_ATTENDANCE_META: Record<AppointmentAttendance, AppointmentAttendanceMeta> = {
  pending: { code: 'pending', label: 'در انتظار حضور', color: 'amber' },
  attended: { code: 'attended', label: 'حاضر در دفتر', color: 'emerald' },
  absent: { code: 'absent', label: 'عدم مراجعه (غایب)', color: 'rose' },
} as const;

// وضعیت نتیجه نوبت
export const APPOINTMENT_COMPLETIONS = [
  'pending',
  'in_progress',
  'completed',
  'not_completed',
] as const;

export type AppointmentCompletion = (typeof APPOINTMENT_COMPLETIONS)[number];

export interface AppointmentCompletionMeta {
  readonly code: AppointmentCompletion;
  readonly label: string;
  readonly color: string;
}

export const APPOINTMENT_COMPLETION_META: Record<AppointmentCompletion, AppointmentCompletionMeta> = {
  pending: { code: 'pending', label: 'در انتظار بررسی', color: 'slate' },
  in_progress: { code: 'in_progress', label: 'در حال انجام خدمت', color: 'blue' },
  completed: { code: 'completed', label: 'خدمت با موفقیت انجام شد', color: 'emerald' },
  not_completed: { code: 'not_completed', label: 'ناتمام / نیازمند پیگیری', color: 'rose' },
} as const;

// نوع یادآور نوبت
export const APPOINTMENT_REMINDER_TYPES = [
  'sms',
  'push',
  'all',
] as const;

export type AppointmentReminderType = (typeof APPOINTMENT_REMINDER_TYPES)[number];

export interface AppointmentReminderTypeMeta {
  readonly code: AppointmentReminderType;
  readonly label: string;
  readonly color: string;
}

export const APPOINTMENT_REMINDER_TYPE_META: Record<AppointmentReminderType, AppointmentReminderTypeMeta> = {
  sms: { code: 'sms', label: 'فقط پیامک', color: 'slate' },
  push: { code: 'push', label: 'فقط اعلان سیستمی (Push)', color: 'blue' },
  all: { code: 'all', label: 'پیامک و اعلان سیستمی', color: 'emerald' },
} as const;