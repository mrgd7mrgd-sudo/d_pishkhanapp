import { useState, useCallback, useEffect } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { messagingApi } from '../api/messagingApi';
import { useRealtimeOrPoll } from '@/shared/realtime';
import { useOutbox } from '@/shared/offline/useOutbox';
import type { CaseMessage } from '../types';

export interface UseCaseChatOptions {
  caseId: string;
}

export function useCaseChat({ caseId }: UseCaseChatOptions) {
  const queryClient = useQueryClient();
  const { enqueue, pendingCount } = useOutbox();
  const [optimisticMessages, setOptimisticMessages] = useState<CaseMessage[]>([]);

  const queryKey = ['case-messages', caseId];

  // 1. Realtime sync + 10s fallback to 15s polling (§4.10, D-22)
  const { isSlowUpdateMode, isPollingFallback, refetchInterval } = useRealtimeOrPoll({
    channelName: caseId ? `private-case.${caseId}` : '',
    queryKey,
    events: ['message.new'],
    pollIntervalMs: 15000,
    connectionTimeoutMs: 10000,
  });

  // 2. TanStack Query
  const {
    data,
    isLoading,
    isError,
    refetch,
  } = useQuery({
    queryKey,
    queryFn: () => (caseId ? messagingApi.getCaseMessages(caseId) : Promise.resolve({ case_id: caseId, unread_count: 0, items: [] })),
    enabled: Boolean(caseId),
    refetchInterval,
  });

  // 3. Clear optimistic messages that are now present in server response
  useEffect(() => {
    if (data?.items && data.items.length > 0) {
      const serverBodies = new Set(data.items.map((m) => `${m.body}-${m.sender_type}`));
      setOptimisticMessages((prev) =>
        prev.filter((opt) => !serverBodies.has(`${opt.body}-${opt.sender_type}`))
      );
    }
  }, [data?.items]);

  // 4. Optimistic Send with Offline Queue fallback (§4.6)
  const sendMessage = useCallback(
    async (body: string) => {
      const trimmed = body.trim();
      if (!trimmed || !caseId) return;

      const tempId = `opt-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
      const newOptimisticMsg: CaseMessage = {
        id: tempId,
        case_id: caseId,
        sender_type: 'citizen',
        sender_id: 'me',
        sender_name: 'شما',
        body: trimmed,
        created_at: new Date().toISOString(),
        status: 'sending',
      };

      setOptimisticMessages((prev) => [...prev, newOptimisticMsg]);

      const isOnline = typeof navigator !== 'undefined' ? navigator.onLine : true;

      if (!isOnline) {
        setOptimisticMessages((prev) =>
          prev.map((m) => (m.id === tempId ? { ...m, status: 'pending_offline' } : m))
        );
        await enqueue(`/api/v1/cases/${caseId}/messages`, 'POST', { body: trimmed });
        return;
      }

      try {
        await messagingApi.sendCaseMessage(caseId, { body: trimmed });
        // Golden Rule §4.10: Realtime/Mutation invalidates query cache
        await queryClient.invalidateQueries({ queryKey });
        setOptimisticMessages((prev) => prev.filter((m) => m.id !== tempId));
      } catch {
        const currentlyOnline = typeof navigator !== 'undefined' ? navigator.onLine : true;
        if (!currentlyOnline) {
          setOptimisticMessages((prev) =>
            prev.map((m) => (m.id === tempId ? { ...m, status: 'pending_offline' } : m))
          );
          await enqueue(`/api/v1/cases/${caseId}/messages`, 'POST', { body: trimmed });
        } else {
          setOptimisticMessages((prev) =>
            prev.map((m) => (m.id === tempId ? { ...m, status: 'failed' } : m))
          );
        }
      }
    },
    [caseId, enqueue, queryClient, queryKey]
  );

  const retryMessage = useCallback(
    async (tempId: string) => {
      const msg = optimisticMessages.find((m) => m.id === tempId);
      if (!msg || !caseId) return;

      setOptimisticMessages((prev) =>
        prev.map((m) => (m.id === tempId ? { ...m, status: 'sending' } : m))
      );

      try {
        await messagingApi.sendCaseMessage(caseId, { body: msg.body });
        await queryClient.invalidateQueries({ queryKey });
        setOptimisticMessages((prev) => prev.filter((m) => m.id !== tempId));
      } catch {
        setOptimisticMessages((prev) =>
          prev.map((m) => (m.id === tempId ? { ...m, status: 'failed' } : m))
        );
      }
    },
    [caseId, optimisticMessages, queryClient, queryKey]
  );

  const removeFailedMessage = useCallback((tempId: string) => {
    setOptimisticMessages((prev) => prev.filter((m) => m.id !== tempId));
  }, []);

  // Merge server and optimistic messages
  const serverMessages = data?.items ?? [];
  const serverIds = new Set(serverMessages.map((m) => m.id));
  const activeOptimistic = optimisticMessages.filter((m) => !serverIds.has(m.id));
  const messages = [...serverMessages, ...activeOptimistic];

  return {
    messages,
    unreadCount: data?.unread_count ?? 0,
    isLoading,
    isError,
    refetch,
    sendMessage,
    retryMessage,
    removeFailedMessage,
    isSlowUpdateMode,
    isPollingFallback,
    pendingOfflineCount: pendingCount,
  };
}
