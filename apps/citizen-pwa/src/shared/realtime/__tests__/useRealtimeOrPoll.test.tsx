import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { citizenRealtime } from '../echo';
import { useRealtimeOrPoll } from '../useRealtimeOrPoll';

describe('Citizen PWA Realtime & Polling Fallback (§4.10, D-22, TASK-080, TASK-080-T)', () => {
  let queryClient: QueryClient;

  const createWrapper = () => {
    queryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false },
      },
    });

    return ({ children }: { children: React.ReactNode }) => (
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );
  };

  beforeEach(() => {
    citizenRealtime.reset();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('connects to realtime channel and invalidates TanStack query cache upon domain event (Golden Rule §4.10)', () => {
    citizenRealtime.setConnectionState('connected');

    const wrapper = createWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');

    const { result } = renderHook(
      () =>
        useRealtimeOrPoll({
          channelName: 'private-case.123',
          queryKey: ['case', 'CR-1405-99'],
        }),
      { wrapper }
    );

    expect(result.current.connectionState).toBe('connected');
    expect(result.current.isPollingFallback).toBe(false);
    expect(result.current.refetchInterval).toBe(false);
    expect(result.current.isSlowUpdateMode).toBe(false);

    // Emit domain event from Reverb
    const chan = citizenRealtime.channel('private-case.123');
    act(() => {
      chan.emit('case.status_changed', { status: 'expert_review' });
    });

    // Verify Golden Rule §4.10: Never mutate state directly, invalidate query cache
    expect(invalidateSpy).toHaveBeenCalledWith({
      queryKey: ['case', 'CR-1405-99'],
    });
  });

  it('triggers 10s fallback timer: when WSS fails or times out, switches to 15000ms polling and flags slow update mode (D-22)', () => {
    vi.useFakeTimers();

    const wrapper = createWrapper();
    const { result } = renderHook(
      () =>
        useRealtimeOrPoll({
          channelName: 'private-case.456',
          queryKey: ['case', 'CR-1405-456'],
          connectionTimeoutMs: 10000,
          pollIntervalMs: 15000,
        }),
      { wrapper }
    );

    expect(result.current.connectionState).toBe('connecting');
    expect(result.current.isSlowUpdateMode).toBe(false);

    // Advance timers by 10 seconds (10,000ms) without WSS connection
    act(() => {
      vi.advanceTimersByTime(10000);
    });

    expect(result.current.connectionState).toBe('unavailable');
    expect(result.current.isPollingFallback).toBe(true);
    expect(result.current.isSlowUpdateMode).toBe(true);
    expect(result.current.refetchInterval).toBe(15000);
  });

  it('restores normal fast realtime mode when connection reconnects', () => {
    const wrapper = createWrapper();
    const { result } = renderHook(
      () =>
        useRealtimeOrPoll({
          channelName: 'private-case.789',
          queryKey: ['case', 'CR-1405-789'],
        }),
      { wrapper }
    );

    // Explicitly set unavailable
    act(() => {
      citizenRealtime.setConnectionState('unavailable');
    });
    expect(result.current.isSlowUpdateMode).toBe(true);
    expect(result.current.refetchInterval).toBe(15000);

    // Reconnect
    act(() => {
      citizenRealtime.setConnectionState('connected');
    });
    expect(result.current.isSlowUpdateMode).toBe(false);
    expect(result.current.refetchInterval).toBe(false);
  });
});
