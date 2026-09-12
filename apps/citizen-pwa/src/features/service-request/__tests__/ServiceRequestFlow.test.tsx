import React from 'react';
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import { ServiceRequestFlow } from '../ui/ServiceRequestFlow';

const mockService = {
  id: 'svc-test-1',
  title: 'تعویض کارت ملی هوشمند',
  slug: 'national-card-renew',
  description: 'درخواست تعویض کارت ملی مفقودی یا مستعمل',
  fee_rials: 500000,
  sla_hours: 48,
  required_docs: [
    { document_type_code: 'id_doc', title: 'تصویر شناسنامه', is_mandatory: true },
  ],
};

const mockOffices = [
  { id: 'off-1', code: '101', name: 'دفتر مرکزی ونک', rating: 4.8 },
  { id: 'off-2', code: '102', name: 'دفتر آزادی', rating: 4.5 },
];

describe('ServiceRequestFlow Slice (§4.1, §5.6, TASK-062, TASK-062-T)', () => {
  const originalFetch = global.fetch;

  beforeEach(() => {
    vi.clearAllMocks();

    global.fetch = vi.fn(async (url: RequestInfo | URL, init?: RequestInit) => {
      const urlStr = url.toString();

      if (urlStr.includes('/api/v1/services/svc-test-1')) {
        return {
          ok: true,
          status: 200,
          json: async () => ({ data: mockService }),
        } as Response;
      }

      if (urlStr.includes('/api/v1/wallet')) {
        return {
          ok: true,
          status: 200,
          json: async () => ({ data: { balance_rials: 200000 } }), // lower than 500,000 for wallet test
        } as Response;
      }

      if (urlStr.includes('/api/v1/offices')) {
        return {
          ok: true,
          status: 200,
          json: async () => ({ data: mockOffices }),
        } as Response;
      }

      if (urlStr.includes('/api/v1/documents/upload-intent')) {
        return {
          ok: true,
          status: 200,
          json: async () => ({
            data: { upload_id: 'up-12345', upload_url: 'https://storage.test/upload' },
          }),
        } as Response;
      }

      if (urlStr.includes('https://storage.test/upload')) {
        return { ok: true, status: 200 } as Response;
      }

      if (urlStr.includes('/api/v1/documents/upload-complete')) {
        return {
          ok: true,
          status: 200,
          json: async () => ({ data: { status: 'processed' } }),
        } as Response;
      }

      if (urlStr.includes('/api/v1/cases') && init?.method === 'POST') {
        return {
          ok: true,
          status: 201,
          json: async () => ({
            data: { id: 'case-uuid-1', tracking_code: 'CR-1405-9988', status: 'searching_office' },
          }),
        } as Response;
      }

      return {
        ok: false,
        status: 404,
        json: async () => ({ message: 'Not found' }),
      } as Response;
    });
  });

  afterEach(() => {
    global.fetch = originalFetch;
  });

  const renderComponent = () => {
    return render(
      <MemoryRouter initialEntries={['/request/svc-test-1']}>
        <Routes>
          <Route path="/request/:serviceId" element={<ServiceRequestFlow />} />
          <Route path="/wallet" element={<div>صفحه شارژ کیف پول</div>} />
          <Route path="/cases/:trackingCode" element={<div>صفحه جزئیات پرونده</div>} />
        </Routes>
      </MemoryRouter>
    );
  };

  it('renders Step 1 (Info) with service details, dropzone and commitment', async () => {
    renderComponent();

    expect(await screen.findByText('تعویض کارت ملی هوشمند')).toBeInTheDocument();
    expect(screen.getByText(/۵۰۰٬۰۰۰ ریال/)).toBeInTheDocument();
    expect(screen.getByText('تصویر شناسنامه')).toBeInTheDocument();
    expect(screen.getByText(/امضای تعهدنامه/)).toBeInTheDocument();
  });

  it('validates mandatory doc and commitment checkbox before advancing', async () => {
    renderComponent();
    expect(await screen.findByText('تعویض کارت ملی هوشمند')).toBeInTheDocument();

    const nextBtn = screen.getByText('مرحله بعد');
    fireEvent.click(nextBtn);

    expect(screen.getByText(/بارگذاری مدرک "تصویر شناسنامه" الزامی است/)).toBeInTheDocument();

    // Select document to satisfy doc validation
    const fileInput = screen.getByTestId('file-dropzone-input');
    const dummyFile = new File(['content'], 'shenasname.jpg', { type: 'image/jpeg' });
    fireEvent.change(fileInput, { target: { files: [dummyFile] } });

    await waitFor(() => {
      expect(screen.getByText('shenasname.jpg')).toBeInTheDocument();
    });

    fireEvent.click(nextBtn);
    expect(screen.getByText('تأیید تعهدنامه الزامی است.')).toBeInTheDocument();

    // Check commitment
    const checkbox = screen.getByRole('checkbox');
    fireEvent.click(checkbox);
    fireEvent.click(nextBtn);

    // Should now be on Step 2 (Dispatch Type)
    expect(await screen.findByText('نحوه واگذاری پرونده به دفاتر پیشخوان را انتخاب فرمایید.')).toBeInTheDocument();
  });

  it('navigates back and forth between steps correctly', async () => {
    renderComponent();
    expect(await screen.findByText('تعویض کارت ملی هوشمند')).toBeInTheDocument();

    // Upload & check commitment
    const fileInput = screen.getByTestId('file-dropzone-input');
    const dummyFile = new File(['content'], 'shenasname.jpg', { type: 'image/jpeg' });
    fireEvent.change(fileInput, { target: { files: [dummyFile] } });
    await waitFor(() => expect(screen.getByText('shenasname.jpg')).toBeInTheDocument());

    fireEvent.click(screen.getByRole('checkbox'));
    fireEvent.click(screen.getByText('مرحله بعد'));

    // In Step 2
    expect(await screen.findByText('نحوه واگذاری پرونده به دفاتر پیشخوان را انتخاب فرمایید.')).toBeInTheDocument();

    // Go back to Step 1
    const backBtn = screen.getByTestId('bottom-back-button');
    fireEvent.click(backBtn);
    expect(await screen.findByText('شیوه دریافت نتیجه خدمت')).toBeInTheDocument();

    // Advance to Step 2 again and then Step 3 (Payment)
    fireEvent.click(screen.getByText('مرحله بعد'));
    expect(await screen.findByText('نحوه واگذاری پرونده به دفاتر پیشخوان را انتخاب فرمایید.')).toBeInTheDocument();
    fireEvent.click(screen.getByText('مرحله بعد'));
    expect(await screen.findByText('مبلغ قابل پرداخت')).toBeInTheDocument();
  });

  it('displays warning and charge wallet button when wallet balance is insufficient', async () => {
    renderComponent();
    expect(await screen.findByText('تعویض کارت ملی هوشمند')).toBeInTheDocument();

    // Pass Step 1
    fireEvent.change(screen.getByTestId('file-dropzone-input'), {
      target: { files: [new File(['c'], 'doc.jpg', { type: 'image/jpeg' })] },
    });
    await waitFor(() => expect(screen.getByText('doc.jpg')).toBeInTheDocument());
    fireEvent.click(screen.getByRole('checkbox'));
    fireEvent.click(screen.getByText('مرحله بعد'));

    // Pass Step 2
    expect(await screen.findByText('نحوه واگذاری پرونده به دفاتر پیشخوان را انتخاب فرمایید.')).toBeInTheDocument();
    fireEvent.click(screen.getByText('مرحله بعد'));

    // Step 3: Payment
    expect(await screen.findByText('مبلغ قابل پرداخت')).toBeInTheDocument();
    expect(screen.getByTestId('wallet-balance-display')).toHaveTextContent(/۲۰۰٬۰۰۰/);

    const warning = await screen.findByTestId('wallet-insufficient-warning');
    expect(warning).toBeInTheDocument();
    expect(screen.getByText('شارژ کیف پول')).toBeInTheDocument();
  });

  it('completes submission through searching to assigned state with tracking code', async () => {
    renderComponent();
    expect(await screen.findByText('تعویض کارت ملی هوشمند')).toBeInTheDocument();

    // Step 1
    fireEvent.change(screen.getByTestId('file-dropzone-input'), {
      target: { files: [new File(['c'], 'doc.jpg', { type: 'image/jpeg' })] },
    });
    await waitFor(() => expect(screen.getByText('doc.jpg')).toBeInTheDocument());
    fireEvent.click(screen.getByRole('checkbox'));
    fireEvent.click(screen.getByText('مرحله بعد'));

    // Step 2
    expect(await screen.findByText('نحوه واگذاری پرونده به دفاتر پیشخوان را انتخاب فرمایید.')).toBeInTheDocument();
    fireEvent.click(screen.getByText('مرحله بعد'));

    // Step 3: Select gateway payment
    expect(await screen.findByText('مبلغ قابل پرداخت')).toBeInTheDocument();
    fireEvent.click(screen.getByText('درگاه پرداخت اینترنتی (شتاب)'));

    // Submit payment
    fireEvent.click(screen.getByText('پرداخت و ثبت نهایی'));

    // Step 5: Assigned
    expect(await screen.findByText('پرونده شما با موفقیت ثبت شد!')).toBeInTheDocument();
    expect(screen.getByTestId('tracking-code-value')).toHaveTextContent('CR-1405-9988');
    expect(screen.getByText('مشاهده و رهگیری پرونده')).toBeInTheDocument();
  });

  it('passes axe accessibility checks with 0 violations', async () => {
    const { container } = renderComponent();
    expect(await screen.findByText('تعویض کارت ملی هوشمند')).toBeInTheDocument();

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
