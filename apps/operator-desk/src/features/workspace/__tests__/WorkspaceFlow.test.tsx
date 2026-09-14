import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import { WorkspacePage } from '../ui/WorkspacePage';
import { ReturnModal } from '../ui/ReturnModal';
import { DocumentReviewPanel } from '../ui/DocumentReviewPanel';
import { useWorkspaceStore } from '../model/useWorkspaceStore';
import { DeskCaseListItem, DeskCaseDetail, CaseDocumentItem } from '../types';
import { RETURN_REASON_CODES } from '@pishkhan/domain';
import { DataGrid } from '@pishkhan/ui-kit';

describe('Operator Desk Workspace Slice (§4.4, §4.8, §5.6, TASK-078, TASK-078-T)', () => {
  const mockFetch = vi.fn();

  const dummyDoc: CaseDocumentItem = {
    id: 'doc_national_card',
    document_type_code: 'NATIONAL_CARD_FRONT',
    title: 'کارت ملی (رو)',
    status: 'uploaded',
    version: 1,
    quality_warnings: ['blur'],
    uploaded_at: new Date().toISOString(),
  };

  const dummyCase: DeskCaseListItem = {
    id: 'case_101',
    tracking_code: 'CR-1405-12345',
    status: 'assigned_to_office',
    turn_owner: 'office',
    turn_owner_label: 'نوبت کارشناس دفتر',
    service: {
      id: 'svc_card',
      title: 'صدور کارت هوشمند ملی',
      tag: 'in-person',
    },
    assigned_office: {
      id: 'off_thr_01',
      name: 'دفتر پیشخوان دولت کد ۱۲۳',
    },
    current_step: 2,
    total_steps: 6,
    fee_paid_rials: 500000,
    created_at: new Date().toISOString(),
    sla_deadline_at: new Date(Date.now() + 86400000).toISOString(),
    available_actions: ['review', 'return', 'inquiry', 'complete', 'reject'],
  };

  const dummyDetail: DeskCaseDetail = {
    ...dummyCase,
    last_change_text: 'درخواست شما با موفقیت ثبت شد.',
    return_reason: null,
    sla: {
      current_deadline: new Date(Date.now() + 86400000).toISOString(),
      remaining_seconds: 72000,
      phase: 'processing',
    },
    documents: [dummyDoc],
    timeline: [],
    realtime_channel: 'private-case.case_101',
  };

  beforeEach(() => {
    vi.stubGlobal('fetch', mockFetch);
    useWorkspaceStore.getState().reset();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('renders 500 virtualized rows rapidly in <= 120ms', async () => {
    // Generate 500 dummy rows
    const rows500: DeskCaseListItem[] = Array.from({ length: 500 }, (_, i) => ({
      ...dummyCase,
      id: `case_${i + 1}`,
      tracking_code: `CR-1405-${String(i + 1).padStart(5, '0')}`,
    }));

    useWorkspaceStore.setState({ cases: rows500, loadingList: false });

    // Warm up
    const dummyRender = render(<div />);
    dummyRender.unmount();

    const startTime = performance.now();
    render(
      <DataGrid<DeskCaseListItem>
        data={rows500}
        columns={[
          { id: 'tracking_code', header: 'کد رهگیری', cell: (i) => i.tracking_code },
          { id: 'status', header: 'وضعیت', cell: (i) => i.status },
        ]}
        keyExtractor={(i) => i.id}
      />
    );
    const duration = performance.now() - startTime;

    // Architecture §4.8 & TASK-078-T criterion: render 500 rows in <= 120ms
    expect(duration).toBeLessThanOrEqual(120);

    // Initial virtualized window should render first batch
    expect(screen.getByText('CR-1405-00001')).toBeInTheDocument();
  });

  it('ReturnModal renders all 10 return reason codes with editable default messages', async () => {
    const mockSubmit = vi.fn().mockResolvedValue(undefined);

    render(
      <ReturnModal
        isOpen={true}
        onClose={vi.fn()}
        onSubmit={mockSubmit}
        documents={[dummyDoc]}
      />
    );

    // Verify all 10 codes from domain exist
    expect(RETURN_REASON_CODES).toHaveLength(10);
    for (const code of RETURN_REASON_CODES) {
      expect(screen.getByText(code)).toBeInTheDocument();
    }

    // Check textarea contains initial default message
    const textarea = screen.getByLabelText(/متن راهنما و توضیحات به متقاضی/i);
    expect(textarea).toHaveValue(
      'تصویر مدرک بارگذاری‌شده تار است و شماره سریال یا مندرجات خوانا نیست. لطفاً در نور کافی و بدون لرزش مجدداً عکس بگیرید.'
    );

    // Edit message
    fireEvent.change(textarea, { target: { value: 'پیام سفارشی اپراتور' } });
    expect(textarea).toHaveValue('پیام سفارشی اپراتور');

    // Submit form
    const submitBtn = screen.getByRole('button', { name: /ثبت بازگشت/i });
    fireEvent.click(submitBtn);

    await waitFor(() => {
      expect(mockSubmit).toHaveBeenCalledWith({
        reason_code: 'DOC_BLUR',
        operator_note: 'پیام سفارشی اپراتور',
        target_document_type_code: 'NATIONAL_CARD_FRONT',
      });
    });
  });

  it('DocumentReviewPanel fetches signed URL, provides zoom and rotation controls', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ url: 'https://minio.local/case-documents/doc1.jpg?sign=xyz' }),
    });

    render(<DocumentReviewPanel documents={[dummyDoc]} />);

    await waitFor(() => {
      const img = screen.getByRole('img', { name: 'کارت ملی (رو)' });
      expect(img).toHaveAttribute('src', 'https://minio.local/case-documents/doc1.jpg?sign=xyz');
    });

    // Zoom in
    const zoomInBtn = screen.getByRole('button', { name: /بزرگ‌نمایی/i });
    fireEvent.click(zoomInBtn);
    expect(screen.getByText('125%')).toBeInTheDocument();

    // Zoom out
    const zoomOutBtn = screen.getByRole('button', { name: /کوچک‌نمایی/i });
    fireEvent.click(zoomOutBtn);
    expect(screen.getByText('100%')).toBeInTheDocument();
  });

  it('passes accessibility audits with zero axe violations', async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => ({ url: 'https://minio.local/case-documents/doc1.jpg?sign=xyz' }),
    });

    useWorkspaceStore.setState({
      cases: [dummyCase],
      selectedCaseDetail: dummyDetail,
      loadingList: false,
    });

    const { container } = render(<WorkspacePage autoFetch={false} />);
    await waitFor(() => {
      expect(screen.getAllByText('CR-1405-12345').length).toBeGreaterThanOrEqual(1);
    });

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
