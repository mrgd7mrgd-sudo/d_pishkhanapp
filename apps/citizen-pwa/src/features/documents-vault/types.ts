export type VaultCategory = 'identity' | 'education' | 'finance' | 'legal' | 'medical' | 'general';

export interface VaultDocumentAttributeItem {
  label: string;
  value: string;
}

export interface VaultVersionItem {
  id: string;
  version: number;
  file_name: string;
  mime_type: string;
  size_bytes: number;
  created_at: string;
}

export interface VaultDocumentItem {
  id: string;
  title: string;
  category: VaultCategory | string;
  document_type_code?: string | null | undefined;
  doc_number?: string | null | undefined;
  issue_date?: string | null | undefined;
  expiry_date?: string | null | undefined;
  is_verified: boolean;
  attributes: VaultDocumentAttributeItem[];
  latest_version?: VaultVersionItem | null | undefined;
  created_at: string;
  updated_at: string;
}

export interface VaultDocumentDetail extends VaultDocumentItem {
  view_url?: string | null | undefined;
  versions: VaultVersionItem[];
}

export interface VaultUploadPayload {
  title: string;
  category: string;
  document_type_code?: string | undefined;
  doc_number?: string | undefined;
  expiry_date?: string | undefined;
  file: File;
}
