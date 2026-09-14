import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import { OffersPanel } from '../ui/OffersPanel';
import { useOffersStore } from '../model/useOffersStore';
import { realtimeManager } from '../../../shared/realtime/echo';
import { DispatchOfferItem } from '../types';

describe('Operator Desk Offers Slice (§4.4, §5.7, TASK-077, TASK-077-T)', () => {
  const mockFetch = vi.fn();

  const dummyOffer1: DispatchOfferItem = {
    id: 'off_001',
    case_id: 'case_001',
    tracking_code: 'CR-1405-99841',
    service_id: 'svc_card',
    service_title: 'صدور کارت هوشمند ملی',
    round: 1,
    status: 'pending',
    created_at: new Date().toISOString(),
    expires_at: new Date(Date.now() + 90_000).toISOString(),
    remaining_seconds: 90,
  };

  const dummyOffer2: DispatchOfferItem = {
    id: 'off_002',
    case_id: 'case_002',
    tracking_code: 'CR-1405-11223',
    service_id: 'svc_cert',
    service_title: 'تأییدیه تحصیلی',
    round: 2,
    status: 'pending',
    created_at: new Date().toISOString(),
    expires_at: new Date(Date.now() + 30_000).toISOString(),
    remaining_seconds: 30,
  };

  beforeEach(() => {
    vi.stubGlobal('fetch', mockFetch);
    useOffersStore.getState().reset();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
  });

  it('renders active offers fetched from API and displays countdown', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [dummyOffer1] }),
    });

    render(<OffersPanel officeId="off_thr_01" />);

    await waitFor(() => {
      expect(screen.getByText('CR-1405-99841')).toBeInTheDocument();
      expect(screen.getByText('صدور کارت هوشمند ملی')).toBeInTheDocument();
      expect(screen.getByText('دور ۱')).toBeInTheDocument();
      expect(screen.getByText('۱ پیشنهاد فعال')).toBeInTheDocument();
    });
  });

  it('decrements live countdown every second and automatically removes card when counter reaches 0', async () => {
    vi.useFakeTimers();

    const shortOffer: DispatchOfferItem = {
      ...dummyOffer1,
      id: 'off_short',
      remaining_seconds: 2,
    };

    useOffersStore.setState({ offers: [shortOffer], loading: false });

    render(<OffersPanel officeId="off_thr_01" autoFetch={false} />);

    expect(screen.getByText('CR-1405-99841')).toBeInTheDocument();

    // Advance 1 second -> remaining 1
    act(() => {
      vi.advanceTimersByTime(1000);
    });
    expect(useOffersStore.getState().offers[0]?.remaining_seconds).toBe(1);

    // Advance 1 more second -> remaining 0, automatically removed!
    act(() => {
      vi.advanceTimersByTime(1000);
    });

    expect(useOffersStore.getState().offers).toHaveLength(0);
    expect(screen.queryByText('CR-1405-99841')).not.toBeInTheDocument();
    expect(screen.getByText('پیشنهاد فعالی در این لحظه وجود ندارد.')).toBeInTheDocument();
  });

  it('handles 409 conflict during acceptance by displaying user-friendly message', async () => {
    useOffersStore.setState({ offers: [dummyOffer1], loading: false });

    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 409,
      json: async () => ({
        status: 409,
        code: 'OFFER_ALREADY_TAKEN',
        detail: 'این پرونده را دفتر دیگری پذیرفت',
      }),
    });

    render(<OffersPanel officeId="off_thr_01" autoFetch={false} />);

    const acceptBtn = screen.getByRole('button', { name: /پذیرش پرونده/i });
    fireEvent.click(acceptBtn);

    await waitFor(() => {
      // Must display exact friendly message rather than raw error
      expect(screen.getByText('این پرونده را دفتر دیگری پذیرفت')).toBeInTheDocument();
    });

    // Dismiss conflict error banner
    const dismissBtn = screen.getByRole('button', { name: /بستن/i });
    fireEvent.click(dismissBtn);

    expect(screen.queryByText('این پرونده را دفتر دیگری پذیرفت')).not.toBeInTheDocument();
  });

  it('successfully accepts offer and removes it from panel', async () => {
    useOffersStore.setState({ offers: [dummyOffer1], loading: false });

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        data: {
          case_id: dummyOffer1.case_id,
          tracking_code: dummyOffer1.tracking_code,
          status: 'assigned_to_office',
          office_id: 'off_thr_01',
          operator_id: 'op_123',
        },
        message: 'پیشنهاد با موفقیت پذیرفته شد.',
      }),
    });

    render(<OffersPanel officeId="off_thr_01" autoFetch={false} />);

    const acceptBtn = screen.getByRole('button', { name: /پذیرش پرونده/i });
    fireEvent.click(acceptBtn);

    await waitFor(() => {
      expect(screen.queryByText('CR-1405-99841')).not.toBeInTheDocument();
      expect(useOffersStore.getState().offers).toHaveLength(0);
    });
  });

  it('successfully declines offer and removes it from panel', async () => {
    useOffersStore.setState({ offers: [dummyOffer1], loading: false });

    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        data: { offer_id: dummyOffer1.id, status: 'declined' },
        message: 'پیشنهاد رد شد.',
      }),
    });

    render(<OffersPanel officeId="off_thr_01" autoFetch={false} />);

    const declineBtn = screen.getByRole('button', { name: /رد/i });
    fireEvent.click(declineBtn);

    await waitFor(() => {
      expect(screen.queryByText('CR-1405-99841')).not.toBeInTheDocument();
      expect(useOffersStore.getState().offers).toHaveLength(0);
    });
  });

  it('dynamically receives new offer and removes taken offer via realtime events', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [] }),
    });

    render(<OffersPanel officeId="off_thr_01" />);

    await waitFor(() => {
      expect(screen.getByText('پیشنهاد فعالی در این لحظه وجود ندارد.')).toBeInTheDocument();
    });

    const channel = realtimeManager.getChannel('office.off_thr_01');
    expect(channel).toBeDefined();

    // Broadcast .offer.new
    act(() => {
      channel?.emit('.offer.new', { offer: dummyOffer2 });
    });

    expect(screen.getByText('CR-1405-11223')).toBeInTheDocument();
    expect(screen.getByText('تأییدیه تحصیلی')).toBeInTheDocument();

    // Broadcast .offer.taken by another office
    act(() => {
      channel?.emit('.offer.taken', { offer_id: dummyOffer2.id });
    });

    expect(screen.queryByText('CR-1405-11223')).not.toBeInTheDocument();
  });

  it('passes accessibility audits with zero axe violations', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: [dummyOffer1, dummyOffer2] }),
    });

    const { container } = render(<OffersPanel officeId="off_thr_01" />);

    await waitFor(() => {
      expect(screen.getByText('CR-1405-99841')).toBeInTheDocument();
    });

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
