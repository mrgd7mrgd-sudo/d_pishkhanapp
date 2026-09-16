import React from 'react';
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import '@/shared/i18n';
import { AppointmentsPage } from '../ui/AppointmentsPage';
import type { AppointmentItem, OfficeSlotsResponse } from '../types';

describe('Citizen Appointments Slice (§4.4, §10.2, TASK-106, TASK-106-T)', () => {
  const mockFetch = vi.fn();
  let testQueryClient: QueryClient;

  const mockAppointments: AppointmentItem[] = [
    {
      id: 'app_1',
      citizen_id: 'cit_1',
      office_id: 'off_shariati',
      office_name: 'دفتر پیشخوان شریعتی',
      office_code: '9101',
      office_address: 'تهران، خیابان شریعتی، پلاک ۱۰۰',
      service_id: 'srv_id',
      service_title: 'صدور کارت هوشمند ملی',
      appointment_date: '1405/06/25',
      time_slot: '09:00 - 09:30',
      tracking_code: 'CR-APP-1405-001',
      status: 'scheduled',
      status_label: 'زمان‌بندی‌شده',
      attendance: 'pending',
      attendance_label: 'در انتظار مراجعه',
      completion: 'pending',
      completion_label: 'در انتظار',
      queue_number: 'Q-101',
      ticket_number: 'Q-101',
      counter_number: 2,
      reminder_enabled: true,
      reminder_type: 'both',
      created_at: '2026-09-16T08:00:00Z',
    },
  ];

  const mockSlots: OfficeSlotsResponse = {
    office_id: 'off_shariati',
    office_name: 'دفتر پیشخوان دولت مرکزی',
    date: '2026-09-17',
    is_open: true,
    capacity_per_slot: 4,
    slots: [
      {
        time_slot: '08:30 - 09:00',
        start_time: '08:30',
        end_time: '09:00',
        capacity: 4,
        booked_count: 4,
        is_available: false, // Full capacity
      },
      {
        time_slot: '09:00 - 09:30',
        start_time: '09:00',
        end_time: '09:30',
        capacity: 4,
        booked_count: 1,
        is_available: true,
      },
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

    mockFetch.mockImplementation(async (url: string, init?: RequestInit) => {
      const urlStr = url.toString();

      if (urlStr.includes('/slots')) {
        return { ok: true, status: 200, json: async () => ({ data: mockSlots }) };
      }

      if (urlStr.includes('/appointments') && init?.method === 'POST') {
        return {
          ok: true,
          status: 201,
          json: async () => ({
            data: {
              ...mockAppointments[0],
              id: 'app_new_2',
              queue_number: 'Q-102',
              time_slot: '09:00 - 09:30',
            },
          }),
        };
      }

      if (urlStr.includes('/appointments/') && init?.method === 'DELETE') {
        return {
          ok: true,
          status: 200,
          json: async () => ({
            data: {
              ...mockAppointments[0],
              status: 'cancelled',
            },
          }),
        };
      }

      if (urlStr.includes('/appointments')) {
        return { ok: true, status: 200, json: async () => ({ data: mockAppointments }) };
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

  it('renders appointment list with queue_number and counter_number', async () => {
    renderWithProviders(<AppointmentsPage />);

    expect(await screen.findByText('صدور کارت هوشمند ملی')).toBeInTheDocument();
    expect(screen.getByText('Q-101')).toBeInTheDocument();
    expect(screen.getByText('باجه 2')).toBeInTheDocument();
    expect(screen.getByText('نوبت معتبر')).toBeInTheDocument();
  });

  it('disables full capacity slots in UI and books available slot', async () => {
    renderWithProviders(<AppointmentsPage />);

    // Open booking modal
    const openBtn = screen.getByTestId('new-appointment-btn');
    fireEvent.click(openBtn);

    // Verify modal opened
    expect(await screen.findByText(/رزرو نوبت حضوری در/)).toBeInTheDocument();

    // Verify full slot is disabled
    const fullSlotBtn = await screen.findByTestId('slot-btn-08:30 - 09:00');
    expect(fullSlotBtn).toBeDisabled();
    expect(screen.getByText('تکمیل ظرفیت')).toBeInTheDocument();

    // Select available slot
    const availSlotBtn = screen.getByTestId('slot-btn-09:00 - 09:30');
    expect(availSlotBtn).not.toBeDisabled();
    fireEvent.click(availSlotBtn);

    // Click confirm
    const confirmBtn = screen.getByTestId('confirm-booking-btn');
    expect(confirmBtn).not.toBeDisabled();
    fireEvent.click(confirmBtn);

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/appointments'),
        expect.objectContaining({ method: 'POST' })
      );
    });

    expect(await screen.findByText(/نوبت شما با موفقیت ثبت شد/)).toBeInTheDocument();
  });

  it('allows cancelling an appointment', async () => {
    renderWithProviders(<AppointmentsPage />);

    const cancelBtn = await screen.findByTestId('cancel-appointment-btn-app_1');
    fireEvent.click(cancelBtn);

    await waitFor(() => {
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/appointments/app_1'),
        expect.objectContaining({ method: 'DELETE' })
      );
    });

    expect(await screen.findByText('نوبت با موفقیت لغو گردید.')).toBeInTheDocument();
  });

  it('passes axe accessibility scan with zero violations', async () => {
    const { container } = renderWithProviders(<AppointmentsPage />);

    await screen.findByText('صدور کارت هوشمند ملی');

    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
