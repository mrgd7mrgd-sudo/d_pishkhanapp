import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import { QueuePage } from '../ui/QueuePage';
import { useQueueStore } from '../model/useQueueStore';
import { realtimeManager } from '../../../shared/realtime/echo';
import { OfficeQueueData } from '../types';

describe('Operator Desk Queue Slice (§4.4, §5.7, §6.7, TASK-079, TASK-079-T)', () => {
  const mockFetch = vi.fn();

  const dummyQueue: OfficeQueueData = {
    office_id: 'off_thr_01',
    waiting_queue: 7,
    active_counters: 3,
    estimated_wait_minutes: 35,
    updated_at: new Date().toISOString(),
  };

  beforeEach(() => {
    vi.stubGlobal('fetch', mockFetch);
    useQueueStore.getState().reset();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('renders queue statistics fetched from API', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: dummyQueue }),
    });

    render(<QueuePage officeId="off_thr_01" autoFetch={true} />);

    await waitFor(() => {
      expect(screen.getByText('مدیریت صف زنده مراجعین دفتر')).toBeInTheDocument();
      expect(screen.getByText('۷')).toBeInTheDocument(); // waiting_queue in Persian
      expect(screen.getByText('۳۵')).toBeInTheDocument(); // estimated_wait_minutes in Persian
    });
  });

  it('calls next ticket and updates counter and calling state with sound chime', async () => {
    useQueueStore.setState({
      queue: dummyQueue,
      counterNumber: 2,
      loading: false,
    });

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        ticket_number: 'A-101',
        message: 'نوبت بعدی به باجه ۲ فراخوانی شد.',
      }),
    });

    render(<QueuePage officeId="off_thr_01" autoFetch={false} />);

    const callNextBtn = screen.getByRole('button', { name: /فراخوانی نوبت بعدی/i });
    fireEvent.click(callNextBtn);

    await waitFor(() => {
      expect(screen.getByText('نوبت A-101')).toBeInTheDocument();
      expect(screen.getByText('باجه ۲')).toBeInTheDocument();
    });
  });

  it('receives queue.updated event via realtime channel and updates state reactively without page refresh', async () => {
    useQueueStore.setState({ queue: dummyQueue, loading: false });

    render(<QueuePage officeId="off_thr_01" autoFetch={false} />);

    const channel = realtimeManager.getChannel('office.off_thr_01');
    expect(channel).toBeDefined();

    // Broadcast .queue.updated event from Laravel Reverb (§5.7)
    act(() => {
      channel?.emit('.queue.updated', {
        waiting_queue: 12,
        active_counters: 4,
        estimated_wait_minutes: 45,
      });
    });

    expect(screen.getByText('۱۲')).toBeInTheDocument();
    expect(screen.getByText('۴۵')).toBeInTheDocument();
  });

  it('passes accessibility audits with zero axe violations', async () => {
    useQueueStore.setState({ queue: dummyQueue, loading: false });

    const { container } = render(<QueuePage officeId="off_thr_01" autoFetch={false} />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
