import type { CaseStatus, TurnOwner, TimelineStepStatus, ReturnReasonCode } from '@pishkhan/domain';

export interface CaseSummary {
  id: string;
  tracking_code: string;
  status: CaseStatus;
  turn_owner: TurnOwner;
  turn_owner_label: string;
  service: {
    id: string;
    title: string;
    tag: string;
  };
  assigned_office?: {
    id: string;
    name: string;
  } | null | undefined;
  current_step: number;
  total_steps: number;
  fee_paid_rials: number;
  created_at: string;
  sla_deadline_at?: string | null | undefined;
  available_actions: string[];
}

export interface CaseDocumentItem {
  id: string;
  document_type_code: string;
  title: string;
  status: string;
  reason_code?: string | null | undefined;
  version: number;
  quality_warnings?: string[] | null | undefined;
  uploaded_at: string;
}

export interface CaseTimelineStepItem {
  id: string;
  title: string;
  description?: string | null | undefined;
  status: TimelineStepStatus;
  turn_owner: TurnOwner;
  turn_owner_label?: string | undefined;
  occurred_at: string;
  office_note?: string | null | undefined;
  duration_actual_minutes?: number | null | undefined;
  duration_typical_minutes?: number | null | undefined;
}

export interface CaseReturnReasonInfo {
  code: ReturnReasonCode | string;
  title: string;
  message: string;
  operator_note?: string | null | undefined;
  target_document_type_code?: string | null | undefined;
  sample_image_url?: string | null | undefined;
  returned_at: string;
}

export interface CaseSlaInfo {
  current_deadline: string;
  remaining_seconds: number;
  phase: string;
}

export interface CaseDetail {
  id: string;
  tracking_code: string;
  status: CaseStatus;
  turn_owner: TurnOwner;
  turn_owner_label: string;
  last_change_text?: string | null | undefined;
  service: {
    id: string;
    title: string;
    category?: string | undefined;
    tag: string;
  };
  assigned_office?: {
    id: string;
    name: string;
    phone?: string | undefined;
    rating?: number | undefined;
    coords?: { lat: number; lng: number } | null | undefined;
  } | null | undefined;
  current_step: number;
  total_steps: number;
  return_reason?: CaseReturnReasonInfo | null | undefined;
  sla?: CaseSlaInfo | null | undefined;
  documents: CaseDocumentItem[];
  timeline: CaseTimelineStepItem[];
  available_actions: string[];
  realtime_channel?: string | undefined;
}
