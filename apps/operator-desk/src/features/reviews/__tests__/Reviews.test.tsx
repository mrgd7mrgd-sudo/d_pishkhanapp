import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import '@/shared/i18n';
import { useOperatorAuthStore } from '@/features/auth/model/useOperatorAuthStore';
import { ReviewsPage } from '../ui/ReviewsPage';
import type { DeskReview, SlaStatsData } from '../types';

describe('Operator Desk Reviews Slice (§4.4, §7.3, §10.2, TASK-104, TASK-104-T)', () => {
  const mockFetch = vi.fn();
  let testQueryClient: QueryClient;

  const mockReviews: DeskReview[] = [
    {
      id: 'rev_01',
      office_id: 'off_01',
      office_name: 'دفتر صادقیه',
      citizen_id: 'cit_01',
      citizen_name: 'علی احمدی',
      case_id: 'case_01',
      service_title: 'صدور کارت ملی هوشمند',
      rating: 5,
      comment: 'بسیار سریع و با احترام انجام شد.',
      tags: ['سرعت_بالا', 'برخورد_مناسب'],
      likes: 3,
      is_verified: true,
      is_verified_citizen: true,
      manager_reply: 'با تشکر از اعتماد شما به دفتر صادقیه.',
      manager_replied_at: '2026-09-16T08:30:00Z',
      manager_reply_info: {
        text: 'با تشکر از اعتماد شما به دفتر صادقیه.',
        date: '2026-09-16T08:30:00Z',
      },
      created_at: '2026-09-16T08:00:00Z',
    },
    {
      id: 'rev_02',
      office_id: 'off_01',
      office_name: 'دفتر صادقیه',
      citizen_id: 'cit_02',
      citizen_name: 'مریم رضایی',
      case_id: 'case_02',
      service_title: 'تعویض شناسنامه',
      rating: 2,
      comment: 'زمان معطلی در باجه بیش از حد انتظار بود.',
      tags: ['زمان_معطلی'],
      likes: 1,
      is_verified: true,
      is_verified_citizen: true,
      manager_reply: null,
      manager_replied_at: null,
      created_at: '2026-09-16T08:15:00Z',
    },
  ];

  const mockSlaStats: SlaStatsData = {
    current_score: 95.0,
    window_days: 30,
    trend: [
      { date: '2026-09-14', breaches_count: 1, penalties: 5 },
      { date: '2026-09-15', breaches_count: 0, penalties: 0 },
      { date: '2026-09-16', breaches_count: 0, penalties: 0 },
    ],
    breakdown: [
      { event_type: 'late_return', count: 1, total_penalty: 5 },
    ],
  };

  beforeEach(() => {
    vi.clearAllMocks();
    globalThis.fetch = mockFetch;

    testQueryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false, gcTime: 0 },
        mutations: { retry: false },
      },
    });

    // Default to office_manager
    useOperatorAuthStore.setState({
      isAuthenticated: true,
      operator: {
        id: 'op_mgr_01',
        office_id: 'off_01',
        username: 'manager_ali',
        full_name: 'علی مدیر',
        role: 'manager',
        role_name: 'مدیر دفتر',
        counter_number: 1,
        is_active: true,
        last_login_at: '2026-09-16T08:00:00Z',
      },
    });

    mockFetch.mockImplementation(async (url: string, init?: RequestInit) => {
      const urlStr = url.toString();

      if (urlStr.includes('/api/v1/desk/reviews/sla-stats')) {
        return {
          ok: true,
          status: 200,
          json: async () => ({ data: mockSlaStats }),
        };
      }

      if (urlStr.includes('/api/v1/desk/reviews') && init?.method === 'POST') {
        const body = JSON.parse((init?.body as string) || '{}');
        return {
          ok: true,
          status: 200,
          json: async () => ({
            data: {
              ...mockReviews[1],
              manager_reply: body.reply,
              manager_replied_at: '2026-09-16T09:00:00Z',
            },
          }),
        };
      }

      if (urlStr.includes('/api/v1/desk/reviews')) {
        let list = [...mockReviews];
        if (urlStr.includes('filter=5star')) {
          list = list.filter((r) => r.rating === 5);
        } else if (urlStr.includes('filter=needReply')) {
          list = list.filter((r) => !r.manager_reply);
        } else if (urlStr.includes('filter=withReply')) {
          list = list.filter((r) => Boolean(r.manager_reply));
        }
        return {
          ok: true,
          status: 200,
          json: async () => ({ data: list }),
        };
      }

      return { ok: false, status: 404, json: async () => ({}) };
    });
  });

  afterEach(() => {
    testQueryClient.clear();
  });

  const renderWithProviders = (ui: React.ReactElement) =>
    render(
      <QueryClientProvider client={testQueryClient}>
        <MemoryRouter>{ui}</MemoryRouter>
      </QueryClientProvider>
    );

  it('renders all 2 subtabs (feedback and sla_quality) and switches between them', async () => {
    renderWithProviders(<ReviewsPage />);

    expect(screen.getByTestId('subtab-feedback')).toBeInTheDocument();
    expect(screen.getByTestId('subtab-sla-quality')).toBeInTheDocument();

    // Default tab is feedback
    expect(await screen.findByText('بسیار سریع و با احترام انجام شد.')).toBeInTheDocument();

    // Switch to sla_quality tab
    fireEvent.click(screen.getByTestId('subtab-sla-quality'));

    expect(await screen.findByText('امتیاز زنده کیفیت SLA')).toBeInTheDocument();
    expect(screen.getByText('95.00')).toBeInTheDocument();
    expect(screen.getByText('روند نقض‌های SLA در ۳۰ روز گذشته')).toBeInTheDocument();
    expect(screen.getByText('تأخیر در عودت مدرک فیزیکی')).toBeInTheDocument();
  });

  it('filters reviews correctly (all, 5star, needReply, withReply)', async () => {
    renderWithProviders(<ReviewsPage />);

    // Default 'all'
    expect(await screen.findByText('بسیار سریع و با احترام انجام شد.')).toBeInTheDocument();
    expect(screen.getByText('زمان معطلی در باجه بیش از حد انتظار بود.')).toBeInTheDocument();

    // Filter 5star
    fireEvent.click(screen.getByTestId('filter-5star'));
    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('filter=5star'),
        expect.anything()
      );
    });

    // Filter needReply
    fireEvent.click(screen.getByTestId('filter-needReply'));
    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('filter=needReply'),
        expect.anything()
      );
    });

    // Filter withReply
    fireEvent.click(screen.getByTestId('filter-withReply'));
    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('filter=withReply'),
        expect.anything()
      );
    });
  });

  it('shows reply button for office_manager and allows submitting a reply', async () => {
    renderWithProviders(<ReviewsPage />);

    expect(await screen.findByText('زمان معطلی در باجه بیش از حد انتظار بود.')).toBeInTheDocument();

    // The reply button is visible for manager
    const replyBtn = screen.getByTestId('reply-btn-rev_02');
    expect(replyBtn).toBeInTheDocument();

    fireEvent.click(replyBtn);

    // Modal opens
    expect(await screen.findByText('پاسخ مدیر دفتر به نظر شهروند')).toBeInTheDocument();

    const textarea = screen.getByLabelText(/متن پاسخ رسمی مدیر دفتر/);
    fireEvent.change(textarea, {
      target: { value: 'با سلام، تمهیدات لازم برای اختصاص باجه پشتیبان اندیشیده شد.' },
    });

    const submitBtn = screen.getByRole('button', { name: 'ثبت پاسخ' });
    fireEvent.click(submitBtn);

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/desk/reviews/rev_02/reply'),
        expect.objectContaining({
          method: 'POST',
        })
      );
    });

    expect(await screen.findByText('پاسخ مدیر دفتر با موفقیت ثبت شد.')).toBeInTheDocument();
  });

  it('hides reply button for office_operator (Role enforcement §7.3)', async () => {
    // Switch auth store to office_operator
    useOperatorAuthStore.setState({
      isAuthenticated: true,
      operator: {
        id: 'op_regular_01',
        office_id: 'off_01',
        username: 'operator_reza',
        full_name: 'رضا کارشناس',
        role: 'operator',
        role_name: 'اپراتور باجه',
        counter_number: 2,
        is_active: true,
        last_login_at: '2026-09-16T08:00:00Z',
      },
    });

    renderWithProviders(<ReviewsPage />);

    expect(await screen.findByText('زمان معطلی در باجه بیش از حد انتظار بود.')).toBeInTheDocument();

    // Reply buttons must NOT be rendered for regular operator
    expect(screen.queryByTestId('reply-btn-rev_01')).not.toBeInTheDocument();
    expect(screen.queryByTestId('reply-btn-rev_02')).not.toBeInTheDocument();
  });

  it('renders SLA Quality trend chart and breakdown properly', async () => {
    renderWithProviders(<ReviewsPage />);

    fireEvent.click(screen.getByTestId('subtab-sla-quality'));

    expect(await screen.findByText('امتیاز زنده کیفیت SLA')).toBeInTheDocument();
    expect(screen.getByText('-5 نمره')).toBeInTheDocument();
    expect(screen.getByText('1 بار')).toBeInTheDocument();
  });

  it('passes axe accessibility scan with zero violations', async () => {
    const { container } = renderWithProviders(<ReviewsPage />);

    // Wait for initial reviews to render
    await screen.findByText('بسیار سریع و با احترام انجام شد.');

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
