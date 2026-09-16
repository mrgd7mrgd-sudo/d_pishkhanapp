import type { DeskReview, ReviewFilter, SlaStatsData } from '../types';

const API_PREFIX = '/api/v1';

export class ReviewApiError extends Error {
  public readonly status: number;
  public readonly code?: string | undefined;

  constructor(message: string, status: number, code?: string | undefined) {
    super(message);
    this.name = 'ReviewApiError';
    this.status = status;
    this.code = code;
  }
}

async function request<T>(url: string, options?: RequestInit): Promise<T> {
  const res = await fetch(url, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...options?.headers,
    },
  });

  if (!res.ok) {
    let errDetail = `درخواست با کد خطای ${res.status} ناموفق بود.`;
    let errCode: string | undefined = undefined;

    try {
      const errorJson = (await res.json()) as { detail?: string; message?: string; code?: string };
      if (errorJson.detail) {
        errDetail = errorJson.detail;
      } else if (errorJson.message) {
        errDetail = errorJson.message;
      }
      errCode = errorJson.code;
    } catch {
      // fallback
    }

    throw new ReviewApiError(errDetail, res.status, errCode);
  }

  return (await res.json()) as T;
}

export const reviewsApi = {
  async fetchReviews(params?: {
    filter?: ReviewFilter | undefined;
    search?: string | undefined;
  }): Promise<DeskReview[]> {
    const query = new URLSearchParams();
    if (params?.filter && params.filter !== 'all') {
      query.set('filter', params.filter);
    }
    if (params?.search) {
      query.set('search', params.search);
    }
    const queryString = query.toString();
    const url = `${API_PREFIX}/desk/reviews${queryString ? `?${queryString}` : ''}`;
    const json = await request<{ data: DeskReview[] }>(url);
    return json.data;
  },

  async replyToReview(id: string, replyText: string): Promise<DeskReview> {
    const url = `${API_PREFIX}/desk/reviews/${id}/reply`;
    const json = await request<{ data: DeskReview }>(url, {
      method: 'POST',
      body: JSON.stringify({ reply: replyText }),
    });
    return json.data;
  },

  async fetchSlaStats(): Promise<SlaStatsData> {
    const url = `${API_PREFIX}/desk/reviews/sla-stats`;
    const json = await request<{ data: SlaStatsData }>(url);
    return json.data;
  },
};
