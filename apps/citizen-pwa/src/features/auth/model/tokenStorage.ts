/**
 * Token Storage Architecture (§7.2, D-12)
 *
 * 1. Citizen PWA Access Token is strictly held IN-MEMORY.
 *    localStorage and sessionStorage are strictly FORBIDDEN for tokens (prevents XSS theft).
 * 2. Service Worker short-lived PAT (15-min TTL) is stored in IndexedDB for Background Sync,
 *    strictly purged once expired.
 */

let inMemoryAccessToken: string | null = null;

export const getAccessToken = (): string | null => inMemoryAccessToken;

export const setAccessToken = (token: string | null): void => {
  inMemoryAccessToken = token;
};

export const clearAccessToken = (): void => {
  inMemoryAccessToken = null;
};

const IDB_NAME = 'pishkhan_auth_db';
const IDB_STORE = 'sw_tokens';
const IDB_KEY = 'sw_pat';
const IDB_VERSION = 1;

interface SwTokenRecord {
  token: string;
  expires_at: string;
  expires_at_ms: number;
}

const openDatabase = (): Promise<IDBDatabase> => {
  return new Promise((resolve, reject) => {
    if (typeof indexedDB === 'undefined') {
      reject(new Error('IndexedDB is not supported in this environment'));
      return;
    }

    const request = indexedDB.open(IDB_NAME, IDB_VERSION);

    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains(IDB_STORE)) {
        db.createObjectStore(IDB_STORE);
      }
    };

    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
};

export const storeSwPat = async (
  token: string,
  expiresAt: string,
  ttlSeconds: number
): Promise<void> => {
  try {
    const db = await openDatabase();
    const expiresAtMs = Date.now() + ttlSeconds * 1000;
    const record: SwTokenRecord = {
      token,
      expires_at: expiresAt,
      expires_at_ms: expiresAtMs,
    };

    await new Promise<void>((resolve, reject) => {
      const tx = db.transaction(IDB_STORE, 'readwrite');
      const store = tx.objectStore(IDB_STORE);
      const putRequest = store.put(record, IDB_KEY);

      putRequest.onsuccess = () => resolve();
      putRequest.onerror = () => reject(putRequest.error);
      tx.oncomplete = () => db.close();
    });
  } catch {
    // IndexedDB failure (e.g. private browsing mode) handled gracefully
  }
};

export const getSwPat = async (): Promise<string | null> => {
  try {
    const db = await openDatabase();
    const record = await new Promise<SwTokenRecord | undefined>((resolve, reject) => {
      const tx = db.transaction(IDB_STORE, 'readonly');
      const store = tx.objectStore(IDB_STORE);
      const getRequest = store.get(IDB_KEY);

      getRequest.onsuccess = () => resolve(getRequest.result as SwTokenRecord | undefined);
      getRequest.onerror = () => reject(getRequest.error);
      tx.oncomplete = () => db.close();
    });

    if (!record) {
      return null;
    }

    if (Date.now() > record.expires_at_ms) {
      await clearSwPat();
      return null;
    }

    return record.token;
  } catch {
    return null;
  }
};

export const clearSwPat = async (): Promise<void> => {
  try {
    const db = await openDatabase();
    await new Promise<void>((resolve, reject) => {
      const tx = db.transaction(IDB_STORE, 'readwrite');
      const store = tx.objectStore(IDB_STORE);
      const delRequest = store.delete(IDB_KEY);

      delRequest.onsuccess = () => resolve();
      delRequest.onerror = () => reject(delRequest.error);
      tx.oncomplete = () => db.close();
    });
  } catch {
    // Graceful error ignore
  }
};
