import { useEffect, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { citizenRealtime, ConnectionState } from './echo';

export interface UseRealtimeOrPollOptions {
  channelName: string;
  queryKey: unknown[];
  events?: string[];
  pollIntervalMs?: number;
  connectionTimeoutMs?: number;
}

export interface UseRealtimeOrPollResult {
  isPollingFallback: boolean;
  isSlowUpdateMode: boolean;
  connectionState: ConnectionState;
  refetchInterval: number | false;
}

/**
 * useRealtimeOrPoll hook (Architecture §4.10, D-22).
 *
 * Golden Rule: Realtime events NEVER mutate component state directly;
 * they ONLY invalidate or patch the TanStack React Query cache!
 *
 * Mandatory Fallback: If WSS connection does not establish within 10 seconds,
 * switches to polling fallback with 15000ms refetchInterval and flags slow update mode.
 */
export function useRealtimeOrPoll({
  channelName,
  queryKey,
  events = ['case.status_changed', 'case.timeline_appended', 'message.new'],
  pollIntervalMs = 15000,
  connectionTimeoutMs = 10000,
}: UseRealtimeOrPollOptions): UseRealtimeOrPollResult {
  const queryClient = useQueryClient();
  const [connectionState, setConnectionState] = useState<ConnectionState>(
    citizenRealtime.getConnectionState()
  );
  const [isSlowMode, setIsSlowMode] = useState<boolean>(false);

  // 1. Connection state monitoring & 10s fallback timer
  useEffect(() => {
    let timer: ReturnType<typeof setTimeout> | null = null;

    const unsubscribe = citizenRealtime.onConnectionChange((state) => {
      setConnectionState(state);
      if (state === 'connected') {
        setIsSlowMode(false);
        if (timer) clearTimeout(timer);
      } else if (state === 'unavailable' || state === 'failed') {
        setIsSlowMode(true);
      }
    });

    if (citizenRealtime.getConnectionState() !== 'connected') {
      timer = setTimeout(() => {
        if (citizenRealtime.getConnectionState() !== 'connected') {
          citizenRealtime.setConnectionState('unavailable');
          setIsSlowMode(true);
        }
      }, connectionTimeoutMs);
    }

    return () => {
      unsubscribe();
      if (timer) clearTimeout(timer);
    };
  }, [connectionTimeoutMs]);

  // 2. Realtime channel subscription & cache invalidation
  useEffect(() => {
    if (!channelName) return;

    const chan = citizenRealtime.channel(channelName);

    const handleEvent = () => {
      // Golden Rule §4.10: Never write state directly — invalidate query cache
      void queryClient.invalidateQueries({ queryKey });
    };

    events.forEach((ev) => {
      chan.listen(ev, handleEvent);
      chan.listen(`.${ev}`, handleEvent);
    });

    return () => {
      events.forEach((ev) => {
        chan.stopListening(ev);
        chan.stopListening(`.${ev}`);
      });
      citizenRealtime.leave(channelName);
    };
  }, [channelName, queryClient, queryKey, events]);

  const isPollingFallback = connectionState !== 'connected';
  const refetchInterval = isPollingFallback ? pollIntervalMs : false;

  return {
    isPollingFallback,
    isSlowUpdateMode: isSlowMode,
    connectionState,
    refetchInterval,
  };
}
