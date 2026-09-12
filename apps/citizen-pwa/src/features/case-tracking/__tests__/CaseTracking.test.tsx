import React from 'react';
import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import { axe } from 'vitest-axe';
import type { CaseStatus } from '@pishkhan/domain';
import { CaseListPage } from '../ui/CaseListPage';
import { CaseDetailPage } from '../ui/CaseDetailPage';
import { CaseActionsPanel } from '../ui/CaseActionsPanel';
import { CaseStatusBanner } from '../ui/CaseStatusBanner';
import type { CaseSummary, CaseDetail } from '../types';

vi.mock('react-i18next', () => ({
  useTranslation: () => ({
    t: (key: string, options?: Record<string, unknown>) => {
      const translations: Record<string, string> = {
        'cases.page_title': 'پرونده‌های من',
        'cases.page_subtitle': 'رهگیری وضعیت پیشرفت خدمات درخواستی',
        'cases.filter_all': 'همه پرونده‌ها',
        'cases.filter_active': 'در جریان',
        'cases.search_placeholder': 'جستجوی کد رهگیری...',
        'cases.search_aria_label': 'جستجوی پرونده',
        'cases.view_details': 'مشاهده و پیگیری',
        'cases.back_to_list': 'بازگشت به لیست پرونده‌ها',
        'cases.turn_owner_status_label': 'وضعیت نوبت اقدام',
        'cases.sla_remaining_label': 'مهلت باقی‌مانده اقدام',
        'cases.action_required_title': 'نقص مدرک / نیازمند اقدام شما',
        'cases.view_sample_image': 'مشاهده نمونه صحیح',
        'cases.fix_document_btn': 'بارگذاری مجدد مدرک',
        'cases.sample_image_modal_title': 'نمونه تصویر صحیح مدرک',
        'cases.sample_image_alt': 'تصویر نمونه مدرک استاندارد',
        'cases.sample_image_guide': 'لطفاً مدرک خود را مشابه نمونه فوق ارسال نمایید.',
        'cases.timeline_title': 'روند پیشرفت مراحل اداری',
        'cases.timeline_aria_label': 'تایم‌لاین مراحل اداری پرونده',
        'cases.office_note_label': 'توضیح دفتر: ',
        'cases.office_assigned_title': 'دفتر پیشخوان مجری',
        'cases.actions_panel_label': 'اقدامات مجاز برای این پرونده',
        'cases.actions_available_title': 'اقدامات مجاز:',
        'cases.action_executed_prefix': 'عملیات فراخوانی شد',
        'cases.action_upload_fix_document': 'رفع نقص و ارسال مدرک',
        'cases.action_open_chat': 'گفتگو با دفتر',
        'cases.action_cancel_case': 'لغو درخواست',
        'cases.action_download_result': 'دریافت سند نهایی',
        'cases.action_rate_service': 'ثبت نظر و امتیاز',
        'cases.of': 'از',
        'common.close': 'بستن',
      };
      if (key === 'cases.service_fee' && options) {
        return `کارمزد خدمت: ${options.amount} ریال`;
      }
      return translations[key] ?? key;
    },
  }),
}));

describe('Case Tracking Slice (§4.1, §5.6, TASK-063, TASK-063-T)', () => {
  const originalFetch = globalThis.fetch;

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    globalThis.fetch = originalFetch;
  });

  const ALL_11_STATUSES: CaseStatus[] = [
    'draft',
    'searching_office',
    'assigned_to_office',
    'expert_review',
    'action_required',
    'government_inquiry',
    'ready_for_issue',
    'delivering',
    'completed',
    'rejected',
    'cancelled',
  ];

  it('renders CaseListPage and lists cases with search and filter', async () => {
    const mockCases: CaseSummary[] = [
      {
        id: 'case-1',
        tracking_code: 'PK-9911',
        status: 'expert_review',
        turn_owner: 'office',
        turn_owner_label: 'دفتر پیشخوان',
        service: { id: 'srv-1', title: 'صدور کارت بهداشت', tag: 'semi-online' },
        current_step: 3,
        total_steps: 6,
        fee_paid_rials: 500000,
        created_at: '2026-09-10T08:30:00Z',
        available_actions: ['open_chat'],
      },
      {
        id: 'case-2',
        tracking_code: 'PK-9922',
        status: 'completed',
        turn_owner: 'citizen',
        turn_owner_label: 'شهروند',
        service: { id: 'srv-2', title: 'تمدید گواهینامه', tag: 'in-person' },
        current_step: 6,
        total_steps: 6,
        fee_paid_rials: 1200000,
        created_at: '2026-09-08T10:00:00Z',
        available_actions: ['download_result', 'rate_service'],
      },
    ];

    globalThis.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({ data: mockCases }),
    } as Response);

    render(
      <MemoryRouter>
        <CaseListPage />
      </MemoryRouter>
    );

    await waitFor(() => {
      expect(screen.getByText('PK-9911')).toBeInTheDocument();
      expect(screen.getByText('صدور کارت بهداشت')).toBeInTheDocument();
      expect(screen.getByText('PK-9922')).toBeInTheDocument();
    });

    // Test filter active
    const filterActiveBtn = screen.getByText('در جریان');
    fireEvent.click(filterActiveBtn);
    expect(screen.getByText('PK-9911')).toBeInTheDocument();
    expect(screen.queryByText('PK-9922')).not.toBeInTheDocument();

    // Test search
    const searchInput = screen.getByRole('textbox', { name: 'جستجوی پرونده' });
    fireEvent.change(searchInput, { target: { value: '9911' } });
    expect(screen.getByText('PK-9911')).toBeInTheDocument();
  });

  it('renders CaseDetailPage with graphical 6-step timeline and SLA countdown', async () => {
    const mockDetail: CaseDetail = {
      id: 'case-1',
      tracking_code: 'PK-12345',
      status: 'expert_review',
      turn_owner: 'office',
      turn_owner_label: 'دفتر پیشخوان',
      service: { id: 'srv-1', title: 'کارت بازرگانی', tag: 'semi-online' },
      assigned_office: {
        id: 'off-1',
        name: 'دفتر پیشخوان دولت کد ۱۰۴۲',
        phone: '021-88776655',
      },
      current_step: 3,
      total_steps: 6,
      sla: {
        current_deadline: '2026-09-15T12:00:00Z',
        remaining_seconds: 7200,
        phase: 'processing',
      },
      documents: [],
      timeline: [
        {
          id: 'step-1',
          title: 'ثبت و پرداخت اولیه',
          status: 'done',
          turn_owner: 'system',
          occurred_at: '2026-09-10T10:00:00Z',
        },
        {
          id: 'step-2',
          title: 'واگذاری به دفتر',
          status: 'done',
          turn_owner: 'system',
          occurred_at: '2026-09-10T10:05:00Z',
        },
        {
          id: 'step-3',
          title: 'بررسی کارشناس دفتر',
          status: 'current',
          turn_owner: 'office',
          occurred_at: '2026-09-10T11:00:00Z',
        },
      ],
      available_actions: ['open_chat'],
    };

    globalThis.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({ data: mockDetail }),
    } as Response);

    render(
      <MemoryRouter initialEntries={['/cases/PK-12345']}>
        <Routes>
          <Route path="/cases/:trackingCode" element={<CaseDetailPage />} />
        </Routes>
      </MemoryRouter>
    );

    await waitFor(() => {
      expect(screen.getByText('کارت بازرگانی')).toBeInTheDocument();
      expect(screen.getByText('PK-12345')).toBeInTheDocument();
      expect(screen.getByText('دفتر پیشخوان دولت کد ۱۰۴۲')).toBeInTheDocument();
      expect(screen.getByText('ثبت و پرداخت اولیه')).toBeInTheDocument();
      expect(screen.getByText('بررسی کارشناس دفتر')).toBeInTheDocument();
      expect(screen.getByRole('timer')).toBeInTheDocument();
    });
  });

  it('renders Action-Required correction banner with return reason and sample image modal', async () => {
    const mockDetail: CaseDetail = {
      id: 'case-2',
      tracking_code: 'PK-ACTION-1',
      status: 'action_required',
      turn_owner: 'citizen',
      turn_owner_label: 'نوبت شماست',
      service: { id: 'srv-1', title: 'تعویض شناسنامه', tag: 'in-person' },
      current_step: 3,
      total_steps: 6,
      return_reason: {
        code: 'DOC_BLUR',
        title: 'تصویر شناسنامه ناخواناست',
        message: 'لطفاً تصویر باکیفیت و بدون لرزش در روشنایی کامل بارگذاری شود.',
        operator_note: 'سریال گوشه چپ صفحه اول خوانده نمی‌شود.',
        sample_image_url: '/img/samples/doc-blur.avif',
        returned_at: '2026-09-11T14:00:00Z',
      },
      documents: [],
      timeline: [],
      available_actions: ['upload_fix_document', 'cancel_case', 'open_chat'],
    };

    globalThis.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({ data: mockDetail }),
    } as Response);

    render(
      <MemoryRouter initialEntries={['/cases/PK-ACTION-1']}>
        <Routes>
          <Route path="/cases/:trackingCode" element={<CaseDetailPage />} />
        </Routes>
      </MemoryRouter>
    );

    await waitFor(() => {
      expect(screen.getByRole('alert')).toBeInTheDocument();
      expect(screen.getByText('تصویر شناسنامه ناخواناست')).toBeInTheDocument();
      expect(screen.getByText(/کد نقص: DOC_BLUR/)).toBeInTheDocument();
      expect(screen.getByText(/سریال گوشه چپ صفحه اول/)).toBeInTheDocument();
    });

    // View sample image modal
    const sampleBtn = screen.getByRole('button', { name: 'مشاهده نمونه صحیح' });
    fireEvent.click(sampleBtn);

    expect(screen.getByRole('dialog', { name: 'نمونه تصویر صحیح مدرک' })).toBeInTheDocument();
    expect(screen.getByAltText('تصویر نمونه مدرک استاندارد')).toBeInTheDocument();

    const closeBtn = screen.getByRole('button', { name: 'بستن' });
    fireEvent.click(closeBtn);
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('strictly renders action buttons ONLY from available_actions', () => {
    const handleAction = vi.fn();
    const actions = ['upload_fix_document', 'open_chat'];

    render(
      <CaseActionsPanel
        availableActions={actions}
        trackingCode="PK-100"
        onActionClick={handleAction}
      />
    );

    expect(screen.getByRole('button', { name: 'رفع نقص و ارسال مدرک' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'گفتگو با دفتر' })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'لغو درخواست' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'دریافت سند نهایی' })).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: 'رفع نقص و ارسال مدرک' }));
    expect(handleAction).toHaveBeenCalledWith('upload_fix_document');
  });

  it('renders all 11 statuses properly with correct StatusPills in list', async () => {
    const mockList: CaseSummary[] = ALL_11_STATUSES.map((st, i) => ({
      id: `case-${i}`,
      tracking_code: `PK-ST-${i}`,
      status: st,
      turn_owner: 'office',
      turn_owner_label: 'دفتر',
      service: { id: `srv-${i}`, title: `خدمت شماره ${i}`, tag: 'online' },
      current_step: 1,
      total_steps: 5,
      fee_paid_rials: 100000,
      created_at: '2026-09-12T00:00:00Z',
      available_actions: [],
    }));

    globalThis.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({ data: mockList }),
    } as Response);

    render(
      <MemoryRouter>
        <CaseListPage />
      </MemoryRouter>
    );

    await waitFor(() => {
      const pills = screen.getAllByRole('status');
      expect(pills.length).toBeGreaterThanOrEqual(11);
    });
  });

  it('passes axe accessibility checks for CaseStatusBanner', async () => {
    const { container } = render(
      <CaseStatusBanner
        returnReason={{
          code: 'DOC_BLUR',
          title: 'تصویر تار است',
          message: 'لطفاً دوباره عکس بگیرید.',
          returned_at: '2026-09-12T00:00:00Z',
        }}
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('passes axe accessibility checks for CaseActionsPanel', async () => {
    const { container } = render(
      <CaseActionsPanel
        availableActions={['open_chat', 'cancel_case']}
        trackingCode="PK-100"
        onActionClick={vi.fn()}
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
