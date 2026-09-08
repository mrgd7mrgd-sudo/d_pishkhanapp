import { QueryClient } from '@tanstack/react-query';

/**
 * Operator Desk QueryClient (Architecture §4.3 & §4.4)
 * Unlike citizen PWA, desktop desk uses networkMode: 'online'
 * because operator operations are directly connected to office broadband network.
 */
export const deskQueryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 30, // 30 seconds for fast reactive operator updates
      gcTime: 1000 * 60 * 60, // 1 hour
      retry: 1,
      refetchOnWindowFocus: true,
      refetchOnReconnect: true,
      networkMode: 'online', // ⭐ Desk is strictly online per §4.4
    },
    mutations: {
      networkMode: 'online',
    },
  },
});
