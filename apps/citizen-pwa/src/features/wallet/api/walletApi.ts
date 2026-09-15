import type {
  WalletBalance,
  TopupIntentPayload,
  TopupIntentData,
  VerifyTopupPayload,
  VerifyTopupData,
  WalletTransaction,
} from '../types';
import { getAccessToken } from '@/features/auth/model/tokenStorage';

const API_PREFIX = '/api/v1';

export class WalletApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    public readonly detail: string
  ) {
    super(detail);
    this.name = 'WalletApiError';
  }
}

const getHeaders = (includeIdempotency = false): Record<string, string> => {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  const token = getAccessToken();
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  if (includeIdempotency) {
    headers['Idempotency-Key'] =
      typeof crypto !== 'undefined' && crypto.randomUUID
        ? crypto.randomUUID()
        : `idem_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;
  }

  return headers;
};

const handleResponse = async <T>(res: Response): Promise<T> => {
  if (!res.ok) {
    let errorDetail = 'خطایی در انجام عملیات مالی رخ داد';
    let errorCode = 'WALLET_ERROR';

    try {
      const body = (await res.json()) as { message?: string; detail?: string; code?: string };
      if (body.detail) errorDetail = body.detail;
      else if (body.message) errorDetail = body.message;
      if (body.code) errorCode = body.code;
    } catch {
      // Non-JSON fallback
    }

    throw new WalletApiError(res.status, errorCode, errorDetail);
  }

  const json = (await res.json()) as { data: T };
  return json.data;
};

export const walletApi = {
  async fetchBalance(): Promise<WalletBalance> {
    const res = await fetch(`${API_PREFIX}/wallet/balance`, {
      method: 'GET',
      headers: getHeaders(),
    });
    return handleResponse<WalletBalance>(res);
  },

  async createTopupIntent(payload: TopupIntentPayload): Promise<TopupIntentData> {
    const res = await fetch(`${API_PREFIX}/wallet/topup`, {
      method: 'POST',
      headers: getHeaders(true),
      body: JSON.stringify(payload),
    });
    return handleResponse<TopupIntentData>(res);
  },

  async verifyTopup(payload: VerifyTopupPayload): Promise<VerifyTopupData> {
    const res = await fetch(`${API_PREFIX}/wallet/topup/verify`, {
      method: 'POST',
      headers: getHeaders(true),
      body: JSON.stringify(payload),
    });
    return handleResponse<VerifyTopupData>(res);
  },

  async fetchTransactions(): Promise<WalletTransaction[]> {
    try {
      const res = await fetch(`${API_PREFIX}/wallet/transactions`, {
        method: 'GET',
        headers: getHeaders(),
      });
      if (res.ok) {
        return handleResponse<WalletTransaction[]>(res);
      }
    } catch {
      // Fallback
    }

    return [];
  },
};
