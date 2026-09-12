import Dexie, { type Table } from 'dexie';

/**
 * OutboxItem model strictly following Architecture §4.6
 */
export interface OutboxItem {
  id: string; // UUID v7 — exactly acts as Idempotency-Key
  createdAt: number;
  endpoint: string;
  method: 'POST' | 'PATCH' | 'PUT';
  body: unknown;
  files?: { name: string; blob: Blob }[] | undefined;
  status: 'pending' | 'syncing' | 'failed';
  attempts: number;
  lastError?: string | undefined;
}

export class PishkhanOutboxDatabase extends Dexie {
  outbox!: Table<OutboxItem, string>;

  constructor() {
    super('pishkhan-outbox');
    this.version(1).stores({
      outbox: 'id, status, createdAt',
    });
  }
}

export const outboxDb = new PishkhanOutboxDatabase();
