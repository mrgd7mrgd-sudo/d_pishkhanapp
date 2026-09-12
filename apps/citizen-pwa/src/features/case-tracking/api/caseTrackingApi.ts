import type { CaseSummary, CaseDetail } from '../types';

const API_PREFIX = '/api/v1';

export class CaseTrackingApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    public readonly detail: string,
  ) {
    super(detail);
    this.name = 'CaseTrackingApiError';
  }
}

const parseError = async (res: Response, defaultMsg: string): Promise<CaseTrackingApiError> => {
  let detail = defaultMsg;
  let code = 'CASE_TRACKING_ERROR';
  try {
    const json = (await res.json()) as { detail?: string; code?: string; message?: string };
    if (json.detail) detail = json.detail;
    else if (json.message) detail = json.message;
    if (json.code) code = json.code;
  } catch {
    // Non-JSON response fallback
  }
  return new CaseTrackingApiError(res.status, code, detail);
};

export const caseTrackingApi = {
  async getCases(): Promise<CaseSummary[]> {
    const res = await fetch(`${API_PREFIX}/cases`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw await parseError(res, 'خطا در دریافت لیست پرونده‌ها');
    const json = (await res.json()) as { data: CaseSummary[] };
    return json.data ?? [];
  },

  async getCaseByTrackingCode(trackingCode: string): Promise<CaseDetail> {
    const res = await fetch(`${API_PREFIX}/cases/${trackingCode}`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw await parseError(res, 'پرونده با این کد رهگیری یافت نشد');
    const json = (await res.json()) as { data: CaseDetail };
    return json.data;
  },

  async cancelCase(trackingCode: string, reason?: string): Promise<void> {
    const res = await fetch(`${API_PREFIX}/cases/${trackingCode}/cancel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({ reason }),
    });
    if (!res.ok) throw await parseError(res, 'خطا در لغو پرونده');
  },
};
