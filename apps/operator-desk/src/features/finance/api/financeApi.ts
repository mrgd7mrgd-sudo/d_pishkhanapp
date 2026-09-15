import type { FinanceQueryParams, FinanceSummary } from '../types';

const API_PREFIX = '/api/v1';

export class FinanceApiError extends Error {
  public readonly status: number;
  public readonly code?: string | undefined;

  constructor(message: string, status: number, code?: string | undefined) {
    super(message);
    this.name = 'FinanceApiError';
    this.status = status;
    this.code = code;
  }
}

export const financeApi = {
  async fetchFinance(params?: FinanceQueryParams | undefined): Promise<FinanceSummary> {
    const query = new URLSearchParams();
    if (params?.period_start) {
      query.append('period_start', params.period_start);
    }
    if (params?.period_end) {
      query.append('period_end', params.period_end);
    }

    const queryString = query.toString();
    const url = `${API_PREFIX}/desk/finance${queryString ? `?${queryString}` : ''}`;

    const res = await fetch(url, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
      },
    });

    if (!res.ok) {
      let errDetail = `Finance fetch failed with status ${res.status}`;
      let errCode: string | undefined = undefined;

      try {
        const errorJson = (await res.json()) as { detail?: string; code?: string };
        if (errorJson.detail) {
          errDetail = errorJson.detail;
        }
        errCode = errorJson.code;
      } catch {
        // use default message
      }

      throw new FinanceApiError(errDetail, res.status, errCode);
    }

    const json = (await res.json()) as { data: FinanceSummary };
    return json.data;
  },
};
