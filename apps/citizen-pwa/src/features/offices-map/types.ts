import type { OfficeMembershipStatus } from '@pishkhan/domain';

export type MapTheme = 'standard-day' | 'neshan' | 'dreamy';

export interface Coordinates {
  lat: number;
  lng: number;
}

export interface CityOption {
  code: string;
  name: string;
  provinceCode: string;
  coords: Coordinates;
}

export interface OfficeWorkingHours {
  label: string;
  is_open_now: boolean;
}

export interface OfficeItem {
  id: string;
  code: string;
  name: string;
  manager_name: string;
  membership_status: OfficeMembershipStatus;
  is_online: boolean;
  rating: number;
  review_count: number;
  medals: string[];
  specialties: string[];
  address: string;
  province_code: string;
  city: string;
  region: string | null;
  coords: Coordinates;
  distance_km: number | null;
  phone: string;
  working_hours: OfficeWorkingHours;
  active_counters: number;
  current_waiting_queue: number;
  estimated_wait_minutes: number;
  supported_category_ids: string[];
  smart_score: number | null;
}

export interface OfficesResponse {
  data: OfficeItem[];
  meta: {
    next_cursor?: string | null | undefined;
    total_estimate?: number | undefined;
    center?: Coordinates | undefined;
    radius_km?: number | undefined;
    count?: number | undefined;
  };
}

export interface MapFilterState {
  searchQuery: string;
  status: OfficeMembershipStatus | 'all';
  minRating: number;
  maxDistanceKm: number;
  theme: MapTheme;
}
