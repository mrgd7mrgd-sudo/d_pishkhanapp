import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import '@/shared/i18n';
import { DeliveryPage } from '../ui/DeliveryPage';
import { WaybillPrintView } from '../components/WaybillPrintView';
import type { DeliveryItem, ReadyCase, WaybillData } from '../types';

describe('Operator Desk Delivery Slice (§4.4, §10.2, TASK-103, TASK-103-T)', () => {
  const mockFetch = vi.fn();
  let testQueryClient: QueryClient;

  const mockReadyCases: ReadyCase[] = [
    {
      id: 'case_ready_01',
      tracking_code: 'CR-1405-99881',
      service_title: 'صدور کارت هوشمند ملی',
      citizen_name: 'امیرحسین مرادی',
      citizen_mobile: '09121112233',
      delivery_preference: 'courier',
      address: 'تهران، خیابان آزادی، پلاک ۱۰',
      postal_code: '1345678901',
    },
  ];

  const mockDeliveries: DeliveryItem[] = [
    {
      id: 'del_01',
      case_id: 'case_01',
      office_id: 'off_01',
      doc_type: 'smart_card',
      doc_type_label: 'کارت هوشمند ملی / سوخت',
      destination_address: 'تهران، میدان آزادی',
      destination_postal_code: '1234567890',
      courier_type: 'express_courier',
      courier_type_label: 'پیک اختصاصی شهری (اکسپرس)',
      delivery_status: 'ready_for_dispatch',
      delivery_status_label: 'آماده تحویل به پیک',
      payment_method: 'office_wallet',
      payment_method_label: 'کسر از کیف پول دفتر',
      require_old_doc_return: false,
      is_sealed_pack: true,
      tracking_barcode: 'DEL-BARCODE-01',
      created_at: '2026-09-16T08:00:00Z',
      updated_at: '2026-09-16T08:00:00Z',
    },
    {
      id: 'del_02',
      case_id: 'case_02',
      office_id: 'off_01',
      doc_type: 'identity_booklet',
      doc_type_label: 'شناسنامه / گذرنامه',
      destination_address: 'تهران، سعادت‌آباد',
      destination_postal_code: '1987654321',
      courier_type: 'express_courier',
      courier_type_label: 'پیک اختصاصی شهری (اکسپرس)',
      delivery_status: 'courier_assigned',
      delivery_status_label: 'سفیر اختصاص یافت',
      courier_name: 'رضا کمالی',
      courier_phone: '09123334455',
      payment_method: 'cod',
      payment_method_label: 'پرداخت در محل',
      require_old_doc_return: true,
      is_sealed_pack: true,
      tracking_barcode: 'DEL-BARCODE-02',
      created_at: '2026-09-16T08:30:00Z',
      updated_at: '2026-09-16T08:30:00Z',
    },
    {
      id: 'del_03',
      case_id: 'case_03',
      office_id: 'off_01',
      doc_type: 'sealed_dossier',
      doc_type_label: 'پرونده پلمب‌شده محرمانه',
      destination_address: 'تهران، خیابان مطهری',
      destination_postal_code: '1567890123',
      courier_type: 'special_post',
      courier_type_label: 'پست ویژه (پیشتاز ۲۴ ساعته)',
      delivery_status: 'in_transit',
      delivery_status_label: 'در مسیر ارسال',
      courier_name: 'پست پیشتاز منطقه ۱۵',
      courier_phone: '09124445566',
      payment_method: 'office_wallet',
      payment_method_label: 'کسر از کیف پول دفتر',
      require_old_doc_return: false,
      is_sealed_pack: true,
      tracking_barcode: 'POST-1405-9988112233',
      created_at: '2026-09-16T09:00:00Z',
      updated_at: '2026-09-16T09:00:00Z',
    },
    {
      id: 'del_04',
      case_id: 'case_04',
      office_id: 'off_01',
      doc_type: 'official_certificate',
      doc_type_label: 'گواهی‌نامه رسمی',
      destination_address: 'تهران، صادقیه',
      destination_postal_code: '1456789012',
      courier_type: 'registered_post',
      courier_type_label: 'پست سفارشی سراسری',
      delivery_status: 'delivered',
      delivery_status_label: 'تحویل داده شد',
      payment_method: 'prepaid',
      payment_method_label: 'پرداخت آنلاین پیش‌کرایه',
      require_old_doc_return: false,
      is_sealed_pack: true,
      tracking_barcode: 'POST-1405-9988114455',
      created_at: '2026-09-15T10:00:00Z',
      updated_at: '2026-09-15T12:00:00Z',
    },
    {
      id: 'del_05',
      case_id: 'case_05',
      office_id: 'off_01',
      doc_type: 'business_license',
      doc_type_label: 'پروانه کسب',
      destination_address: 'تهران، بازار بزرگ',
      destination_postal_code: '1123456789',
      courier_type: 'express_courier',
      courier_type_label: 'پیک اختصاصی شهری (اکسپرس)',
      delivery_status: 'failed',
      delivery_status_label: 'تحویل ناموفق (برگشتی)',
      payment_method: 'cod',
      payment_method_label: 'پرداخت در محل',
      require_old_doc_return: false,
      is_sealed_pack: true,
      tracking_barcode: 'DEL-BARCODE-05',
      created_at: '2026-09-14T11:00:00Z',
      updated_at: '2026-09-14T15:00:00Z',
    },
  ];

  beforeEach(() => {
    vi.stubGlobal('fetch', mockFetch);
    mockFetch.mockImplementation(async (url: string) => {
      if (url.includes('/deliveries/ready-cases')) {
        return { ok: true, json: async () => ({ data: mockReadyCases }) };
      }
      if (url.includes('/deliveries')) {
        return { ok: true, json: async () => ({ data: mockDeliveries }) };
      }
      return { ok: false, status: 404, json: async () => ({}) };
    });

    testQueryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false, gcTime: 0 },
      },
    });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  const renderWithProviders = (ui: React.ReactElement) => {
    return render(
      <QueryClientProvider client={testQueryClient}>
        <MemoryRouter>{ui}</MemoryRouter>
      </QueryClientProvider>
    );
  };

  it('renders all 3 subtabs and allows navigation between them', async () => {
    renderWithProviders(<DeliveryPage />);

    expect(await screen.findByTestId('tab-active-deliveries')).toBeInTheDocument();
    expect(screen.getByTestId('tab-new-courier')).toBeInTheDocument();
    expect(screen.getByTestId('tab-postal-barcodes')).toBeInTheDocument();

    // Click new courier tab
    fireEvent.click(screen.getByTestId('tab-new-courier'));
    expect(await screen.findByText('صدور حواله و درخواست پیک / پست جدید')).toBeInTheDocument();

    // Click postal barcodes tab
    fireEvent.click(screen.getByTestId('tab-postal-barcodes'));
    expect(await screen.findByText('بارنامه‌ها و بارکدهای پستی شرکت ملی پست')).toBeInTheDocument();
  });

  it('correctly renders all 5 delivery statuses with proper labels', async () => {
    renderWithProviders(<DeliveryPage />);

    expect(await screen.findByText('آماده تحویل به پیک')).toBeInTheDocument();
    expect(screen.getByText('سفیر اختصاص یافت')).toBeInTheDocument();
    expect(screen.getByText('در مسیر ارسال')).toBeInTheDocument();
    expect(screen.getByText('تحویل داده شد')).toBeInTheDocument();
    expect(screen.getByText('تحویل ناموفق (برگشتی)')).toBeInTheDocument();
  });

  it('displays clear Persian error message when wrong OTP is entered', async () => {
    mockFetch.mockImplementation(async (url: string, opts?: RequestInit) => {
      if (url.includes('/deliveries/ready-cases')) {
        return { ok: true, json: async () => ({ data: mockReadyCases }) };
      }
      if (url.includes('/confirm') && opts?.method === 'POST') {
        return {
          ok: false,
          status: 422,
          json: async () => ({ detail: 'کد تأیید یک‌بارمصرف نامعتبر یا منقضی شده است.' }),
        };
      }
      if (url.includes('/deliveries')) {
        return { ok: true, json: async () => ({ data: mockDeliveries }) };
      }
      return { ok: false, status: 404, json: async () => ({}) };
    });

    renderWithProviders(<DeliveryPage />);

    // Click "تأیید تحویل با OTP" for the in_transit delivery
    const otpButtons = await screen.findAllByText('تأیید تحویل با OTP');
    expect(otpButtons[0]).toBeDefined();
    fireEvent.click(otpButtons[0]!);

    expect(await screen.findByText('تأیید تحویل مرسوله با کد یک‌بارمصرف (OTP)')).toBeInTheDocument();

    const input = screen.getByLabelText('کد ۶ رقمی تحویل');
    const submitBtn = screen.getByText('تأیید و ثبت تحویل');

    // Test client validation for invalid length
    fireEvent.change(input, { target: { value: '123' } });
    fireEvent.click(submitBtn);

    // Enter wrong 6-digit OTP
    fireEvent.change(input, { target: { value: '999999' } });
    fireEvent.click(submitBtn);

    expect(
      await screen.findByText('کد تأیید یک‌بارمصرف نامعتبر یا منقضی شده است.')
    ).toBeInTheDocument();
  });

  it('pre-fills address and postal code when ready case is selected in new request form', async () => {
    renderWithProviders(<DeliveryPage />);

    fireEvent.click(await screen.findByTestId('tab-new-courier'));

    const caseSelect = await screen.findByLabelText(/پرونده آماده تحویل/);
    fireEvent.change(caseSelect, { target: { value: 'case_ready_01' } });

    const addressInput = screen.getByLabelText(/نشانی مقصد تحویل/);
    const postalInput = screen.getByLabelText(/کد پستی مقصد/);

    expect(addressInput).toHaveValue('تهران، خیابان آزادی، پلاک ۱۰');
    expect(postalInput).toHaveValue('1345678901');
  });

  it('renders printable waybill document with barcode and details', () => {
    const waybillData: WaybillData = {
      delivery_id: 'del_test_99',
      tracking_barcode: 'DEL-1405-998811',
      office_name: 'دفتر پیشخوان دولت ولیعصر',
      office_phone: '۰۲۱۸۸۹۰۱۲۳۴',
      office_address: 'تهران، میدان ولیعصر',
      citizen_name: 'سهراب سپهری',
      destination_address: 'تهران، خیابان انقلاب، کوچه حافظ',
      destination_postal_code: '1234567890',
      doc_type_label: 'کارت هوشمند ملی',
      courier_type_label: 'پیک اکسپرس شهری',
      payment_method_label: 'کیف پول دفتر',
      is_sealed_pack: true,
      require_old_doc_return: true,
    };

    render(
      <MemoryRouter>
        <WaybillPrintView waybill={waybillData} />
      </MemoryRouter>
    );

    expect(screen.getByTestId('waybill-printable')).toBeInTheDocument();
    expect(screen.getByTestId('waybill-barcode')).toHaveTextContent('DEL-1405-998811');
    expect(screen.getByText('دفتر پیشخوان دولت ولیعصر')).toBeInTheDocument();
    expect(screen.getByText('سهراب سپهری')).toBeInTheDocument();
    expect(screen.getByText('چاپ بارنامه (Print)')).toBeInTheDocument();
  });

  it('passes axe accessibility scan with zero violations', async () => {
    const { container } = renderWithProviders(<DeliveryPage />);
    await screen.findByTestId('tab-active-deliveries');

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('handles assigning a courier with form validation and API mutation', async () => {
    mockFetch.mockImplementation(async (url: string, opts?: RequestInit) => {
      if (url.includes('/assign-courier') && opts?.method === 'POST') {
        return { ok: true, json: async () => ({ data: { ...mockDeliveries[0], courier_name: 'اصغر محمدی' } }) };
      }
      if (url.includes('/deliveries/ready-cases')) {
        return { ok: true, json: async () => ({ data: mockReadyCases }) };
      }
      if (url.includes('/deliveries')) {
        return { ok: true, json: async () => ({ data: mockDeliveries }) };
      }
      return { ok: false, status: 404, json: async () => ({}) };
    });

    renderWithProviders(<DeliveryPage />);

    const assignBtn = await screen.findByText('تخصیص سفیر');
    fireEvent.click(assignBtn);

    expect(await screen.findByText('تخصیص سفیر به مرسوله')).toBeInTheDocument();

    const nameInput = screen.getByLabelText(/نام و نام خانوادگی سفیر/);
    const phoneInput = screen.getByLabelText(/شماره موبایل سفیر/);
    const submitBtn = screen.getByText('ثبت و تخصیص');

    fireEvent.change(nameInput, { target: { value: 'اصغر محمدی' } });
    fireEvent.change(phoneInput, { target: { value: '09127778899' } });
    fireEvent.click(submitBtn);

    await waitFor(() => {
      expect(screen.queryByText('تخصیص سفیر به مرسوله')).not.toBeInTheDocument();
    });
  });

  it('filters active deliveries by status and search input', async () => {
    renderWithProviders(<DeliveryPage />);

    expect(await screen.findByText('DEL-BARCODE-01')).toBeInTheDocument();

    // Filter by delivered
    const deliveredFilterBtn = screen.getByRole('button', { name: /تحویل داده شد/ });
    fireEvent.click(deliveredFilterBtn);

    expect(screen.queryByText('DEL-BARCODE-01')).not.toBeInTheDocument();
    expect(screen.getByText('POST-1405-9988114455')).toBeInTheDocument();

    // Reset to all
    const allFilterBtn = screen.getByRole('button', { name: /همه \(/ });
    fireEvent.click(allFilterBtn);
    expect(screen.getByText('DEL-BARCODE-01')).toBeInTheDocument();

    // Search input
    const searchInput = screen.getByPlaceholderText('جستجوی بارکد، نشانی، سفیر...');
    fireEvent.change(searchInput, { target: { value: 'سعادت‌آباد' } });

    expect(screen.queryByText('DEL-BARCODE-01')).not.toBeInTheDocument();
    expect(screen.getByText('DEL-BARCODE-02')).toBeInTheDocument();
  });
});
