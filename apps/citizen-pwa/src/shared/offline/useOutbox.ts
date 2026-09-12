import { useState, useEffect, useCallback } from 'react';
import { outboxDb, type OutboxItem } from './outbox';
import { generateUuidV7 } from './uuidv7';
import { flushOutbox, registerSyncTrigger } from './sync';

async function fetchOutboxCounts(): Promise<{ pending: number; failed: number }> {
  try {
    const pending = await outboxDb.outbox.where('status').equals('pending').count();
    const failed = await outboxDb.outbox.where('status').equals('failed').count();
    return { pending, failed };
  } catch {
    return { pending: 0, failed: 0 };
  }
}

async function markFailedAsPending(): Promise<void> {
  const failedItems = await outboxDb.outbox.where('status').equals('failed').toArray();
  for (const item of failedItems) {
    await outboxDb.outbox.update(item.id, { status: 'pending', attempts: 0 });
  }
}

export function useOutbox() {
  const [pendingCount, setPendingCount] = useState<number>(0);
  const [failedCount, setFailedCount] = useState<number>(0);
  const [isSyncing, setIsSyncing] = useState<boolean>(false);

  const refreshCounts = useCallback(async () => {
    const counts = await fetchOutboxCounts();
    setPendingCount(counts.pending);
    setFailedCount(counts.failed);
  }, []);

  const triggerSync = useCallback(async (token?: string) => {
    setIsSyncing(true);
    try {
      await flushOutbox(token);
    } finally {
      setIsSyncing(false);
      await refreshCounts();
    }
  }, [refreshCounts]);

  useEffect(() => {
    refreshCounts();
    registerSyncTrigger(() => { triggerSync(); });
  }, [refreshCounts, triggerSync]);

  const enqueue = useCallback(async (
    endpoint: string,
    method: 'POST' | 'PATCH' | 'PUT',
    body: unknown,
    files?: { name: string; blob: Blob }[]
  ): Promise<string> => {
    const id = generateUuidV7();
    const item: OutboxItem = { id, createdAt: Date.now(), endpoint, method, body, files, status: 'pending', attempts: 0 };
    await outboxDb.outbox.add(item);
    await refreshCounts();
    if (typeof navigator !== 'undefined' && navigator.onLine) {
      triggerSync();
    }
    return id;
  }, [refreshCounts, triggerSync]);

  const retryFailed = useCallback(async () => {
    await markFailedAsPending();
    await refreshCounts();
    if (typeof navigator !== 'undefined' && navigator.onLine) {
      triggerSync();
    }
  }, [refreshCounts, triggerSync]);

  return { pendingCount, failedCount, isSyncing, enqueue, triggerSync, retryFailed };
}
