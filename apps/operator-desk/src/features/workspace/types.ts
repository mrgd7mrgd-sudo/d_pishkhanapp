import type { CaseStatus, TurnOwner, ReturnReasonCode } from '@pishkhan/domain';

export interface DeskCaseListItem {
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
  } | null;
  current_step: number;
  total_steps: number;
  fee_paid_rials: number;
  created_at: string;
  sla_deadline_at?: string | null;
  available_actions: string[];
}

export interface CaseDocumentItem {
  id: string;
  document_type_code: string;
  title: string;
  status: string;
  reason_code?: string | null;
  version: number;
  quality_warnings?: string[] | null;
  uploaded_at: string;
}

export interface CaseTimelineStepItem {
  id: string;
  step_number: number;
  title: string;
  description?: string | null;
  status: string;
  created_at: string;
}

export interface CaseReturnDetail {
  code: ReturnReasonCode;
  title: string;
  message: string;
  operator_note?: string | null;
  target_document_type_code?: string | null;
  sample_image_url?: string | null;
  returned_at: string;
}

export interface DeskCaseDetail extends DeskCaseListItem {
  last_change_text: string;
  return_reason?: CaseReturnDetail | null;
  sla: {
    current_deadline: string;
    remaining_seconds: number;
    phase: string;
  };
  documents: CaseDocumentItem[];
  timeline: CaseTimelineStepItem[];
  realtime_channel: string;
}

export interface DeskCasesQueryFilter {
  status?: CaseStatus | 'all' | undefined;
  search?: string | undefined;
  cursor?: string | undefined;
  per_page?: number | undefined;
}

export interface ReturnCasePayload {
  reason_code: ReturnReasonCode;
  operator_note?: string | undefined;
  target_document_type_code?: string | undefined;
}

export interface InquiryCasePayload {
  provider: 'shahkar' | 'civil_registry' | 'post';
}

export interface RejectCasePayload {
  reason: string;
}
