import type { ServiceDetail, OfficeOption, ServiceRequestFormData } from '../types';

const API_PREFIX = '/api/v1';

export class ServiceRequestApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    public readonly detail: string,
  ) {
    super(detail);
    this.name = 'ServiceRequestApiError';
  }
}

const parseError = async (res: Response, defaultMsg: string): Promise<ServiceRequestApiError> => {
  let detail = defaultMsg;
  let code = 'SERVICE_REQUEST_ERROR';
  try {
    const json = (await res.json()) as { detail?: string; code?: string; message?: string };
    if (json.detail) detail = json.detail;
    else if (json.message) detail = json.message;
    if (json.code) code = json.code;
  } catch {
    // Non-JSON response fallback
  }
  return new ServiceRequestApiError(res.status, code, detail);
};

export const serviceRequestApi = {
  async getService(serviceId: string): Promise<ServiceDetail> {
    const res = await fetch(`${API_PREFIX}/services/${serviceId}`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw await parseError(res, 'خطا در دریافت مشخصات خدمت');
    const json = (await res.json()) as { data: ServiceDetail };
    return json.data;
  },

  async getWalletBalance(): Promise<number> {
    try {
      const res = await fetch(`${API_PREFIX}/wallet`, {
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) return 0;
      const json = (await res.json()) as { data: { balance_rials: number } };
      return json.data?.balance_rials ?? 0;
    } catch {
      return 0;
    }
  },

  async getOffices(): Promise<OfficeOption[]> {
    try {
      const res = await fetch(`${API_PREFIX}/offices`, {
        headers: { Accept: 'application/json' },
      });
      if (!res.ok) return [];
      const json = (await res.json()) as { data: OfficeOption[] };
      return json.data ?? [];
    } catch {
      return [];
    }
  },

  async uploadDocument(
    file: File,
    onProgress?: (percent: number) => void
  ): Promise<string> {
    const intentRes = await fetch(`${API_PREFIX}/documents/upload-intent`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        mime_type: file.type || 'application/octet-stream',
        file_size_bytes: file.size,
        file_name: file.name,
      }),
    });

    if (!intentRes.ok) throw await parseError(intentRes, 'خطا در صدور مجوز آپلود');
    const intentJson = (await intentRes.json()) as {
      data: { upload_id: string; upload_url: string };
    };
    const { upload_id, upload_url } = intentJson.data;

    onProgress?.(30);

    const uploadRes = await fetch(upload_url, {
      method: 'PUT',
      headers: { 'Content-Type': file.type || 'application/octet-stream' },
      body: file,
    });

    if (!uploadRes.ok) throw new ServiceRequestApiError(uploadRes.status, 'UPLOAD_FAILED', 'خطا در انتقال فایل مدرک');

    onProgress?.(80);

    const completeRes = await fetch(`${API_PREFIX}/documents/upload-complete`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({ upload_id }),
    });

    if (!completeRes.ok) throw await parseError(completeRes, 'خطا در تایید و ثبت نهایی مدرک');

    onProgress?.(100);
    return upload_id;
  },

  async submitCase(formData: ServiceRequestFormData): Promise<{ id: string; tracking_code: string; status: string }> {
    const idempotencyKey = typeof crypto !== 'undefined' && crypto.randomUUID
      ? crypto.randomUUID()
      : `idemp-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;

    const docsPayload = Object.values(formData.documents).map((doc) => ({
      document_type_code: doc.docTypeCode,
      upload_id: doc.uploadId,
    }));

    const res = await fetch(`${API_PREFIX}/cases`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'Idempotency-Key': idempotencyKey,
      },
      body: JSON.stringify({
        service_id: formData.serviceId,
        dispatch_mode: formData.dispatchMode,
        office_id: formData.dispatchMode === 'manual' ? formData.officeId : undefined,
        payment_method: formData.paymentMethod,
        delivery_preference: formData.deliveryPreference,
        commitment_signed: formData.commitmentSigned,
        documents: docsPayload,
      }),
    });

    if (!res.ok) throw await parseError(res, 'خطا در ثبت پرونده');
    const json = (await res.json()) as { data: { id: string; tracking_code: string; status: string } };
    return json.data;
  },
};
