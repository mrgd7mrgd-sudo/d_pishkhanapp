import type {
  CourierType,
  DeliveryDocType,
  DeliveryPaymentMethod,
  DeliveryStatus,
} from '@pishkhan/domain';

export type DeliverySubTab = 'new_courier_request' | 'active_deliveries' | 'postal_barcodes';

export interface DeliveryItem {
  id: string;
  case_id: string;
  office_id: string;
  doc_type: DeliveryDocType;
  doc_type_label: string;
  doc_type_name?: string | null;
  doc_serial_number?: string | null;
  destination_address: string;
  destination_postal_code: string;
  destination_zone?: string | null;
  courier_type: CourierType;
  courier_type_label: string;
  delivery_status: DeliveryStatus;
  delivery_status_label: string;
  courier_name?: string | null;
  courier_phone?: string | null;
  courier_plate?: string | null;
  otp_expires_at?: string | null;
  shipping_fee_rials?: number | null;
  payment_method: DeliveryPaymentMethod;
  payment_method_label: string;
  require_old_doc_return: boolean;
  is_sealed_pack: boolean;
  security_note?: string | null;
  tracking_barcode?: string | null;
  dispatched_at?: string | null;
  delivered_at?: string | null;
  created_at: string;
  updated_at: string;
}

export interface ReadyCase {
  id: string;
  tracking_code: string;
  service_title?: string | null;
  citizen_name?: string | null;
  citizen_mobile?: string | null;
  delivery_preference: string;
  address?: string | null;
  postal_code?: string | null;
}

export interface CreateDeliveryPayload {
  case_id: string;
  doc_type: DeliveryDocType;
  doc_type_name?: string | undefined;
  doc_serial_number?: string | undefined;
  destination_address: string;
  destination_postal_code: string;
  destination_zone?: string | undefined;
  courier_type: CourierType;
  shipping_fee_rials?: number | undefined;
  payment_method: DeliveryPaymentMethod;
  require_old_doc_return?: boolean | undefined;
  is_sealed_pack?: boolean | undefined;
  security_note?: string | undefined;
  tracking_barcode?: string | undefined;
}

export interface AssignCourierPayload {
  courier_name: string;
  courier_phone: string;
  courier_plate?: string | undefined;
}

export interface WaybillData {
  delivery_id: string;
  tracking_barcode: string;
  pdf_url?: string | undefined;
  qr_data?: string | undefined;
  office_name?: string | undefined;
  office_phone?: string | undefined;
  office_address?: string | undefined;
  citizen_name?: string | undefined;
  destination_address?: string | undefined;
  destination_postal_code?: string | undefined;
  doc_type_label?: string | undefined;
  doc_serial_number?: string | undefined;
  courier_type_label?: string | undefined;
  payment_method_label?: string | undefined;
  shipping_fee_rials?: number | undefined;
  is_sealed_pack?: boolean | undefined;
  require_old_doc_return?: boolean | undefined;
  created_at?: string | undefined;
}
