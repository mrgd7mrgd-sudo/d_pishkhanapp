import type { VaultDocumentItem, VaultDocumentDetail } from '../types';

const API_PREFIX = '/api/v1';

export class VaultApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    public readonly detail: string,
  ) {
    super(detail);
    this.name = 'VaultApiError';
  }
}

const parseError = async (res: Response, defaultMsg: string): Promise<VaultApiError> => {
  let detail = defaultMsg;
  let code = 'VAULT_ERROR';
  try {
    const json = (await res.json()) as { detail?: string; code?: string; message?: string };
    if (json.detail) detail = json.detail;
    else if (json.message) detail = json.message;
    if (json.code) code = json.code;
  } catch {
    // Non-JSON fallback
  }
  return new VaultApiError(res.status, code, detail);
};

export const vaultApi = {
  async getDocuments(category?: string): Promise<VaultDocumentItem[]> {
    const query = category ? `?category=${encodeURIComponent(category)}` : '';
    const res = await fetch(`${API_PREFIX}/vault${query}`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw await parseError(res, 'خطا در دریافت لیست مدارک');
    const json = (await res.json()) as { data: VaultDocumentItem[] };
    return json.data ?? [];
  },

  async getDocumentById(id: string): Promise<VaultDocumentDetail> {
    const res = await fetch(`${API_PREFIX}/vault/${id}`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw await parseError(res, 'مدرک یافت نشد');
    const json = (await res.json()) as { data: VaultDocumentDetail };
    return json.data;
  },

  async uploadDocument(formData: FormData): Promise<VaultDocumentItem> {
    const res = await fetch(`${API_PREFIX}/vault`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: formData,
    });
    if (!res.ok) throw await parseError(res, 'خطا در آپلود مدرک در مخزن');
    const json = (await res.json()) as { data: VaultDocumentItem };
    return json.data;
  },

  async deleteDocument(id: string): Promise<void> {
    const res = await fetch(`${API_PREFIX}/vault/${id}`, {
      method: 'DELETE',
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw await parseError(res, 'خطا در حذف مدرک');
  },
};
