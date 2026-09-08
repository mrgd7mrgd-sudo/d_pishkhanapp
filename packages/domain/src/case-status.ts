export const CASE_STATUSES = [
  'draft',
  'searching_office',
  'assigned_to_office',
  'expert_review',
  'action_required',
  'government_inquiry',
  'ready_for_issue',
  'delivering',
  'completed',
  'rejected',
  'cancelled',
] as const;

export type CaseStatus = (typeof CASE_STATUSES)[number];

export interface CaseStatusMeta {
  readonly code: CaseStatus;
  readonly label: string;
  readonly color: string;
  readonly isTerminal: boolean;
}

export const CASE_STATUS_META: Record<CaseStatus, CaseStatusMeta> = {
  draft: {
    code: 'draft',
    label: 'پیش‌نویس اولیه',
    color: 'slate',
    isTerminal: false,
  },
  searching_office: {
    code: 'searching_office',
    label: 'در جستجوی دفتر پیشخوان',
    color: 'amber',
    isTerminal: false,
  },
  assigned_to_office: {
    code: 'assigned_to_office',
    label: 'واگذار شده به دفتر',
    color: 'blue',
    isTerminal: false,
  },
  expert_review: {
    code: 'expert_review',
    label: 'در حال بررسی کارشناس',
    color: 'indigo',
    isTerminal: false,
  },
  action_required: {
    code: 'action_required',
    label: 'نیازمند اقدام شهروند (نقص مدرک)',
    color: 'orange',
    isTerminal: false,
  },
  government_inquiry: {
    code: 'government_inquiry',
    label: 'در انتظار استعلام دولتی',
    color: 'cyan',
    isTerminal: false,
  },
  ready_for_issue: {
    code: 'ready_for_issue',
    label: 'آماده صدور و تحویل',
    color: 'teal',
    isTerminal: false,
  },
  delivering: {
    code: 'delivering',
    label: 'در حال ارسال با پیک/پست',
    color: 'violet',
    isTerminal: false,
  },
  completed: {
    code: 'completed',
    label: 'تکمیل و تحویل شده',
    color: 'emerald',
    isTerminal: true,
  },
  rejected: {
    code: 'rejected',
    label: 'رد شده قطعی',
    color: 'rose',
    isTerminal: true,
  },
  cancelled: {
    code: 'cancelled',
    label: 'لغو شده',
    color: 'zinc',
    isTerminal: true,
  },
} as const;

/**
 * منبع حقیقت واحد گذارهای مجاز وضعیت پرونده (مطابق بخش ۳.۵ سند معماری)
 */
export const ALLOWED_CASE_TRANSITIONS: Record<CaseStatus, readonly CaseStatus[]> = {
  draft: ['searching_office'],
  searching_office: ['assigned_to_office', 'cancelled'],
  assigned_to_office: ['expert_review', 'searching_office'],
  expert_review: ['action_required', 'government_inquiry', 'rejected'],
  action_required: ['expert_review', 'cancelled'],
  government_inquiry: ['ready_for_issue', 'action_required', 'rejected'],
  ready_for_issue: ['completed', 'delivering'],
  delivering: ['completed', 'ready_for_issue'],
  completed: [],
  rejected: [],
  cancelled: [],
} as const;

export class InvalidCaseTransitionError extends Error {
  constructor(from: CaseStatus, to: CaseStatus) {
    super(`گذار غیرمجاز وضعیت پرونده از '${from}' به '${to}'.`);
    this.name = 'InvalidCaseTransitionError';
  }
}

export const canTransitionCase = (from: CaseStatus, to: CaseStatus): boolean => {
  const allowed = ALLOWED_CASE_TRANSITIONS[from];
  return allowed.includes(to);
};

export const assertValidCaseTransition = (from: CaseStatus, to: CaseStatus): void => {
  if (!canTransitionCase(from, to)) {
    throw new InvalidCaseTransitionError(from, to);
  }
};

export const getCaseStatusMeta = (status: CaseStatus): CaseStatusMeta => {
  return CASE_STATUS_META[status];
};
