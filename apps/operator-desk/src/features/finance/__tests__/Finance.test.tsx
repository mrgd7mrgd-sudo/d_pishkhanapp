import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import '@/shared/i18n';
import { FinanceView } from '../ui/FinanceView';
import { FinanceSummaryCards } from '../components/FinanceSummaryCards';
import { RevenueChart } from '../components/RevenueChart';
import { PayoutsTable } from '../components/PayoutsTable';
import { useOperatorAuthStore } from '@/features/auth/model/useOperatorAuthStore';
import type { FinanceSummary } from '../types';

describe('Operator Desk Finance Slice (§4.4, §7.3, TASK-093, TASK-093-T)', () => {
  const mockFetch = vi.fn();

  const dummySummary: FinanceSummary = {
    office: {
      id: 'off_thr_1001',
      code: '1001',
      name: 'دفتر پیشخوان بهارستان',
    },
    payable_balance_rials: 25000000,
    period_summary: {
      period_start: '2026-09-01T00:00:00Z',
      period_end: '2026-09-15T23:59:59Z',
      total_cases_count: 42,
      total_fee_rials: 70000000,
      office_share_rials: 35000000,
      platform_share_rials: 35000000,
      period_credits_rials: 35000000,
      period_debits_rials: 10000000,
      period_net_payable_rials: 25000000,
    },
    ledger_integrity: {
      is_office_ledger_consistent: true,
      is_global_ledger_balanced: true,
      discrepancy_rials: 0,
    },
    recent_payouts: [
      {
        id: 'payout_01',
        amount_rials: 15000000,
        status: 'completed',
        period_start: '2026-08-15T00:00:00Z',
        period_end: '2026-08-31T23:59:59Z',
        total_cases_count: 30,
        reference_number: 'SATNA-1405-9988',
        generated_at: '2026-09-01T02:00:00Z',
        processed_at: '2026-09-01T08:30:00Z',
      },
      {
        id: 'payout_02',
        amount_rials: 8000000,
        status: 'pending',
        period_start: '2026-09-01T00:00:00Z',
        period_end: '2026-09-07T23:59:59Z',
        total_cases_count: 12,
        reference_number: null,
        generated_at: '2026-09-08T02:00:00Z',
        processed_at: null,
      },
    ],
    revenue_chart: [
      { date: '1405-06-10', office_share_rials: 3400000, cases_count: 4 },
      { date: '1405-06-11', office_share_rials: 5100000, cases_count: 6 },
      { date: '1405-06-12', office_share_rials: 6800000, cases_count: 8 },
    ],
  };

  let testQueryClient: QueryClient;

  beforeEach(() => {
    vi.stubGlobal('fetch', mockFetch);
    testQueryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false, gcTime: 0 },
      },
    });
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    testQueryClient.clear();
  });

  const renderWithProviders = (ui: React.ReactElement) => {
    return render(
      <QueryClientProvider client={testQueryClient}>
        <MemoryRouter>{ui}</MemoryRouter>
      </QueryClientProvider>
    );
  };

  it('strictly blocks office_operator and displays 403 Access Denied banner', () => {
    useOperatorAuthStore.setState({
      operator: {
        id: 'op_1',
        office_id: 'off_thr_1001',
        username: 'operator_ali',
        full_name: 'علی باجه‌دار',
        role: 'operator',
        role_name: 'اپراتور پیشخوان',
        counter_number: 1,
        is_active: true,
        last_login_at: null,
      },
      isAuthenticated: true,
    });

    renderWithProviders(<FinanceView />);

    expect(screen.getByTestId('finance-access-denied')).toBeInTheDocument();
    expect(screen.queryByTestId('finance-dashboard-view')).not.toBeInTheDocument();
    expect(mockFetch).not.toHaveBeenCalled();
  });

  it('allows office_manager to access and renders full financial summary and cards', async () => {
    useOperatorAuthStore.setState({
      operator: {
        id: 'mgr_1',
        office_id: 'off_thr_1001',
        username: 'manager_hosseini',
        full_name: 'حسینی مدیر دفتر',
        role: 'manager',
        role_name: 'مدیر دفتر',
        counter_number: 2,
        is_active: true,
        last_login_at: null,
      },
      isAuthenticated: true,
    });

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: dummySummary }),
    });

    renderWithProviders(<FinanceView />);

    await waitFor(() => {
      expect(screen.getByTestId('payable-balance-value')).toBeInTheDocument();
    });

    // Verify numbers matching API
    expect(screen.getByTestId('payable-balance-value')).toHaveTextContent('۲٬۵۰۰٬۰۰۰');
    expect(screen.getByTestId('period-revenue-value')).toHaveTextContent('۳٬۵۰۰٬۰۰۰');
    expect(screen.getByTestId('settled-cases-count')).toHaveTextContent('۴۲');
    expect(screen.getByTestId('ledger-balanced-badge')).toBeInTheDocument();
  });

  it('renders payouts table with correct rows and badges', () => {
    renderWithProviders(<PayoutsTable payouts={dummySummary.recent_payouts} />);

    expect(screen.getByTestId('payouts-table-card')).toBeInTheDocument();
    expect(screen.getByTestId('payout-row-payout_01')).toBeInTheDocument();
    expect(screen.getByTestId('payout-row-payout_02')).toBeInTheDocument();
    expect(screen.getByText('SATNA-1405-9988')).toBeInTheDocument();
  });

  it('renders revenue dataviz chart with accessible bars and labels', () => {
    renderWithProviders(<RevenueChart points={dummySummary.revenue_chart} />);

    expect(screen.getByTestId('revenue-chart-card')).toBeInTheDocument();
    const bars = screen.getAllByRole('img');
    expect(bars).toHaveLength(3);
    expect(bars[0]).toHaveAttribute('aria-label', expect.stringContaining('1405-06-10'));
  });

  it('displays ledger imbalance warning when discrepancy > 0', () => {
    const imbalancedSummary: FinanceSummary = {
      ...dummySummary,
      ledger_integrity: {
        is_office_ledger_consistent: false,
        is_global_ledger_balanced: false,
        discrepancy_rials: 500000,
      },
    };

    renderWithProviders(<FinanceSummaryCards summary={imbalancedSummary} />);

    expect(screen.getByTestId('ledger-imbalance-badge')).toBeInTheDocument();
    expect(screen.queryByTestId('ledger-balanced-badge')).not.toBeInTheDocument();
  });

  it('passes axe accessibility audit with 0 violations', async () => {
    const { container } = renderWithProviders(
      <div>
        <FinanceSummaryCards summary={dummySummary} />
        <RevenueChart points={dummySummary.revenue_chart} />
        <PayoutsTable payouts={dummySummary.recent_payouts} />
      </div>
    );

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
