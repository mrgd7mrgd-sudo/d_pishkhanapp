import type {
  FullOfficeProfileResponse,
  OfficeSpecialtyItem,
  OfficeCoverageItem,
  OfficeAnnouncementItem,
  UpdateOfficeInfoPayload,
  CreateAnnouncementPayload,
  CreateOperatorPayload,
  OfficeOperatorItem,
} from '../types';

const API_PREFIX = '/api/v1';

export class OfficeProfileApiError extends Error {
  public readonly status: number;
  public readonly code?: string | undefined;

  constructor(message: string, status: number, code?: string | undefined) {
    super(message);
    this.name = 'OfficeProfileApiError';
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

    throw new OfficeProfileApiError(errDetail, res.status, errCode);
  }

  return (await res.json()) as T;
}

export const officeProfileApi = {
  async fetchProfile(): Promise<FullOfficeProfileResponse> {
    const url = `${API_PREFIX}/desk/profile`;
    const json = await request<{ data: FullOfficeProfileResponse }>(url);
    return json.data;
  },

  async updateInfo(payload: UpdateOfficeInfoPayload): Promise<void> {
    const url = `${API_PREFIX}/desk/profile/info`;
    await request(url, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
  },

  async updateSpecialties(specialties: string[]): Promise<OfficeSpecialtyItem[]> {
    const url = `${API_PREFIX}/desk/profile/specialties`;
    const json = await request<{ data: OfficeSpecialtyItem[] }>(url, {
      method: 'PUT',
      body: JSON.stringify({ specialties }),
    });
    return json.data;
  },

  async updateCoverages(
    coverages: Array<{ category_id: string; is_active: boolean; daily_capacity?: number | undefined }>
  ): Promise<OfficeCoverageItem[]> {
    const url = `${API_PREFIX}/desk/profile/coverages`;
    const json = await request<{ data: OfficeCoverageItem[] }>(url, {
      method: 'PUT',
      body: JSON.stringify({ coverages }),
    });
    return json.data;
  },

  async createAnnouncement(payload: CreateAnnouncementPayload): Promise<OfficeAnnouncementItem> {
    const url = `${API_PREFIX}/desk/profile/announcements`;
    const json = await request<{ data: OfficeAnnouncementItem }>(url, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return json.data;
  },

  async deleteAnnouncement(id: string): Promise<void> {
    const url = `${API_PREFIX}/desk/profile/announcements/${id}`;
    await request(url, {
      method: 'DELETE',
    });
  },

  async createOperator(payload: CreateOperatorPayload): Promise<OfficeOperatorItem> {
    const url = `${API_PREFIX}/desk/profile/operators`;
    const json = await request<{ data: OfficeOperatorItem }>(url, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return json.data;
  },

  async toggleOperator(id: string): Promise<{ is_active: boolean }> {
    const url = `${API_PREFIX}/desk/profile/operators/${id}/toggle`;
    const json = await request<{ data: { is_active: boolean } }>(url, {
      method: 'POST',
    });
    return json.data;
  },
};
