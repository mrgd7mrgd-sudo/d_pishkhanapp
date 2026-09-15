export interface WalletBalance {
  balance_rials: number;
  balance_toman: number;
  currency: string;
}

export interface TopupIntentPayload {
  amount_rials: number;
  return_url: string;
  gateway?: 'zarinpal' | 'zibal' | undefined;
}

export interface TopupIntentData {
  payment_intent_id: string;
  redirect_url: string;
  authority: string;
  expires_at: string;
  amount_rials: number;
}

export interface VerifyTopupPayload {
  authority: string;
  gateway?: 'zarinpal' | 'zibal' | undefined;
}

export interface VerifyTopupData {
  payment_intent_id: string;
  status: string;
  amount_rials: number;
  ref_id: string | null;
  card_pan_masked: string | null;
  verified_at: string | null;
  wallet_balance_rials: number;
}

export type TransactionType =
  | 'topup'
  | 'service_fee'
  | 'refund'
  | 'payout'
  | 'consultation_fee'
  | 'shipping_fee';

export interface WalletTransaction {
  id: string;
  reference: string;
  type: TransactionType;
  direction: 'debit' | 'credit';
  amount_rials: number;
  description: string | null;
  created_at: string;
}
