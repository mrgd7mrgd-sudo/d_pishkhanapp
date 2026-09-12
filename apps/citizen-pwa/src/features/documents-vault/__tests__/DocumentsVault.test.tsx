import React from 'react';
import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { axe } from 'vitest-axe';
import { DocumentsVaultView } from '../ui/DocumentsVaultView';
import { VaultDocCard } from '../ui/VaultDocCard';
import { VaultCategoryFilter } from '../ui/VaultCategoryFilter';
import { VaultDocViewModal } from '../ui/VaultDocViewModal';
import { VaultUploadModal } from '../ui/VaultUploadModal';
import { SW_RUNTIME_CACHING } from '../../../shared/pwa/sw-strategies';
import type { VaultDocumentItem, VaultDocumentDetail } from '../types';

vi.mock('react-i18next', () => ({
  useTranslation: () => ({
    t: (key: string) => {
      const dict: Record<string, string> = {
        'vault.page_title': 'مخزن امن مدارک من',
        'vault.page_subtitle': 'مدیریت اسناد شخصی',
        'vault.add_doc_btn': 'افزودن مدرک جدید',
        'vault.categories_aria_label': 'دسته‌بندی مدارک مخزن',
        'vault.cat_all': 'همه مدارک',
        'vault.cat_identity': 'هویتی',
        'vault.cat_education': 'تحصیلی',
        'vault.empty_title': 'مدرکی در این دسته یافت نشد',
        'vault.empty_desc': 'برای سهولت در ثبت خدمات آتی مدارک خود را بارگذاری نمایید.',
        'vault.verified_badge': 'تأییدشده',
        'vault.unverified_badge': 'در انتظار استعلام',
        'vault.expired_badge': 'منقضی شده',
        'vault.near_expiry_badge': 'نزدیک به انقضا',
        'vault.doc_number_label': 'شماره سند',
        'vault.view_btn': 'مشاهده سند',
        'vault.delete_btn': 'حذف',
        'vault.view_modal_title': 'مشاهده امن مدرک',
        'vault.signed_url_notice': 'پیوند مشاهده این مدرک موقت و ۶۰ ثانیه‌ای است.',
        'vault.offline_view_title': 'عدم دسترسی در حالت آفلاین',
        'vault.offline_view_desc': 'محتوای اسناد هویتی در حافظه دستگاه ذخیره نمی‌شود.',
        'vault.upload_modal_title': 'بارگذاری مدرک در مخزن امن',
        'vault.input_title_placeholder': 'عنوان مدرک',
        'vault.input_title_label': 'عنوان مدرک',
        'vault.input_doc_number_placeholder': 'شماره سند',
        'vault.input_doc_number_label': 'شماره سند',
        'vault.category_select_label': 'دسته‌بندی مدرک',
        'vault.dropzone_label': 'انتخاب فایل مدرک',
        'vault.dropzone_desc': 'فرمت‌های مجاز JPG یا PDF',
        'vault.submit_upload': 'بارگذاری و ذخیره در مخزن',
        'vault.upload_missing_fields': 'لطفاً عنوان مدرک و فایل را انتخاب فرمایید.',
        'common.close': 'بستن',
        'common.cancel': 'انصراف',
      };
      return dict[key] ?? key;
    },
  }),
}));

describe('Documents Vault Slice (§4.1, §4.4, §4.6, TASK-064, TASK-064-T)', () => {
  const originalFetch = globalThis.fetch;

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    globalThis.fetch = originalFetch;
  });

  const mockDocuments: VaultDocumentItem[] = [
    {
      id: 'doc-1',
      title: 'کارت ملی هوشمند',
      category: 'identity',
      doc_number: '0012345678',
      expiry_date: '2030-01-01',
      is_verified: true,
      attributes: [{ label: 'شماره ملی', value: '0012345678' }],
      latest_version: {
        id: 'ver-1',
        version: 1,
        file_name: 'national_card.jpg',
        mime_type: 'image/jpeg',
        size_bytes: 153600,
        created_at: '2026-09-10T10:00:00Z',
      },
      created_at: '2026-09-10T10:00:00Z',
      updated_at: '2026-09-10T10:00:00Z',
    },
    {
      id: 'doc-2',
      title: 'گذرنامه بین‌المللی',
      category: 'identity',
      doc_number: 'P98765432',
      expiry_date: '2024-01-01', // Expired
      is_verified: false,
      attributes: [],
      latest_version: {
        id: 'ver-2',
        version: 2,
        file_name: 'passport.jpg',
        mime_type: 'image/jpeg',
        size_bytes: 204800,
        created_at: '2026-09-11T12:00:00Z',
      },
      created_at: '2026-09-11T12:00:00Z',
      updated_at: '2026-09-11T12:00:00Z',
    },
  ];

  it('renders vault documents list with category filtering', async () => {
    globalThis.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({ data: mockDocuments }),
    } as Response);

    render(
      <MemoryRouter>
        <DocumentsVaultView />
      </MemoryRouter>
    );

    await waitFor(() => {
      expect(screen.getByText('کارت ملی هوشمند')).toBeInTheDocument();
      expect(screen.getByText('گذرنامه بین‌المللی')).toBeInTheDocument();
      expect(screen.getByText('منقضی شده')).toBeInTheDocument();
    });
  });

  it('verifies NetworkOnly SW caching rule for document contents (§4.6)', () => {
    const docDownloadRule = SW_RUNTIME_CACHING.find((rule) =>
      rule.urlPattern.test('/api/v1/documents/ver-1/view') ||
      rule.urlPattern.test('/api/v1/documents/ver-1/download') ||
      rule.urlPattern.test('/api/v1/documents/signed-url')
    );

    expect(docDownloadRule).toBeDefined();
    expect(docDownloadRule?.handler).toBe('NetworkOnly');
  });

  it('shows appropriate offline warning when viewing a document while offline', () => {
    const docDetail: VaultDocumentDetail = {
      ...mockDocuments[0]!,
      view_url: 'https://minio.internal/vault/signed?expires=60',
      versions: [],
    };

    render(
      <VaultDocViewModal
        document={docDetail}
        onClose={vi.fn()}
        isOnline={false}
      />
    );

    expect(screen.getByText('عدم دسترسی در حالت آفلاین')).toBeInTheDocument();
    expect(screen.getByText(/محتوای اسناد هویتی در حافظه دستگاه ذخیره نمی‌شود/)).toBeInTheDocument();
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });

  it('renders signed url and document image when online', () => {
    const docDetail: VaultDocumentDetail = {
      ...mockDocuments[0]!,
      view_url: 'https://minio.internal/vault/signed?expires=60',
      versions: [],
    };

    render(
      <VaultDocViewModal
        document={docDetail}
        onClose={vi.fn()}
        isOnline={true}
      />
    );

    expect(screen.getByAltText('کارت ملی هوشمند')).toBeInTheDocument();
    expect(screen.getByText(/پیوند مشاهده این مدرک موقت و ۶۰ ثانیه‌ای است/)).toBeInTheDocument();
  });

  it('validates upload form and handles successful submission', async () => {
    const handleUpload = vi.fn().mockResolvedValue(undefined);
    const handleClose = vi.fn();

    render(
      <VaultUploadModal
        isOpen={true}
        onClose={handleClose}
        onUpload={handleUpload}
      />
    );

    const submitBtn = screen.getByRole('button', { name: 'بارگذاری و ذخیره در مخزن' });
    fireEvent.click(submitBtn);

    expect(screen.getByText('لطفاً عنوان مدرک و فایل را انتخاب فرمایید.')).toBeInTheDocument();

    const titleInput = screen.getByRole('textbox', { name: 'عنوان مدرک' });
    fireEvent.change(titleInput, { target: { value: 'کارت پایان خدمت' } });

    const fileInput = screen.getByTestId('file-dropzone-input');
    const dummyFile = new File(['hello'], 'service.jpg', { type: 'image/jpeg' });
    fireEvent.change(fileInput, { target: { files: [dummyFile] } });

    fireEvent.click(submitBtn);

    await waitFor(() => {
      expect(handleUpload).toHaveBeenCalled();
      expect(handleClose).toHaveBeenCalled();
    });
  });

  it('passes axe accessibility checks for VaultDocCard', async () => {
    const { container } = render(
      <VaultDocCard
        item={mockDocuments[0]!}
        onView={vi.fn()}
        onDelete={vi.fn()}
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('passes axe accessibility checks for VaultCategoryFilter', async () => {
    const { container } = render(
      <VaultCategoryFilter
        activeCategory=""
        onSelectCategory={vi.fn()}
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
