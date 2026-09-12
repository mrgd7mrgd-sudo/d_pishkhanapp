/**
 * Double-Entry Ledger Enums & Types (Architecture §6.1, §6.2, §8.2, TASK-054)
 */

export const LEDGER_DIRECTIONS = ['debit', 'credit'] as const;
export type LedgerDirection = (typeof LEDGER_DIRECTIONS)[number];

export const LEDGER_ACCOUNT_KINDS = [
  'wallet',
  'payable',
  'revenue',
  'clearing',
  'escrow',
] as const;
export type LedgerAccountKind = (typeof LEDGER_ACCOUNT_KINDS)[number];

export const LEDGER_OWNER_TYPES = [
  'citizen',
  'office',
  'advisor',
  'platform',
  'gateway',
] as const;
export type LedgerOwnerType = (typeof LEDGER_OWNER_TYPES)[number];

export const LEDGER_TRANSACTION_TYPES = [
  'topup',
  'service_fee',
  'refund',
  'payout',
  'cashback',
  'consultation_fee',
  'shipping_fee',
] as const;
export type LedgerTransactionType = (typeof LEDGER_TRANSACTION_TYPES)[number];

export interface LedgerAccountKindMeta {
  code: LedgerAccountKind;
  label: string;
}

export const LEDGER_ACCOUNT_KIND_DEFINITIONS: Record<LedgerAccountKind, LedgerAccountKindMeta> = {
  wallet: { code: 'wallet', label: 'کیف پول نقدی' },
  payable: { code: 'payable', label: 'حساب پرداختنی (بستانکار دفتر/مشاور)' },
  revenue: { code: 'revenue', label: 'درآمد پلتفرم' },
  clearing: { code: 'clearing', label: 'حساب واسط تسویه درگاه' },
  escrow: { code: 'escrow', label: 'حساب امانی پرونده‌ها' },
};
