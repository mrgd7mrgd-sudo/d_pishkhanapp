import {
  DeskCaseListItem,
  DeskCaseDetail,
  DeskCasesQueryFilter,
  ReturnCasePayload,
  InquiryCasePayload,
  RejectCasePayload,
} from '../types';

const API_PREFIX = '/api/v1';

export const workspaceApi = {
  async fetchCases(
    filter: DeskCasesQueryFilter = {}
  ): Promise<{ data: DeskCaseListItem[]; meta: { next_cursor?: string | null; per_page: number } }> {
    const params = new URLSearchParams();
    if (filter.status && filter.status !== 'all') {
      params.append('status', filter.status);
    }
    if (filter.search) {
      params.append('search', filter.search);
    }
    if (filter.cursor) {
      params.append('cursor', filter.cursor);
    }
    if (filter.per_page) {
      params.append('per_page', String(filter.per_page));
    }

    const res = await fetch(`${API_PREFIX}/desk/cases?${params.toString()}`, {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });

    if (!res.ok) {
      throw new Error(`خطا در دریافت لیست پرونده‌ها: ${res.status}`);
    }

    return (await res.json()) as {
      data: DeskCaseListItem[];
      meta: { next_cursor?: string | null; per_page: number };
    };
  },

  async fetchCaseDetail(caseId: string): Promise<DeskCaseDetail> {
    const res = await fetch(`${API_PREFIX}/desk/cases/${caseId}`, {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });

    if (!res.ok) {
      throw new Error(`پرونده با شناسه ${caseId} یافت نشد.`);
    }

    const json = (await res.json()) as { data: DeskCaseDetail };
    return json.data;
  },

  async startReview(caseId: string): Promise<DeskCaseDetail> {
    const res = await fetch(`${API_PREFIX}/cases/${caseId}/review`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    });

    if (!res.ok) {
      const err = (await res.json().catch(() => ({}))) as { detail?: string };
      throw new Error(err.detail || 'خطا در شروع بررسی پرونده.');
    }

    const json = (await res.json()) as { data: DeskCaseDetail };
    return json.data;
  },

  async returnCase(caseId: string, payload: ReturnCasePayload): Promise<DeskCaseDetail> {
    const res = await fetch(`${API_PREFIX}/cases/${caseId}/return`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      const err = (await res.json().catch(() => ({}))) as { detail?: string };
      throw new Error(err.detail || 'خطا در بازگرداندن پرونده برای اصلاح.');
    }

    const json = (await res.json()) as { data: DeskCaseDetail };
    return json.data;
  },

  async requestInquiry(caseId: string, payload: InquiryCasePayload): Promise<DeskCaseDetail> {
    const res = await fetch(`${API_PREFIX}/cases/${caseId}/inquiry`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      const err = (await res.json().catch(() => ({}))) as { detail?: string };
      throw new Error(err.detail || 'خطا در ثبت درخواست استعلام دولتی.');
    }

    const json = (await res.json()) as { data: DeskCaseDetail };
    return json.data;
  },

  async completeCase(caseId: string): Promise<DeskCaseDetail> {
    const res = await fetch(`${API_PREFIX}/cases/${caseId}/complete`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    });

    if (!res.ok) {
      const err = (await res.json().catch(() => ({}))) as { detail?: string };
      throw new Error(err.detail || 'خطا در تکمیل و تأیید نهایی پرونده.');
    }

    const json = (await res.json()) as { data: DeskCaseDetail };
    return json.data;
  },

  async rejectCase(caseId: string, payload: RejectCasePayload): Promise<DeskCaseDetail> {
    const res = await fetch(`${API_PREFIX}/cases/${caseId}/reject`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      const err = (await res.json().catch(() => ({}))) as { detail?: string };
      throw new Error(err.detail || 'خطا در رد پرونده (فقط مدیر دفتر مجاز است).');
    }

    const json = (await res.json()) as { data: DeskCaseDetail };
    return json.data;
  },

  async fetchDocumentSignedUrl(documentId: string): Promise<string> {
    const res = await fetch(`${API_PREFIX}/documents/${documentId}/url`, {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });

    if (!res.ok) {
      throw new Error('خطا در دریافت نشانی دسترسی امن به سند.');
    }

    const json = (await res.json()) as { url?: string; download_url?: string };
    return json.url || json.download_url || '';
  },
};
