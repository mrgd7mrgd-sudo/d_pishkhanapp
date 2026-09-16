import type {
  AssignCourierPayload,
  CreateDeliveryPayload,
  DeliveryItem,
  ReadyCase,
  WaybillData,
} from '../types';

const API_PREFIX = '/api/v1';

export class DeliveryApiError extends Error {
  public readonly status: number;
  public readonly code?: string | undefined;

  constructor(message: string, status: number, code?: string | undefined) {
    super(message);
    this.name = 'DeliveryApiError';
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

    throw new DeliveryApiError(errDetail, res.status, errCode);
  }

  return (await res.json()) as T;
}

export const deliveryApi = {
  async fetchDeliveries(params?: {
    status?: string;
    courier_type?: string;
    search?: string;
  }): Promise<DeliveryItem[]> {
    const query = new URLSearchParams();
    if (params?.status && params.status !== 'all') {
      query.append('status', params.status);
    }
    if (params?.courier_type) {
      query.append('courier_type', params.courier_type);
    }
    if (params?.search) {
      query.append('search', params.search);
    }

    const qs = query.toString();
    const url = `${API_PREFIX}/deliveries${qs ? `?${qs}` : ''}`;
    const res = await request<{ data: DeliveryItem[] }>(url, { method: 'GET' });
    return res.data;
  },

  async fetchReadyCases(): Promise<ReadyCase[]> {
    const url = `${API_PREFIX}/deliveries/ready-cases`;
    const res = await request<{ data: ReadyCase[] }>(url, { method: 'GET' });
    return res.data;
  },

  async createDelivery(payload: CreateDeliveryPayload): Promise<DeliveryItem> {
    const url = `${API_PREFIX}/deliveries`;
    const res = await request<{ data: DeliveryItem }>(url, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  },

  async assignCourier(id: string, payload: AssignCourierPayload): Promise<DeliveryItem> {
    const url = `${API_PREFIX}/deliveries/${id}/assign-courier`;
    const res = await request<{ data: DeliveryItem }>(url, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  },

  async markInTransit(id: string, payload?: { location?: string; note?: string }): Promise<DeliveryItem> {
    const url = `${API_PREFIX}/deliveries/${id}/in-transit`;
    const res = await request<{ data: DeliveryItem }>(url, {
      method: 'POST',
      body: JSON.stringify(payload ?? {}),
    });
    return res.data;
  },

  async confirmDelivery(id: string, otp: string): Promise<DeliveryItem> {
    const url = `${API_PREFIX}/deliveries/${id}/confirm`;
    const res = await request<{ data: DeliveryItem }>(url, {
      method: 'POST',
      body: JSON.stringify({ otp }),
    });
    return res.data;
  },

  async markFailed(id: string, reason: string, location?: string): Promise<DeliveryItem> {
    const url = `${API_PREFIX}/deliveries/${id}/fail`;
    const res = await request<{ data: DeliveryItem }>(url, {
      method: 'POST',
      body: JSON.stringify({ reason, location }),
    });
    return res.data;
  },

  async fetchWaybill(id: string): Promise<WaybillData> {
    const url = `${API_PREFIX}/deliveries/${id}/waybill`;
    const res = await request<{ data: WaybillData }>(url, { method: 'GET' });
    return res.data;
  },
};
