export type ReviewSubTab = 'feedback' | 'sla_quality';

export type ReviewFilter = 'all' | '5star' | 'withReply' | 'needReply';

export interface ManagerReplyInfo {
  text: string;
  date: string;
}

export interface DeskReview {
  id: string;
  office_id: string;
  office_name?: string | null | undefined;
  citizen_id: string;
  citizen_name?: string | null | undefined;
  case_id: string;
  service_title?: string | null | undefined;
  rating: number;
  comment: string;
  tags: string[];
  likes: number;
  is_verified: boolean;
  is_verified_citizen: boolean;
  manager_reply?: string | null | undefined;
  manager_replied_at?: string | null | undefined;
  manager_reply_info?: ManagerReplyInfo | null | undefined;
  created_at: string;
}

export interface SlaTrendPoint {
  date: string;
  breaches_count: number;
  penalties: number;
}

export interface SlaBreachCategory {
  event_type: string;
  count: number;
  total_penalty: number;
}

export interface SlaStatsData {
  current_score: number;
  window_days: number;
  trend: SlaTrendPoint[];
  breakdown: SlaBreachCategory[];
}
