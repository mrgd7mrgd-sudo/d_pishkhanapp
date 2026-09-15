import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { walletApi } from '../api/walletApi';
import type {
  WalletBalance,
  TopupIntentPayload,
  TopupIntentData,
  VerifyTopupPayload,
  VerifyTopupData,
  WalletTransaction,
} from '../types';

export const WALLET_QUERY_KEYS = {
  all: ['wallet'] as const,
  balance: () => [...WALLET_QUERY_KEYS.all, 'balance'] as const,
  transactions: () => [...WALLET_QUERY_KEYS.all, 'transactions'] as const,
};

export function useWalletBalance() {
  return useQuery<WalletBalance, Error>({
    queryKey: WALLET_QUERY_KEYS.balance(),
    queryFn: () => walletApi.fetchBalance(),
    staleTime: 30_000,
    refetchOnWindowFocus: true,
  });
}

export function useWalletTransactions() {
  return useQuery<WalletTransaction[], Error>({
    queryKey: WALLET_QUERY_KEYS.transactions(),
    queryFn: () => walletApi.fetchTransactions(),
    staleTime: 60_000,
  });
}

export function useTopupIntent() {
  return useMutation<TopupIntentData, Error, TopupIntentPayload>({
    mutationFn: (payload: TopupIntentPayload) => walletApi.createTopupIntent(payload),
  });
}

export function useVerifyTopup() {
  const queryClient = useQueryClient();

  return useMutation<VerifyTopupData, Error, VerifyTopupPayload>({
    mutationFn: (payload: VerifyTopupPayload) => walletApi.verifyTopup(payload),
    onSuccess: () => {
      // Golden Rule §4.10: Invalidate cache upon financial state change
      void queryClient.invalidateQueries({ queryKey: WALLET_QUERY_KEYS.balance() });
      void queryClient.invalidateQueries({ queryKey: WALLET_QUERY_KEYS.transactions() });
    },
  });
}
