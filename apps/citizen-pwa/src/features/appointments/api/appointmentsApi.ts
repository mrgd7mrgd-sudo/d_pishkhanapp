import type {
  AppointmentItem,
  BookAppointmentPayload,
  OfficeSlotsResponse,
} from '../types';

const API_PREFIX = '/api/v1';

export class AppointmentApiError extends Error {
  public readonly status: number;
  public readonly code?: string | undefined;

  constructor(message: string, status: number, code?: string | undefined) {
    super(message);
    this.name = 'AppointmentApiError';
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

    throw new AppointmentApiError(errDetail, res.status, errCode);
  }

  return (await res.json()) as T;
}

export const appointmentsApi = {
  async fetchAppointments(): Promise<AppointmentItem[]> {
    const url = `${API_PREFIX}/appointments`;
    const json = await request<{ data: AppointmentItem[] }>(url);
    return json.data;
  },

  async fetchOfficeSlots(officeId: string, date?: string | undefined): Promise<OfficeSlotsResponse> {
    const url = `${API_PREFIX}/offices/${officeId}/slots${date ? `?date=${date}` : ''}`;
    const json = await request<{ data: OfficeSlotsResponse }>(url);
    return json.data;
  },

  async bookAppointment(payload: BookAppointmentPayload): Promise<AppointmentItem> {
    const url = `${API_PREFIX}/appointments`;
    const json = await request<{ data: AppointmentItem }>(url, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return json.data;
  },

  async cancelAppointment(id: string, reason?: string | undefined): Promise<AppointmentItem> {
    const url = `${API_PREFIX}/appointments/${id}`;
    const json = await request<{ data: AppointmentItem }>(url, {
      method: 'DELETE',
      body: JSON.stringify({ reason }),
    });
    return json.data;
  },
};
