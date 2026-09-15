export type PayoutStatus = 'completed' | 'pending' | 'processing' | 'failed';

export interface PayoutItem {
  id: string;
  amount_rials: number;
  status: PayoutStatus;
  period_start: string;
  period_end: string;
  total_cases_count: number;
  reference_number?: string | null | undefined;
  generated_at: string;
  processed_at?: string | null | undefined;
}

export interface RevenueChartPoint {
  date: string;
  office_share_rials: number;
  cases_count: number;
}

export interface FinanceSummary {
  office: {
    id: string;
    code: string;
    name: string;
  };
  payable_balance_rials: number;
  period_summary: {
    period_start: string;
    period_end: string;
    total_cases_count: number;
    total_fee_rials: number;
    office_share_rials: number;
    platform_share_rials: number;
    period_credits_rials: number;
    period_debits_rials: number;
    period_net_payable_rials: number;
  };
  ledger_integrity: {
    is_office_ledger_consistent: boolean;
    is_global_ledger_balanced: boolean;
    discrepancy_rials: number;
  };
  recent_payouts: PayoutItem[];
  revenue_chart: RevenueChartPoint[];
}

export interface FinanceQueryParams {
  period_start?: string | undefined;
  period_end?: string | undefined;
}
