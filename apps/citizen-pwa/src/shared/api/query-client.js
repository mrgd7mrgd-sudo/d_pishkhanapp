import { QueryClient } from '@tanstack/react-query';
/**
 * QueryClient configured according to Architecture §4.3:
 * - gcTime: 24h for persistent caching on mobile PWA
 * - staleTime: 5m default
 * - networkMode: 'offlineFirst' for seamless offline capability
 * - retryDelay: exponential backoff up to 30s
 * - refetchOnWindowFocus: false (traffic savings on mobile)
 */
export const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60 * 5, // 5 minutes
            gcTime: 1000 * 60 * 60 * 24, // 24 hours
            retry: (failureCount, error) => {
                // Don't retry 4xx errors
                if (typeof error === 'object' && error !== null && 'status' in error) {
                    const status = Number(error.status);
                    if (status >= 400 && status < 500)
                        return false;
                }
                return failureCount < 3;
            },
            retryDelay: (i) => Math.min(1000 * 2 ** i, 30_000),
            refetchOnWindowFocus: false,
            refetchOnReconnect: true,
            networkMode: 'offlineFirst',
        },
        mutations: {
            networkMode: 'offlineFirst',
            retry: 2,
        },
    },
});
