import React from 'react';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor, act } from '@testing-library/react';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { CaseDetailPage } from '../src/features/case-tracking/ui/CaseDetailPage';
import { citizenRealtime } from '../src/shared/realtime/echo';
import type { CaseDetail } from '../src/features/case-tracking/types';

describe('Real-Time & Polling Fallback E2E Scenarios (§4.10, D-22, TASK-080-T, E13 & E14)', () => {
  let queryClient: QueryClient;
  const originalFetch = globalThis.fetch;

  beforeEach(() => {
    citizenRealtime.reset();
    queryClient = new QueryClient({
      defaultOptions: {
        queries: {
          retry: false,
        },
      },
    });
    vi.clearAllMocks();
  });

  afterEach(() => {
    globalThis.fetch = originalFetch;
    vi.useRealTimers();
  });

  const baseCaseDetail: CaseDetail = {
    id: 'case-rt-101',
    tracking_code: 'PK-RT-101',
    status: 'assigned_to_office',
    turn_owner: 'office',
    turn_owner_label: 'دفتر پیشخوان',
    service: {
      id: 'srv-1',
      title: 'صدور کارت بازرگانی',
      tag: 'semi-online',
    },
    assigned_office: {
      id: 'off-1',
      name: 'دفتر پیشخوان ۱۰۴۲',
      phone: '021-88776655',
    },
    current_step: 2,
    total_steps: 5,
    documents: [],
    timeline: [
      {
        id: 'step-1',
        title: 'ثبت پرونده',
        status: 'done',
        turn_owner: 'system',
        occurred_at: '2026-09-15T10:00:00Z',
      },
      {
        id: 'step-2',
        title: 'واگذاری به دفتر',
        status: 'current',
        turn_owner: 'office',
        occurred_at: '2026-09-15T10:05:00Z',
      },
    ],
    available_actions: ['open_chat'],
  };

  it('E13: receives status changes in real-time (< 2s) via WebSocket and invalidates query cache (§4.10)', async () => {
    // 1. WebSocket connects successfully
    citizenRealtime.setConnectionState('connected');

    let currentCaseStatus = 'assigned_to_office';
    let fetchCount = 0;

    globalThis.fetch = vi.fn().mockImplementation((url: string) => {
      if (url.includes('/cases/PK-RT-101')) {
        fetchCount++;
        return Promise.resolve({
          ok: true,
          json: async () => ({
            data: {
              ...baseCaseDetail,
              status: currentCaseStatus,
            },
          }),
        } as Response);
      }
      return Promise.resolve({ ok: false, status: 404 } as Response);
    });

    render(
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/cases/PK-RT-101']}>
          <Routes>
            <Route path="/cases/:trackingCode" element={<CaseDetailPage />} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );

    // Initial render
    await waitFor(() => {
      expect(screen.getByText('PK-RT-101')).toBeInTheDocument();
      expect(fetchCount).toBeGreaterThanOrEqual(1);
    });

    // Slow update badge should NOT be present when WebSocket is connected
    expect(screen.queryByTestId('slow-update-badge')).toBeNull();

    // 2. Server updates case and Reverb emits real-time event
    currentCaseStatus = 'expert_review';
    const channel = citizenRealtime.channel('private-case.PK-RT-101');

    act(() => {
      channel.emit('case.status_changed', {
        case_id: 'case-rt-101',
        status: 'expert_review',
      });
    });

    // Golden Rule §4.10: Event invalidated TanStack query cache, triggering immediate refetch
    await waitFor(() => {
      expect(fetchCount).toBeGreaterThanOrEqual(2);
    });
  });

  it('E14: activates mandatory fallback mode when WebSocket blocked, refetches every 15s (D-22)', async () => {
    vi.useFakeTimers();

    let fetchCalls = 0;
    globalThis.fetch = vi.fn().mockImplementation((url: string) => {
      if (url.includes('/cases/PK-RT-101')) {
        fetchCalls++;
        return Promise.resolve({
          ok: true,
          json: async () => ({
            data: baseCaseDetail,
          }),
        } as Response);
      }
      return Promise.resolve({ ok: false, status: 404 } as Response);
    });

    render(
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/cases/PK-RT-101']}>
          <Routes>
            <Route path="/cases/:trackingCode" element={<CaseDetailPage />} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );

    // Initial fetch
    await act(async () => {
      await vi.advanceTimersByTimeAsync(100);
    });

    expect(fetchCalls).toBe(1);

    // At 5s: WSS is still connecting, not yet timed out
    await act(async () => {
      await vi.advanceTimersByTimeAsync(5000);
    });
    expect(screen.queryByTestId('slow-update-badge')).toBeNull();

    // At 10s: WSS connection failed to establish -> transitions to 'unavailable'
    await act(async () => {
      await vi.advanceTimersByTimeAsync(5000);
    });

    // Fallback slow update badge is rendered to notify citizen
    expect(screen.getByTestId('slow-update-badge')).toBeInTheDocument();
    expect(screen.getByTestId('slow-update-badge')).toHaveTextContent(
      'حالت به‌روزرسانی کند (دریافت اطلاعات هر ۱۵ ثانیه)'
    );

    // Advance 15s -> Polling triggers automatic refetch without breaking UX
    const callsBeforePoll = fetchCalls;
    await act(async () => {
      await vi.advanceTimersByTimeAsync(15000);
    });

    expect(fetchCalls).toBeGreaterThan(callsBeforePoll);
  });
});
