import React from 'react';
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { axe } from 'vitest-axe';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { WalletDashboardView } from '../ui/WalletDashboardView';
import { WalletTransactionsView } from '../ui/WalletTransactionsView';
import { TopupModal } from '../components/TopupModal';
import { WalletBalanceCard } from '../components/WalletBalanceCard';

const createTestQueryClient = () =>
  new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
        gcTime: 0,
      },
    },
  });

const renderWithProviders = (ui: React.ReactElement, initialEntries = ['/wallet']) => {
  const queryClient = createTestQueryClient();
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter initialEntries={initialEntries}>{ui}</MemoryRouter>
    </QueryClientProvider>
  );
};

describe('Citizen Wallet Slice (§4.1, §4.4, §4.6, TASK-092, TASK-092-T)', () => {
  const mockFetch = vi.fn();

  beforeEach(() => {
    vi.stubGlobal('fetch', mockFetch);
    vi.restoreAllMocks();
    Object.defineProperty(navigator, 'onLine', { value: true, configurable: true });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('renders wallet dashboard with balance converted from Rials to Tomans', async () => {
    mockFetch.mockImplementation(async (url: string) => {
      if (url.includes('/wallet/balance')) {
        return {
          ok: true,
          json: async () => ({
            data: {
              balance_rials: 5_000_000,
              balance_toman: 500_000,
              currency: 'IRR',
            },
          }),
        };
      }
      if (url.includes('/wallet/transactions')) {
        return {
          ok: true,
          json: async () => ({
            data: [
              {
                id: 'tx_1',
                reference: 'REF-TOPUP-01',
                type: 'topup',
                direction: 'credit',
                amount_rials: 5_000_000,
                description: 'شارژ آنلاین',
                created_at: new Date().toISOString(),
              },
            ],
          }),
        };
      }
      return { ok: false, status: 404 };
    });

    renderWithProviders(<WalletDashboardView />);

    expect(screen.getByText('کیف پول شهروندی')).toBeInTheDocument();

    await waitFor(() => {
      // 5,000,000 Rials = 500,000 Tomans
      expect(screen.getAllByText('۵۰۰٬۰۰۰').length).toBeGreaterThan(0);
      expect(screen.getByText('شارژ کیف پول')).toBeInTheDocument();
    });
  });

  it('offline test: disables topup button and shows notice when offline (Architecture §4.6)', async () => {
    Object.defineProperty(navigator, 'onLine', { value: false, configurable: true });

    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => ({
        data: { balance_rials: 1_000_000, balance_toman: 100_000, currency: 'IRR' },
      }),
    });

    renderWithProviders(<WalletBalanceCard />);

    expect(screen.getByTestId('offline-wallet-warning')).toBeInTheDocument();
    expect(
      screen.getByText(/شارژ کیف پول نیازمند اتصال اینترنت است/i)
    ).toBeInTheDocument();

    const topupBtn = screen.getByTestId('open-topup-btn');
    expect(topupBtn).toBeDisabled();
  });

  it('topup modal displays Tomans to citizen but strictly sends Rials to API', async () => {
    let capturedPayload: unknown = null;

    mockFetch.mockImplementation(async (url: string, init?: RequestInit) => {
      if (url.includes('/wallet/topup')) {
        capturedPayload = JSON.parse((init?.body as string) || '{}');
        return {
          ok: true,
          json: async () => ({
            data: {
              payment_intent_id: 'pi_test_123',
              redirect_url: 'https://gateway.example.com/pay/A0001',
              authority: 'A0001',
              expires_at: new Date(Date.now() + 600_000).toISOString(),
              amount_rials: 1_000_000,
            },
          }),
        };
      }
      return { ok: false, status: 404 };
    });

    const onClose = vi.fn();
    renderWithProviders(<TopupModal isOpen={true} onClose={onClose} />);

    expect(screen.getByText('افزایش موجودی کیف پول')).toBeInTheDocument();

    // Default preset is 100,000 Tomans (1,000,000 Rials)
    expect(screen.getByTestId('final-payment-amount')).toHaveTextContent(/۱٬۰۰۰٬۰۰۰\s*ریال/);

    const submitBtn = screen.getByTestId('submit-topup-btn');
    fireEvent.click(submitBtn);

    await waitFor(() => {
      expect(capturedPayload).not.toBeNull();
      // Verifies amount is strictly sent in Rials
      expect((capturedPayload as { amount_rials: number }).amount_rials).toBe(1_000_000);
      expect((capturedPayload as { gateway: string }).gateway).toBe('zarinpal');
    });
  });

  it('gateway callback: automatically verifies transaction when returning with Status=OK', async () => {
    let verifyCalled = false;

    mockFetch.mockImplementation(async (url: string, init?: RequestInit) => {
      if (url.includes('/wallet/balance')) {
        return {
          ok: true,
          json: async () => ({
            data: { balance_rials: 3_000_000, balance_toman: 300_000, currency: 'IRR' },
          }),
        };
      }
      if (url.includes('/wallet/topup/verify')) {
        verifyCalled = true;
        const body = JSON.parse((init?.body as string) || '{}') as { authority: string };
        expect(body.authority).toBe('AUTH_CALLBACK_123');

        return {
          ok: true,
          json: async () => ({
            data: {
              payment_intent_id: 'pi_123',
              status: 'paid',
              amount_rials: 2_000_000,
              ref_id: 'REF_998877',
              card_pan_masked: '603799******1234',
              verified_at: new Date().toISOString(),
              wallet_balance_rials: 3_000_000,
            },
          }),
        };
      }
      return { ok: false, status: 404 };
    });

    renderWithProviders(<WalletDashboardView />, ['/wallet?authority=AUTH_CALLBACK_123&Status=OK']);

    await waitFor(() => {
      expect(verifyCalled).toBe(true);
      expect(screen.getByTestId('callback-success-banner')).toBeInTheDocument();
      expect(screen.getByText(/شارژ کیف پول با موفقیت انجام شد/i)).toBeInTheDocument();
      expect(screen.getByText(/REF_998877/i)).toBeInTheDocument();
    });
  });

  it('gateway callback: shows error banner when returning with Status=NOK', async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => ({ data: { balance_rials: 0, balance_toman: 0, currency: 'IRR' } }),
    });

    renderWithProviders(<WalletDashboardView />, ['/wallet?authority=AUTH_NOK&Status=NOK']);

    await waitFor(() => {
      expect(screen.getByTestId('callback-error-banner')).toBeInTheDocument();
      expect(screen.getByText(/پرداخت در درگاه لغو شد یا ناموفق بود/i)).toBeInTheDocument();
    });
  });

  it('transactions view: renders list and filters by tab', async () => {
    mockFetch.mockImplementation(async (url: string) => {
      if (url.includes('/wallet/transactions')) {
        return {
          ok: true,
          json: async () => ({
            data: [
              {
                id: 'tx_topup',
                reference: 'REF-TOPUP',
                type: 'topup',
                direction: 'credit',
                amount_rials: 1_000_000,
                description: 'واریز آنلاین',
                created_at: new Date().toISOString(),
              },
              {
                id: 'tx_fee',
                reference: 'REF-FEE',
                type: 'service_fee',
                direction: 'debit',
                amount_rials: 500_000,
                description: 'هزینه صدور کارت',
                created_at: new Date().toISOString(),
              },
            ],
          }),
        };
      }
      return { ok: false, status: 404 };
    });

    renderWithProviders(<WalletTransactionsView />, ['/wallet/transactions']);

    await waitFor(() => {
      expect(screen.getByText('واریز آنلاین')).toBeInTheDocument();
      expect(screen.getByText('هزینه صدور کارت')).toBeInTheDocument();
    });

    // Filter to topup only
    fireEvent.click(screen.getByText('شارژها (واریز)'));

    await waitFor(() => {
      expect(screen.getByText('واریز آنلاین')).toBeInTheDocument();
      expect(screen.queryByText('هزینه صدور کارت')).not.toBeInTheDocument();
    });
  });

  it('passes axe accessibility test with zero violations', async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => ({
        data: { balance_rials: 2_000_000, balance_toman: 200_000, currency: 'IRR' },
      }),
    });

    const { container } = renderWithProviders(<WalletBalanceCard />);

    await waitFor(() => {
      expect(screen.getByTestId('wallet-balance-card')).toBeInTheDocument();
    });

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
