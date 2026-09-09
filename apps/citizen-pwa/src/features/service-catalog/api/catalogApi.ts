import type {
  ServiceCategory,
  ServiceItem,
  ServicesResponse,
  DocumentType,
  ServiceTag,
} from '../types';

const API_PREFIX = '/api/v1';

export class CatalogApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    public readonly detail: string,
  ) {
    super(detail);
    this.name = 'CatalogApiError';
  }
}

const handleResponse = async <T>(res: Response): Promise<T> => {
  if (!res.ok) {
    let detail = 'خطایی در دریافت اطلاعات کاتالوگ رخ داد';
    let code = 'CATALOG_FETCH_ERROR';

    try {
      const errorJson = (await res.json()) as { detail?: string; code?: string };
      if (errorJson.detail) detail = errorJson.detail;
      if (errorJson.code) code = errorJson.code;
    } catch {
      // Fallback for non-JSON errors
    }

    throw new CatalogApiError(res.status, code, detail);
  }

  const json = (await res.json()) as { data: T; meta?: unknown };
  // If response has meta (like ServicesResponse), return full object
  if (json.meta !== undefined) {
    return json as unknown as T;
  }
  return json.data;
};

export interface FetchServicesParams {
  category_id?: string | undefined;
  tag?: ServiceTag | undefined;
  search?: string | undefined;
  sort?: string | undefined;
  cursor?: string | undefined;
  per_page?: number | undefined;
}

export const catalogApi = {
  async getCategories(): Promise<ServiceCategory[]> {
    const res = await fetch(`${API_PREFIX}/categories`, {
      headers: { Accept: 'application/json' },
    });
    return handleResponse<ServiceCategory[]>(res);
  },

  async getServices(params: FetchServicesParams = {}): Promise<ServicesResponse> {
    const searchParams = new URLSearchParams();
    if (params.category_id) searchParams.set('category_id', params.category_id);
    if (params.tag) searchParams.set('tag', params.tag);
    if (params.search) searchParams.set('search', params.search);
    if (params.sort) searchParams.set('sort', params.sort);
    if (params.cursor) searchParams.set('cursor', params.cursor);
    if (params.per_page) searchParams.set('per_page', String(params.per_page));

    const qs = searchParams.toString();
    const url = qs ? `${API_PREFIX}/services?${qs}` : `${API_PREFIX}/services`;

    const res = await fetch(url, {
      headers: { Accept: 'application/json' },
    });
    return handleResponse<ServicesResponse>(res);
  },

  async getServiceBySlug(slug: string): Promise<ServiceItem> {
    const res = await fetch(`${API_PREFIX}/services/${encodeURIComponent(slug)}`, {
      headers: { Accept: 'application/json' },
    });
    return handleResponse<ServiceItem>(res);
  },

  async getDocumentTypes(): Promise<DocumentType[]> {
    const res = await fetch(`${API_PREFIX}/document-types`, {
      headers: { Accept: 'application/json' },
    });
    return handleResponse<DocumentType[]>(res);
  },
};
