import { outboxDb, type OutboxItem } from './outbox';

const MAX_ATTEMPTS = 5;

/**
 * Execute network send for a single outbox item with UUID v7 Idempotency-Key
 */
async function sendItem(item: OutboxItem, token?: string): Promise<boolean> {
  const headers: Record<string, string> = {
    'Idempotency-Key': item.id,
    Accept: 'application/json',
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  let body: BodyInit | undefined;
  if (item.files && item.files.length > 0) {
    const formData = new FormData();
    if (typeof item.body === 'object' && item.body !== null) {
      Object.entries(item.body as Record<string, unknown>).forEach(([k, v]) => {
        formData.append(k, typeof v === 'string' ? v : JSON.stringify(v));
      });
    }
    item.files.forEach((f) => formData.append('files[]', f.blob, f.name));
    body = formData;
  } else if (item.body) {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(item.body);
  }

  const init: RequestInit = {
    method: item.method,
    headers,
  };
  if (body !== undefined) {
    init.body = body;
  }

  const res = await fetch(item.endpoint, init);

  return res.ok || res.status === 409; // 409 Conflict with idempotent replay is considered successful
}

/**
 * Flush all pending outbox items sequentially with exponential backoff (§4.6)
 */
export async function flushOutbox(token?: string): Promise<{ sent: number; failed: number }> {
  if (typeof navigator !== 'undefined' && !navigator.onLine) {
    return { sent: 0, failed: 0 };
  }

  const items = await outboxDb.outbox
    .where('status')
    .equals('pending')
    .sortBy('createdAt');

  let sentCount = 0;
  let failedCount = 0;

  for (const item of items) {
    try {
      await outboxDb.outbox.update(item.id, { status: 'syncing' });
      const ok = await sendItem(item, token);

      if (ok) {
        await outboxDb.outbox.delete(item.id);
        sentCount++;
      } else {
        const nextAttempts = item.attempts + 1;
        await outboxDb.outbox.update(item.id, {
          attempts: nextAttempts,
          status: nextAttempts >= MAX_ATTEMPTS ? 'failed' : 'pending',
          lastError: `HTTP Error`,
        });
        failedCount++;
      }
    } catch (err) {
      const nextAttempts = item.attempts + 1;
      await outboxDb.outbox.update(item.id, {
        attempts: nextAttempts,
        status: nextAttempts >= MAX_ATTEMPTS ? 'failed' : 'pending',
        lastError: err instanceof Error ? err.message : 'Network error',
      });
      failedCount++;
    }
  }

  return { sent: sentCount, failed: failedCount };
}

/**
 * Register Background Sync or setup window online event listener fallback
 */
export function registerSyncTrigger(onFlush: () => void): void {
  if (typeof window === 'undefined') return;

  window.addEventListener('online', () => {
    onFlush();
  });

  if ('serviceWorker' in navigator && 'SyncManager' in window) {
    navigator.serviceWorker.ready.then((registration) => {
      // Background Sync registered
      return (registration as unknown as { sync?: { register: (tag: string) => Promise<void> } }).sync?.register('pishkhan-outbox-sync');
    }).catch(() => {
      // Fallback is online event listener
    });
  }
}
