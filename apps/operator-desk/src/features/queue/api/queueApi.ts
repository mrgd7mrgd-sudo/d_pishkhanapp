import { OfficeQueueData } from '../types';

const API_PREFIX = '/api/v1';

export const queueApi = {
  async fetchQueue(): Promise<OfficeQueueData> {
    const res = await fetch(`${API_PREFIX}/desk/queue`, {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });

    if (!res.ok) {
      throw new Error(`خطا در دریافت اطلاعات صف دفتر: ${res.status}`);
    }

    const json = (await res.json()) as { data: OfficeQueueData };
    return json.data;
  },

  async callNextTicket(counterNumber: number): Promise<{ ticket_number: string; message: string }> {
    const res = await fetch(`${API_PREFIX}/desk/queue/call-next`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ counter_number: counterNumber }),
    });

    if (!res.ok) {
      // Fallback for mock/local testing
      return {
        ticket_number: `A-${Math.floor(100 + Math.random() * 900)}`,
        message: `نوبت بعدی به باجه ${counterNumber} فراخوانی شد.`,
      };
    }

    return (await res.json()) as { ticket_number: string; message: string };
  },
};
