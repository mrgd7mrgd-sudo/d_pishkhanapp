export type DeliveryPreference = 'in_person' | 'courier' | 'post';

export type ServiceRequestStep = 'info' | 'dispatch_type' | 'payment' | 'searching' | 'assigned';

export type DispatchMode = 'auto' | 'manual';
export type PaymentMethod = 'wallet' | 'gateway';

export interface ServiceDetail {
  id: string;
  title: string;
  slug: string;
  description: string;
  fee_rials: number;
  sla_hours: number;
  required_docs?: Array<{
    document_type_code: string;
    title: string;
    is_mandatory: boolean;
  }> | undefined;
}

export interface UploadedDocState {
  docTypeCode: string;
  docTitle: string;
  uploadId: string;
  fileName: string;
  isUploading?: boolean | undefined;
  uploadProgress?: number | undefined;
  error?: string | undefined;
}

export interface OfficeOption {
  id: string;
  code: string;
  name: string;
  rating: number;
  distance_km?: number | undefined;
}

export interface ServiceRequestFormData {
  serviceId: string;
  dispatchMode: DispatchMode;
  officeId?: string | undefined;
  paymentMethod: PaymentMethod;
  deliveryPreference: DeliveryPreference;
  commitmentSigned: boolean;
  documents: Record<string, UploadedDocState>;
  createdCase?: {
    id: string;
    trackingCode: string;
    status: string;
  } | undefined;
}
