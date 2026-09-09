import type { ServiceTag } from '@pishkhan/domain';

export type { ServiceTag };

export interface ServiceCategory {
  id: string;
  title: string;
  slug: string;
  icon_name: string | null;
  color: string | null;
  display_order: number;
  services_count?: number | undefined;
}

export interface ServiceRequiredDocument {
  code: string;
  title: string;
  is_mandatory: boolean;
  accepts: string[];
}

export interface ServiceEstimatedDays {
  min: number;
  max: number;
  label: string;
}

export interface ServiceImage {
  avif: string;
  webp: string;
  width: number;
  height: number;
  blurhash?: string | undefined;
}

export interface ServiceCategorySummary {
  id: string;
  title: string;
  color?: string | undefined;
  icon?: string | undefined;
}

export interface ServiceItem {
  id: string;
  title: string;
  slug: string;
  category: ServiceCategorySummary;
  tags: ServiceTag[];
  description: string;
  requirements: string[];
  required_documents: ServiceRequiredDocument[];
  estimated_days: ServiceEstimatedDays;
  fee_rials: number;
  department: string;
  is_popular: boolean;
  is_new: boolean;
  image: ServiceImage;
  requires_in_person: boolean;
  supports_delivery: boolean;
}

export interface ServicesPaginationMeta {
  next_cursor: string | null;
  prev_cursor: string | null;
  per_page: number;
}

export interface ServicesResponse {
  data: ServiceItem[];
  meta: ServicesPaginationMeta;
}

export interface ServiceFilters {
  categoryId?: string | undefined;
  tag?: ServiceTag | undefined;
  search?: string | undefined;
  sort?: 'popular' | 'newest' | 'fee_asc' | 'fee_desc' | undefined;
}

export interface DocumentType {
  code: string;
  title: string;
  description: string | null;
  accepted_mimes: string[];
  max_size_mb: number;
  is_expirable: boolean;
}
