export const TIMELINE_STEP_STATUSES = [
  'done',
  'current',
  'pending',
  'failed',
  'warning',
] as const;

export type TimelineStepStatus = (typeof TIMELINE_STEP_STATUSES)[number];

export interface TimelineStepStatusMeta {
  readonly code: TimelineStepStatus;
  readonly label: string;
  readonly color: string;
}

export const TIMELINE_STEP_STATUS_META: Record<TimelineStepStatus, TimelineStepStatusMeta> = {
  done: {
    code: 'done',
    label: 'انجام‌شده و تایید',
    color: 'emerald',
  },
  current: {
    code: 'current',
    label: 'در دست اقدام جاری',
    color: 'blue',
  },
  pending: {
    code: 'pending',
    label: 'در انتظار نوبت',
    color: 'slate',
  },
  failed: {
    code: 'failed',
    label: 'رد شده یا ناموفق',
    color: 'rose',
  },
  warning: {
    code: 'warning',
    label: 'نقص مدرک یا هشدار',
    color: 'amber',
  },
} as const;

export const getTimelineStepStatusMeta = (status: TimelineStepStatus): TimelineStepStatusMeta => {
  return TIMELINE_STEP_STATUS_META[status];
};
