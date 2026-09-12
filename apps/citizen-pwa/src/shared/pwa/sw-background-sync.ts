/**
 * Service Worker Background Sync event handler (Architecture §4.6)
 * Processes offline outbox queue using short-lived 15-min PAT from POST /auth/refresh cookie.
 */
import { outboxDb } from '../offline/outbox';
import { flushOutbox } from '../offline/sync';

export function setupBackgroundSyncHandler(sw: EventTarget): void {
  sw.addEventListener('sync', (event: unknown) => {
    const syncEvent = event as { tag: string; waitUntil: (p: Promise<unknown>) => void };
    if (syncEvent.tag === 'pishkhan-outbox-sync') {
      syncEvent.waitUntil(handleOutboxBackgroundSync());
    }
  });
}

async function handleOutboxBackgroundSync(): Promise<void> {
  let token: string | undefined;

  try {
    // Attempt to retrieve fresh 15-minute PAT via HTTP-only refresh cookie
    const refreshRes = await fetch('/api/v1/auth/refresh', {
      method: 'POST',
      credentials: 'include',
      headers: { Accept: 'application/json' },
    });

    if (refreshRes.ok) {
      const json = (await refreshRes.json()) as { data?: { access_token?: string } };
      token = json.data?.access_token;
    }
  } catch {
    // If refresh fails, proceed without token for public/whitelisted endpoints
  }

  await flushOutbox(token);
}

export async function getPendingOutboxCount(): Promise<number> {
  try {
    return await outboxDb.outbox.where('status').equals('pending').count();
  } catch {
    return 0;
  }
}
