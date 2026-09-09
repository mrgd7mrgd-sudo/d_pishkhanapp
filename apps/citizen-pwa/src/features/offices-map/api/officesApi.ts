import type { OfficeItem, OfficesResponse, Coordinates } from '../types';

const API_PREFIX = '/api/v1';

export class OfficeApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    public readonly detail: string,
  ) {
    super(detail);
    this.name = 'OfficeApiError';
  }
}

const handleResponse = async <T>(res: Response): Promise<T> => {
  if (!res.ok) {
    let detail = 'خطایی در دریافت اطلاعات دفاتر پیشخوان رخ داد';
    let code = 'OFFICE_FETCH_ERROR';

    try {
      const errorJson = (await res.json()) as { detail?: string; code?: string };
      if (errorJson.detail) detail = errorJson.detail;
      if (errorJson.code) code = errorJson.code;
    } catch {
      // Fallback for non-JSON errors
    }

    throw new OfficeApiError(res.status, code, detail);
  }

  const json = (await res.json()) as { data: T; meta?: unknown };
  if (json.meta !== undefined) {
    return json as unknown as T;
  }
  return json.data;
};

export interface FetchNearbyOfficesParams {
  coords: Coordinates;
  radiusKm?: number | undefined;
  categoryId?: string | undefined;
  limit?: number | undefined;
}

export const officesApi = {
  async getOffices(params: {
    onlyOnline?: boolean | undefined;
    provinceCode?: string | undefined;
    categoryId?: string | undefined;
    cursor?: string | undefined;
    limit?: number | undefined;
  } = {}): Promise<OfficesResponse> {
    const searchParams = new URLSearchParams();
    if (params.onlyOnline) searchParams.set('only_online', 'true');
    if (params.provinceCode) searchParams.set('province_code', params.provinceCode);
    if (params.categoryId) searchParams.set('category_id', params.categoryId);
    if (params.cursor) searchParams.set('cursor', params.cursor);
    if (params.limit) searchParams.set('limit', String(params.limit));

    const qs = searchParams.toString();
    const url = qs ? `${API_PREFIX}/offices?${qs}` : `${API_PREFIX}/offices`;
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    return handleResponse<OfficesResponse>(res);
  },

  async getNearby(params: FetchNearbyOfficesParams): Promise<OfficesResponse> {
    const searchParams = new URLSearchParams({
      lat: String(params.coords.lat),
      lng: String(params.coords.lng),
    });
    if (params.radiusKm !== undefined) searchParams.set('radius_km', String(params.radiusKm));
    if (params.categoryId !== undefined) searchParams.set('category_id', params.categoryId);
    if (params.limit !== undefined) searchParams.set('limit', String(params.limit));

    const url = `${API_PREFIX}/offices/nearby?${searchParams.toString()}`;
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    return handleResponse<OfficesResponse>(res);
  },

  async getOfficeById(id: string): Promise<OfficeItem> {
    const res = await fetch(`${API_PREFIX}/offices/${encodeURIComponent(id)}`, {
      headers: { Accept: 'application/json' },
    });
    return handleResponse<OfficeItem>(res);
  },
};
