import type { QueryClient } from '@tanstack/react-query';
import { persistQueryClient } from '@tanstack/react-query-persist-client';
import { createAsyncStoragePersister } from '@tanstack/query-async-storage-persister';
import { idbStorage } from './idb-storage';

/**
 * Service Worker Runtime Caching Definitions strictly matching Architecture §4.6
 */
export const SW_RUNTIME_CACHING = [
  // 1. Service Catalog & Categories: StaleWhileRevalidate, 7 days
  {
    urlPattern: /\/api\/v1\/(services|categories).*/i,
    handler: 'StaleWhileRevalidate' as const,
    options: {
      cacheName: 'service-catalog-cache',
      expiration: {
        maxEntries: 100,
        maxAgeSeconds: 7 * 24 * 60 * 60, // 7 days
      },
      cacheableResponse: {
        statuses: [0, 200],
      },
    },
  },
  // 2. Offices Network: StaleWhileRevalidate, 3 days
  {
    urlPattern: /\/api\/v1\/offices.*/i,
    handler: 'StaleWhileRevalidate' as const,
    options: {
      cacheName: 'offices-cache',
      expiration: {
        maxEntries: 100,
        maxAgeSeconds: 3 * 24 * 60 * 60, // 3 days
      },
      cacheableResponse: {
        statuses: [0, 200],
      },
    },
  },
  // 3. Map Tiles (Neshan via proxy or direct upstream): CacheFirst, max 300 tiles, 30 days
  {
    urlPattern: /(\/tiles\/(standard-day|neshan|dreamy)\/.*|https:\/\/api\.neshan\.org\/v.*)/i,
    handler: 'CacheFirst' as const,
    options: {
      cacheName: 'map-tiles-cache',
      expiration: {
        maxEntries: 300,
        maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
      },
      cacheableResponse: {
        statuses: [0, 200],
      },
    },
  },
  // 4. Service Images: CacheFirst, max 60 files, 30 days
  {
    urlPattern: /(\/images\/optimized\/.*|\/assets\/.*\.(avif|webp|png|jpg|jpeg))/i,
    handler: 'CacheFirst' as const,
    options: {
      cacheName: 'service-images-cache',
      expiration: {
        maxEntries: 60,
        maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
      },
      cacheableResponse: {
        statuses: [0, 200],
      },
    },
  },
  // 5. User Cases: NetworkFirst (timeout 3s), 24 hours
  {
    urlPattern: /\/api\/v1\/cases.*/i,
    handler: 'NetworkFirst' as const,
    options: {
      cacheName: 'cases-cache',
      networkTimeoutSeconds: 3,
      expiration: {
        maxEntries: 50,
        maxAgeSeconds: 24 * 60 * 60, // 24 hours
      },
      cacheableResponse: {
        statuses: [0, 200],
      },
    },
  },
  // 6. Vault Metadata: NetworkFirst, 24 hours
  {
    urlPattern: /\/api\/v1\/documents(\?.*)?$/i,
    handler: 'NetworkFirst' as const,
    options: {
      cacheName: 'vault-metadata-cache',
      networkTimeoutSeconds: 3,
      expiration: {
        maxEntries: 50,
        maxAgeSeconds: 24 * 60 * 60, // 24 hours
      },
      cacheableResponse: {
        statuses: [0, 200],
      },
    },
  },
  // 7. Document Contents / Signed URLs: NetworkOnly (Never cache PII / sensitive docs)
  {
    urlPattern: /\/api\/v1\/documents\/.*(download|view|signed-url).*/i,
    handler: 'NetworkOnly' as const,
  },
  // 8. Payments & OTP Auth: NetworkOnly
  {
    urlPattern: /\/api\/v1\/(payments|auth)\/.*/i,
    handler: 'NetworkOnly' as const,
  },
];

/**
 * Persist TanStack QueryClient in IndexedDB with 24-hour retention (§4.6)
 */
export const initQueryClientPersistence = (client: QueryClient): void => {
  if (typeof window === 'undefined') return;

  const asyncPersister = createAsyncStoragePersister({
    storage: idbStorage,
    key: 'PISHKHAN_OFFLINE_QUERIES',
  });

  persistQueryClient({
    queryClient: client,
    persister: asyncPersister,
    maxAge: 1000 * 60 * 60 * 24, // 24 hours
    buster: 'v1.0.0',
  });
};
