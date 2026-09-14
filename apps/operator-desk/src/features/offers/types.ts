/**
 * Dispatch Offer Types (§5.5, §5.8, TASK-077).
 */

export interface DispatchOfferItem {
  id: string;
  case_id: string;
  tracking_code: string;
  service_id: string;
  service_title?: string;
  province_code?: string;
  city?: string;
  round: number;
  status: 'pending' | 'accepted' | 'declined' | 'expired';
  created_at: string;
  expires_at: string;
  remaining_seconds: number;
  fee_rials?: number;
}

export interface AcceptOfferResponse {
  data: {
    case_id: string;
    tracking_code: string;
    status: string;
    office_id: string;
    operator_id: string;
  };
  message: string;
}

export interface DeclineOfferResponse {
  data: {
    offer_id: string;
    status: string;
  };
  message: string;
}

export class OfferConflictError extends Error {
  constructor(message = 'این پرونده توسط دفتر دیگری پذیرفته شده است.') {
    super(message);
    this.name = 'OfferConflictError';
  }
}
