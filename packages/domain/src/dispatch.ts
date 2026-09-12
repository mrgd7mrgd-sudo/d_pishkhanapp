/**
 * Dispatch Offer Status Enum (Architecture §5.8, §6.1, TASK-066).
 * Single source of truth for the 4 dispatch offer statuses.
 */
export const DISPATCH_OFFER_STATUSES = [
  'pending',
  'accepted',
  'declined',
  'expired',
] as const;

export type DispatchOfferStatus = (typeof DISPATCH_OFFER_STATUSES)[number];

export interface DispatchOfferStatusMeta {
  readonly code: DispatchOfferStatus;
  readonly label: string;
  readonly isTerminal: boolean;
}

export const DISPATCH_OFFER_STATUS_META: Record<DispatchOfferStatus, DispatchOfferStatusMeta> = {
  pending: {
    code: 'pending',
    label: 'در انتظار پاسخ دفتر',
    isTerminal: false,
  },
  accepted: {
    code: 'accepted',
    label: 'پذیرفته شده توسط دفتر',
    isTerminal: true,
  },
  declined: {
    code: 'declined',
    label: 'رد شده توسط دفتر',
    isTerminal: true,
  },
  expired: {
    code: 'expired',
    label: 'منقضی شده',
    isTerminal: true,
  },
};
