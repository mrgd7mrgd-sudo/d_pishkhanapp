import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import '@/shared/i18n';
import { useOperatorAuthStore } from '@/features/auth/model/useOperatorAuthStore';
import { OfficeProfilePage } from '../ui/OfficeProfilePage';
import type { FullOfficeProfileResponse } from '../types';

describe('Operator Desk Office Profile Slice (§4.4, §7.3, §10.2, TASK-105, TASK-105-T)', () => {
  const mockFetch = vi.fn();
  let testQueryClient: QueryClient;

  const mockProfile: FullOfficeProfileResponse = {
    office: {
      id: 'off_shariati',
      code: '9101',
      name: 'دفتر پیشخوان شریعتی',
      manager_name: 'سهراب مرادی',
      is_online: true,
      membership_status: 'registered_online',
      address: 'تهران، خیابان شریعتی، پلاک ۱۰۰',
      phone: '02122334455',
      active_counters: 3,
      current_waiting_queue: 2,
      rating: 4.8,
      review_count: 20,
      sla_score: 98.5,
    },
    specialties: [
      { id: 'spec_1', office_id: 'off_shariati', title: 'کارت هوشمند ملی', is_active: true },
      { id: 'spec_2', office_id: 'off_shariati', title: 'امور مالیاتی اصناف', is_active: true },
    ],
    coverages: [
      {
        id: 'cov_1',
        office_id: 'off_shariati',
        category_id: 'cat_civil',
        is_active: true,
        daily_capacity: 100,
        category: { id: 'cat_civil', title: 'خدمات ثبت احوال و هویت' },
      },
      {
        id: 'cov_2',
        office_id: 'off_shariati',
        category_id: 'cat_tax',
        is_active: false,
        daily_capacity: 50,
        category: { id: 'cat_tax', title: 'خدمات مالیاتی و دارایی' },
      },
    ],
    announcements: [
      {
        id: 'ann_1',
        office_id: 'off_shariati',
        title: 'بروزرسانی شبکه در روز پنج‌شنبه',
        content: 'ساعات کاری در روز پنج‌شنبه تا ساعت ۱۲ خواهد بود.',
        priority: 'important',
        is_active: true,
      },
    ],
    operators: [
      {
        id: 'op_1',
        username: 'op_shariati_1',
        full_name: 'کیوان بهرامی',
        counter_number: 1,
        role: 'operator',
        is_active: true,
      },
      {
        id: 'op_2',
        username: 'op_shariati_2',
        full_name: 'سارا تهرانی',
        counter_number: 2,
        role: 'operator',
        is_active: false,
      },
    ],
    available_categories: [
      { id: 'cat_civil', title: 'خدمات ثبت احوال و هویت' },
      { id: 'cat_tax', title: 'خدمات مالیاتی و دارایی' },
    ],
  };

  beforeEach(() => {
    vi.clearAllMocks();
    globalThis.fetch = mockFetch;

    testQueryClient = new QueryClient({
      defaultOptions: {
        queries: { retry: false, gcTime: 0 },
        mutations: { retry: false },
      },
    });

    // Default to office_manager
    useOperatorAuthStore.setState({
      isAuthenticated: true,
      operator: {
        id: 'op_mgr_01',
        office_id: 'off_shariati',
        username: 'manager_sohrab',
        full_name: 'سهراب مرادی',
        role: 'manager',
        role_name: 'مدیر دفتر',
        counter_number: 1,
        is_active: true,
        last_login_at: '2026-09-16T08:00:00Z',
      },
    });

    mockFetch.mockImplementation(async (url: string, init?: RequestInit) => {
      const urlStr = url.toString();

      if (urlStr.includes('/api/v1/desk/profile/info') && init?.method === 'PATCH') {
        return { ok: true, status: 200, json: async () => ({ status: 'success' }) };
      }

      if (urlStr.includes('/api/v1/desk/profile/specialties') && init?.method === 'PUT') {
        return { ok: true, status: 200, json: async () => ({ data: [] }) };
      }

      if (urlStr.includes('/api/v1/desk/profile/coverages') && init?.method === 'PUT') {
        return { ok: true, status: 200, json: async () => ({ data: [] }) };
      }

      if (urlStr.includes('/api/v1/desk/profile/announcements') && init?.method === 'POST') {
        return {
          ok: true,
          status: 201,
          json: async () => ({
            data: {
              id: 'ann_new',
              title: 'اطلاعیه جدید',
              content: 'متن جدید',
              priority: 'normal',
              is_active: true,
            },
          }),
        };
      }

      if (urlStr.includes('/api/v1/desk/profile/announcements/') && init?.method === 'DELETE') {
        return { ok: true, status: 200, json: async () => ({ status: 'success' }) };
      }

      if (urlStr.includes('/api/v1/desk/profile/operators') && init?.method === 'POST') {
        if (urlStr.includes('/toggle')) {
          return { ok: true, status: 200, json: async () => ({ data: { is_active: false } }) };
        }
        return {
          ok: true,
          status: 201,
          json: async () => ({
            data: {
              id: 'op_new',
              username: 'new_op',
              full_name: 'کارمند تازه',
              counter_number: 3,
              role: 'operator',
              is_active: true,
            },
          }),
        };
      }

      if (urlStr.includes('/api/v1/desk/profile')) {
        return {
          ok: true,
          status: 200,
          json: async () => ({ data: mockProfile }),
        };
      }

      return { ok: false, status: 404, json: async () => ({}) };
    });
  });

  afterEach(() => {
    testQueryClient.clear();
  });

  const renderWithProviders = (ui: React.ReactElement) =>
    render(
      <QueryClientProvider client={testQueryClient}>
        <MemoryRouter>{ui}</MemoryRouter>
      </QueryClientProvider>
    );

  it('hides profile page and displays access-denied message for office_operator (Role enforcement §7.3)', async () => {
    useOperatorAuthStore.setState({
      isAuthenticated: true,
      operator: {
        id: 'op_regular_01',
        office_id: 'off_shariati',
        username: 'operator_reza',
        full_name: 'رضا کارشناس',
        role: 'operator',
        role_name: 'اپراتور باجه',
        counter_number: 2,
        is_active: true,
        last_login_at: '2026-09-16T08:00:00Z',
      },
    });

    renderWithProviders(<OfficeProfilePage />);

    expect(screen.getByTestId('profile-access-denied')).toBeInTheDocument();
    expect(
      screen.getByText('این بخش فقط مختص مدیر دفتر است و اپراتور عادی به آن دسترسی ندارد.')
    ).toBeInTheDocument();
    expect(screen.queryByTestId('subtab-info')).not.toBeInTheDocument();
  });

  it('renders all 4 subtabs (info, services, reception, announcements) and switches between them', async () => {
    renderWithProviders(<OfficeProfilePage />);

    expect(screen.getByTestId('subtab-info')).toBeInTheDocument();
    expect(screen.getByTestId('subtab-services')).toBeInTheDocument();
    expect(screen.getByTestId('subtab-reception')).toBeInTheDocument();
    expect(screen.getByTestId('subtab-announcements')).toBeInTheDocument();

    // Default info subtab
    expect(await screen.findByDisplayValue('تهران، خیابان شریعتی، پلاک ۱۰۰')).toBeInTheDocument();

    // Switch to services
    fireEvent.click(screen.getByTestId('subtab-services'));
    expect(await screen.findByText('پوشش دسته‌بندی‌های خدمات (فعال/غیرفعال برای توزیع و جستجو)')).toBeInTheDocument();
    expect(screen.getByText('کارت هوشمند ملی')).toBeInTheDocument();

    // Switch to reception
    fireEvent.click(screen.getByTestId('subtab-reception'));
    expect(await screen.findByText('پرسنل و اپراتورهای باجه‌های دفتر')).toBeInTheDocument();
    expect(screen.getByText('کیوان بهرامی')).toBeInTheDocument();

    // Switch to announcements
    fireEvent.click(screen.getByTestId('subtab-announcements'));
    expect(await screen.findByText('تابلوی اعلانات و اطلاعیه‌های دفتر')).toBeInTheDocument();
    expect(screen.getByText('بروزرسانی شبکه در روز پنج‌شنبه')).toBeInTheDocument();
  });

  it('allows manager to edit and save office info', async () => {
    renderWithProviders(<OfficeProfilePage />);

    const addressInput = await screen.findByDisplayValue('تهران، خیابان شریعتی، پلاک ۱۰۰');
    fireEvent.change(addressInput, {
      target: { value: 'تهران، خیابان شریعتی، پلاک ۱۲۰، طبقه ۲' },
    });

    const submitBtn = screen.getByRole('button', { name: 'ذخیره تغییرات مشخصات' });
    fireEvent.click(submitBtn);

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/desk/profile/info'),
        expect.objectContaining({ method: 'PATCH' })
      );
    });

    expect(await screen.findByText('اطلاعات پایه دفتر با موفقیت ذخیره شد.')).toBeInTheDocument();
  });

  it('allows manager to toggle category coverage and save specialties', async () => {
    renderWithProviders(<OfficeProfilePage />);

    fireEvent.click(screen.getByTestId('subtab-services'));
    expect(await screen.findByText('خدمات مالیاتی و دارایی')).toBeInTheDocument();

    // Toggle category coverage checkbox
    const taxCheckbox = screen.getByLabelText('فعال بودن پوشش خدمات مالیاتی و دارایی');
    fireEvent.click(taxCheckbox);

    // Save changes
    const saveBtn = screen.getByRole('button', { name: 'ذخیره پوشش و تخصص‌ها' });
    fireEvent.click(saveBtn);

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/desk/profile/coverages'),
        expect.objectContaining({ method: 'PUT' })
      );
    });
  });

  it('allows manager to toggle operator active state and delete announcement', async () => {
    renderWithProviders(<OfficeProfilePage />);

    // Test Operator toggle
    fireEvent.click(screen.getByTestId('subtab-reception'));
    const toggleBtn = await screen.findByTestId('toggle-op-op_1');
    fireEvent.click(toggleBtn);

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/desk/profile/operators/op_1/toggle'),
        expect.objectContaining({ method: 'POST' })
      );
    });

    // Test Announcement delete
    fireEvent.click(screen.getByTestId('subtab-announcements'));
    const deleteBtn = await screen.findByTestId('delete-ann-ann_1');
    fireEvent.click(deleteBtn);

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/desk/profile/announcements/ann_1'),
        expect.objectContaining({ method: 'DELETE' })
      );
    });
  });

  it('allows manager to create new operator and new announcement', async () => {
    renderWithProviders(<OfficeProfilePage />);

    // 1. Create Announcement
    fireEvent.click(screen.getByTestId('subtab-announcements'));
    const addAnnBtn = await screen.findByTestId('add-announcement-btn');
    fireEvent.click(addAnnBtn);

    const annTitle = screen.getByLabelText(/عنوان اطلاعیه/);
    const annContent = screen.getByLabelText(/متن کامل اطلاعیه/);
    fireEvent.change(annTitle, { target: { value: 'اطلاعیه جدید تست' } });
    fireEvent.change(annContent, { target: { value: 'شرح متن اطلاعیه تست' } });

    const submitAnn = screen.getByRole('button', { name: 'انتشار اطلاعیه' });
    fireEvent.click(submitAnn);

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/desk/profile/announcements'),
        expect.objectContaining({ method: 'POST' })
      );
    });

    // 2. Create Operator
    fireEvent.click(screen.getByTestId('subtab-reception'));
    const addOpBtn = await screen.findByTestId('add-operator-btn');
    fireEvent.click(addOpBtn);

    const opUser = screen.getByLabelText(/نام کاربری/);
    const opName = screen.getByLabelText(/نام و نام خانوادگی/);
    const opPass = screen.getByLabelText(/رمز عبور اولیه/);
    fireEvent.change(opUser, { target: { value: 'new_op_3' } });
    fireEvent.change(opName, { target: { value: 'کاربر شماره ۳' } });
    fireEvent.change(opPass, { target: { value: 'Password123!' } });

    const submitOp = screen.getByRole('button', { name: 'ثبت و ایجاد حساب' });
    fireEvent.click(submitOp);

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/desk/profile/operators'),
        expect.objectContaining({ method: 'POST' })
      );
    });
  });

  it('passes axe accessibility scan with zero violations', async () => {
    const { container } = renderWithProviders(<OfficeProfilePage />);

    await screen.findByDisplayValue('تهران، خیابان شریعتی، پلاک ۱۰۰');

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
