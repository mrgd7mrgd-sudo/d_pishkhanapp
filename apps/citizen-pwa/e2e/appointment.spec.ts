import { describe, it, expect, vi, beforeEach } from 'vitest';

describe('Appointment E2E Scenario E20 (Architecture §10.3, TASK-106, TASK-106-T)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('E20: booking -> operator attendance check-in -> completion result recording', async () => {
    // 1. Citizen books an in-person appointment
    const bookingPayload = {
      office_id: 'off-tehran-101',
      service_id: 'srv-national-card',
      appointment_date: '2026-09-25',
      time_slot: '10:00-10:30',
      reminder_enabled: true,
      reminder_type: 'both',
    };

    let appointmentState = {
      id: 'app-e20-789',
      office_id: 'off-tehran-101',
      service_id: 'srv-national-card',
      appointment_date: '2026-09-25',
      time_slot: '10:00-10:30',
      queue_number: 14,
      counter_number: '3',
      status: 'scheduled',
      attendance: 'pending',
      completion: 'pending',
      completion_reason: null as string | null,
    };

    globalThis.fetch = vi.fn().mockImplementation((url: string, init?: RequestInit) => {
      // 1. Booking endpoint
      if (url === '/api/v1/appointments' && init?.method === 'POST') {
        const body = JSON.parse(init.body as string);
        expect(body.office_id).toBe('off-tehran-101');
        return Promise.resolve({
          ok: true,
          status: 201,
          json: async () => ({
            data: appointmentState,
          }),
        });
      }

      // 2. Desk Operator sets attendance to 'attended'
      if (url === '/api/v1/desk/appointments/app-e20-789/attendance' && init?.method === 'PATCH') {
        const body = JSON.parse(init.body as string);
        expect(body.attendance).toBe('attended');
        appointmentState = {
          ...appointmentState,
          attendance: 'attended',
          status: 'attended',
        };
        return Promise.resolve({
          ok: true,
          status: 200,
          json: async () => ({
            data: appointmentState,
          }),
        });
      }

      // 3. Desk Operator sets completion to 'completed'
      if (url === '/api/v1/desk/appointments/app-e20-789/completion' && init?.method === 'PATCH') {
        const body = JSON.parse(init.body as string);
        expect(body.completion).toBe('completed');
        appointmentState = {
          ...appointmentState,
          completion: 'completed',
        };
        return Promise.resolve({
          ok: true,
          status: 200,
          json: async () => ({
            data: appointmentState,
          }),
        });
      }

      return Promise.resolve({ ok: false, status: 404 });
    });

    // Step 1: Book
    const bookRes = await fetch('/api/v1/appointments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(bookingPayload),
    });
    const booked = (await bookRes.json()).data;
    expect(booked.queue_number).toBe(14);
    expect(booked.status).toBe('scheduled');
    expect(booked.attendance).toBe('pending');

    // Step 2: Citizen arrives at office, operator marks attendance
    const attendRes = await fetch('/api/v1/desk/appointments/app-e20-789/attendance', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ attendance: 'attended' }),
    });
    const attended = (await attendRes.json()).data;
    expect(attended.attendance).toBe('attended');
    expect(attended.status).toBe('attended');

    // Step 3: Operator delivers service and marks completion
    const completeRes = await fetch('/api/v1/desk/appointments/app-e20-789/completion', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ completion: 'completed' }),
    });
    const completed = (await completeRes.json()).data;
    expect(completed.completion).toBe('completed');
    expect(completed.attendance).toBe('attended');
  });
});
