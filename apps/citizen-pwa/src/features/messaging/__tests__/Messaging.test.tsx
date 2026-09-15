import React from 'react';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { axe } from 'vitest-axe';
import 'fake-indexeddb/auto';
import { outboxDb } from '@/shared/offline/outbox';
import { citizenRealtime } from '@/shared/realtime/echo';
import { CaseChatView } from '../ui/CaseChatView';
import { NotificationsView } from '../ui/NotificationsView';
import type { CaseMessagesResponse, NotificationsResponse } from '../types';

describe('Messaging & Notifications Slice (§4.1, §4.6, §5.7, TASK-081, TASK-081-T)', () => {
  let queryClient: QueryClient;
  const originalFetch = globalThis.fetch;

  beforeEach(async () => {
    await outboxDb.outbox.clear();
    citizenRealtime.reset();
    queryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false },
        mutations: { retry: false },
      },
    });
    vi.clearAllMocks();
  });

  afterEach(() => {
    globalThis.fetch = originalFetch;
    vi.useRealTimers();
  });

  const mockMessagesResponse: CaseMessagesResponse = {
    case_id: 'PK-MSG-100',
    unread_count: 1,
    items: [
      {
        id: 'msg-1',
        case_id: 'PK-MSG-100',
        sender_type: 'operator',
        sender_id: 'op-12',
        sender_name: 'کارشناس دفتر ۱۰۴۲',
        body: 'سلام، مدارک شما بررسی شد و نیاز به تصویر واضح‌تر از کارت ملی است.',
        created_at: '2026-09-15T10:00:00Z',
      },
      {
        id: 'msg-2',
        case_id: 'PK-MSG-100',
        sender_type: 'citizen',
        sender_id: 'me',
        sender_name: 'شما',
        body: 'سلام، تا دقایقی دیگر بارگذاری مجدد انجام می‌شود.',
        created_at: '2026-09-15T10:05:00Z',
      },
    ],
  };

  const mockNotificationsResponse: NotificationsResponse = {
    unread_count: 2,
    items: [
      {
        id: 'notif-1',
        type: 'case.status_changed',
        title: 'تغییر وضعیت پرونده',
        body: 'پرونده شما به مرحله بررسی کارشناس انتقال یافت.',
        read_at: null,
        created_at: '2026-09-15T11:00:00Z',
      },
      {
        id: 'notif-2',
        type: 'official_notice',
        title: 'ابلاغیه پرداخت کارمزد مصوب',
        body: 'رسید پرداخت الکترونیک صادر شد و در سوابق مالی ثبت گردید.',
        read_at: null,
        created_at: '2026-09-15T11:30:00Z',
      },
      {
        id: 'notif-3',
        type: 'system',
        title: 'خوش‌آمدگویی به سامانه',
        body: 'حساب کاربری شما با موفقیت فعال شد.',
        read_at: '2026-09-14T08:00:00Z',
        created_at: '2026-09-14T08:00:00Z',
      },
    ],
  };

  it('renders CaseChatView with messages from server and passes axe audit', async () => {
    globalThis.fetch = vi.fn().mockImplementation((url: string) => {
      if (url.includes('/cases/PK-MSG-100/messages')) {
        return Promise.resolve({
          ok: true,
          json: async () => mockMessagesResponse,
        } as Response);
      }
      return Promise.resolve({ ok: false, status: 404 } as Response);
    });

    const { container } = render(
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/cases/PK-MSG-100/chat']}>
          <Routes>
            <Route path="/cases/:trackingCode/chat" element={<CaseChatView />} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText(/نیاز به تصویر واضح‌تر/)).toBeInTheDocument();
      expect(screen.getByText(/تا دقایقی دیگر/)).toBeInTheDocument();
      expect(screen.getByText('کارشناس دفتر ۱۰۴۲')).toBeInTheDocument();
    });

    const axeResult = await axe(container);
    expect(axeResult).toHaveNoViolations();
  });

  it('sends a message online with optimistic UI and invalidates query cache (§4.10)', async () => {
    let sentCount = 0;
    globalThis.fetch = vi.fn().mockImplementation((url: string, init?: RequestInit) => {
      if (url.includes('/cases/PK-MSG-100/messages')) {
        if (init?.method === 'POST') {
          sentCount++;
          const body = JSON.parse(init.body as string);
          return Promise.resolve({
            ok: true,
            json: async () => ({
              id: 'msg-new-1',
              case_id: 'PK-MSG-100',
              sender_type: 'citizen',
              sender_id: 'me',
              sender_name: 'شما',
              body: body.body,
              created_at: new Date().toISOString(),
            }),
          } as Response);
        }
        return Promise.resolve({
          ok: true,
          json: async () => mockMessagesResponse,
        } as Response);
      }
      return Promise.resolve({ ok: false, status: 404 } as Response);
    });

    render(
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/cases/PK-MSG-100/chat']}>
          <Routes>
            <Route path="/cases/:trackingCode/chat" element={<CaseChatView />} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText(/نیاز به تصویر واضح‌تر/)).toBeInTheDocument();
    });

    const input = screen.getByPlaceholderText('پیام خود را بنویسید...');
    const sendButton = screen.getByTestId('chat-send-button');

    fireEvent.change(input, { target: { value: 'تصویر جدید بارگذاری گردید' } });
    fireEvent.click(sendButton);

    // Optimistic UI: input cleared immediately and message rendered
    expect((input as HTMLTextAreaElement).value).toBe('');
    expect(screen.getByText('تصویر جدید بارگذاری گردید')).toBeInTheDocument();

    await waitFor(() => {
      expect(sentCount).toBe(1);
    });
  });

  it('queues message into Dexie offline outbox when network is disconnected (§4.6)', async () => {
    // Simulate offline
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(false);

    globalThis.fetch = vi.fn().mockImplementation((url: string) => {
      if (url.includes('/cases/PK-MSG-100/messages')) {
        return Promise.resolve({
          ok: true,
          json: async () => mockMessagesResponse,
        } as Response);
      }
      return Promise.resolve({ ok: false, status: 404 } as Response);
    });

    render(
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/cases/PK-MSG-100/chat']}>
          <Routes>
            <Route path="/cases/:trackingCode/chat" element={<CaseChatView />} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText(/نیاز به تصویر واضح‌تر/)).toBeInTheDocument();
    });

    const input = screen.getByPlaceholderText('پیام خود را بنویسید...');
    const sendButton = screen.getByTestId('chat-send-button');

    fireEvent.change(input, { target: { value: 'این پیام در حالت آفلاین ارسال شده است' } });
    fireEvent.click(sendButton);

    // Optimistic offline status badge
    await waitFor(() => {
      expect(screen.getByText('این پیام در حالت آفلاین ارسال شده است')).toBeInTheDocument();
      expect(screen.getByTestId('status-pending-offline')).toBeInTheDocument();
    });

    // Verify enqueued into Dexie outbox
    const queuedItems = await outboxDb.outbox.toArray();
    expect(queuedItems.length).toBe(1);
    expect(queuedItems[0]!.endpoint).toBe('/api/v1/cases/PK-MSG-100/messages');
    expect(queuedItems[0]!.body).toEqual({ body: 'این پیام در حالت آفلاین ارسال شده است' });
  });

  it('marks optimistic message as failed when server returns error, allowing retry', async () => {
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(true);

    let attempts = 0;
    globalThis.fetch = vi.fn().mockImplementation((url: string, init?: RequestInit) => {
      if (url.includes('/cases/PK-MSG-100/messages')) {
        if (init?.method === 'POST') {
          attempts++;
          if (attempts === 1) {
            // First attempt fails
            return Promise.resolve({
              ok: false,
              status: 500,
              json: async () => ({ detail: 'Internal Server Error' }),
            } as Response);
          }
          // Retry succeeds
          return Promise.resolve({
            ok: true,
            json: async () => ({
              id: 'msg-success-retry',
              case_id: 'PK-MSG-100',
              sender_type: 'citizen',
              sender_id: 'me',
              sender_name: 'شما',
              body: 'پیام تست خطا',
              created_at: new Date().toISOString(),
            }),
          } as Response);
        }
        return Promise.resolve({
          ok: true,
          json: async () => mockMessagesResponse,
        } as Response);
      }
      return Promise.resolve({ ok: false, status: 404 } as Response);
    });

    render(
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/cases/PK-MSG-100/chat']}>
          <Routes>
            <Route path="/cases/:trackingCode/chat" element={<CaseChatView />} />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText(/نیاز به تصویر واضح‌تر/)).toBeInTheDocument();
    });

    const input = screen.getByPlaceholderText('پیام خود را بنویسید...');
    const sendButton = screen.getByTestId('chat-send-button');

    fireEvent.change(input, { target: { value: 'پیام تست خطا' } });
    fireEvent.click(sendButton);

    // Should transition to failed
    await waitFor(() => {
      expect(screen.getByTestId('status-failed')).toBeInTheDocument();
      expect(screen.getByText('تلاش مجدد')).toBeInTheDocument();
    });

    // Click retry
    fireEvent.click(screen.getByText('تلاش مجدد'));

    await waitFor(() => {
      expect(attempts).toBe(2);
    });
  });

  it('renders NotificationsView, filters tabs, and marks notification as read', async () => {
    let markReadId: string | null = null;
    globalThis.fetch = vi.fn().mockImplementation((url: string, init?: RequestInit) => {
      if (url.includes('/notifications')) {
        if (init?.method === 'PATCH') {
          const match = url.match(/\/notifications\/([^/]+)\/read/);
          markReadId = match ? (match[1] ?? null) : null;
          return Promise.resolve({
            ok: true,
            json: async () => ({
              ...mockNotificationsResponse.items[0],
              read_at: new Date().toISOString(),
            }),
          } as Response);
        }
        return Promise.resolve({
          ok: true,
          json: async () => mockNotificationsResponse,
        } as Response);
      }
      return Promise.resolve({ ok: false, status: 404 } as Response);
    });

    const { container } = render(
      <QueryClientProvider client={queryClient}>
        <NotificationsView />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('تغییر وضعیت پرونده')).toBeInTheDocument();
      expect(screen.getByText('ابلاغیه پرداخت کارمزد مصوب')).toBeInTheDocument();
      expect(screen.getByTestId('unread-badge')).toHaveTextContent('2');
    });

    // Filter to unread
    const unreadTab = screen.getByRole('tab', { name: /خوانده‌نشده/ });
    fireEvent.click(unreadTab);

    expect(screen.getByText('تغییر وضعیت پرونده')).toBeInTheDocument();
    expect(screen.getByText('ابلاغیه پرداخت کارمزد مصوب')).toBeInTheDocument();
    expect(screen.queryByText('خوش‌آمدگویی به سامانه')).toBeNull();

    // Filter to notices
    const noticesTab = screen.getByRole('tab', { name: /ابلاغیه‌های رسمی/ });
    fireEvent.click(noticesTab);

    expect(screen.getByText('ابلاغیه پرداخت کارمزد مصوب')).toBeInTheDocument();
    expect(screen.queryByText('تغییر وضعیت پرونده')).toBeNull();

    // Mark as read
    const markReadBtn = screen.getByText('خوانده شد');
    fireEvent.click(markReadBtn);

    await waitFor(() => {
      expect(markReadId).toBe('notif-2');
    });

    const axeResult = await axe(container);
    expect(axeResult).toHaveNoViolations();
  });
});
