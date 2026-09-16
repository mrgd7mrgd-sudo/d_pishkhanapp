export type ProfileSubTab = 'info' | 'services' | 'reception' | 'announcements';

export interface OfficeProfileData {
  id: string;
  code: string;
  name: string;
  manager_name: string;
  is_online: boolean;
  membership_status: string;
  address?: string | null | undefined;
  phone?: string | null | undefined;
  working_hours?: Record<string, string> | null | undefined;
  active_counters: number;
  current_waiting_queue: number;
  rating: number;
  review_count: number;
  sla_score: number;
}

export interface OfficeSpecialtyItem {
  id: string;
  office_id: string;
  title: string;
  is_active: boolean;
}

export interface ServiceCategoryCoverage {
  id: string;
  title: string;
  short_title?: string | null | undefined;
  icon_name?: string | null | undefined;
}

export interface OfficeCoverageItem {
  id: string;
  office_id: string;
  category_id: string;
  is_active: boolean;
  daily_capacity: number;
  category?: ServiceCategoryCoverage | null | undefined;
}

export interface OfficeAnnouncementItem {
  id: string;
  office_id: string;
  title: string;
  content: string;
  priority: 'normal' | 'important' | 'urgent';
  starts_at?: string | null | undefined;
  expires_at?: string | null | undefined;
  is_active: boolean;
}

export interface OfficeOperatorItem {
  id: string;
  username: string;
  full_name: string;
  counter_number: number;
  role: string;
  is_active: boolean;
  last_login_at?: string | null | undefined;
}

export interface FullOfficeProfileResponse {
  office: OfficeProfileData;
  specialties: OfficeSpecialtyItem[];
  coverages: OfficeCoverageItem[];
  announcements: OfficeAnnouncementItem[];
  operators: OfficeOperatorItem[];
  available_categories: ServiceCategoryCoverage[];
}

export interface UpdateOfficeInfoPayload {
  phone?: string | undefined;
  address?: string | undefined;
  active_counters?: number | undefined;
  working_hours?: Record<string, string> | undefined;
}

export interface CreateAnnouncementPayload {
  title: string;
  content: string;
  priority?: 'normal' | 'important' | 'urgent' | undefined;
  starts_at?: string | undefined;
  expires_at?: string | undefined;
}

export interface CreateOperatorPayload {
  username: string;
  full_name: string;
  counter_number: number;
  password: string;
  role?: 'operator' | 'manager' | undefined;
}
