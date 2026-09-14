import { create } from 'zustand';
import { OfficeQueueData, QueueTicket } from '../types';
import { queueApi } from '../api/queueApi';
import { playNotificationSound } from '../../../shared/realtime/echo';

interface QueueState {
  queue: OfficeQueueData | null;
  tickets: QueueTicket[];
  counterNumber: number;
  currentCallingTicket: string | null;
  loading: boolean;
  callingLoading: boolean;
  error: string | null;

  // Actions
  setCounterNumber: (num: number) => void;
  fetchQueue: () => Promise<void>;
  updateQueueData: (data: Partial<OfficeQueueData>) => void;
  callNext: () => Promise<string | null>;
  reset: () => void;
}

export const useQueueStore = create<QueueState>((set, get) => ({
  queue: null,
  tickets: [
    {
      ticket_number: 'A-101',
      tracking_code: 'CR-1405-11001',
      service_title: 'صدور کارت هوشمند ملی',
      waiting_minutes: 12,
      status: 'waiting',
    },
    {
      ticket_number: 'A-102',
      tracking_code: 'CR-1405-11002',
      service_title: 'تأییدیه تحصیلی و دیپلم',
      waiting_minutes: 8,
      status: 'waiting',
    },
    {
      ticket_number: 'A-103',
      tracking_code: 'CR-1405-11003',
      service_title: 'گواهی عدم سوء پیشینه',
      waiting_minutes: 4,
      status: 'waiting',
    },
  ],
  counterNumber: 1,
  currentCallingTicket: null,
  loading: false,
  callingLoading: false,
  error: null,

  setCounterNumber: (num) => set({ counterNumber: num }),

  fetchQueue: async () => {
    set({ loading: true, error: null });
    try {
      const data = await queueApi.fetchQueue();
      set({ queue: data, loading: false });
    } catch (err) {
      const msg = err instanceof Error ? err.message : 'خطا در بارگذاری صف';
      set({ loading: false, error: msg });
    }
  },

  updateQueueData: (incoming) => {
    set((state) => ({
      queue: state.queue ? { ...state.queue, ...incoming } : (incoming as OfficeQueueData),
    }));
  },

  callNext: async () => {
    const { counterNumber, tickets } = get();
    set({ callingLoading: true });
    try {
      const res = await queueApi.callNextTicket(counterNumber);
      const ticketNum = res.ticket_number || tickets[0]?.ticket_number || 'A-101';
      
      set((state) => ({
        currentCallingTicket: ticketNum,
        callingLoading: false,
        tickets: state.tickets.map((t) =>
          t.ticket_number === ticketNum ? { ...t, status: 'called', counter_number: counterNumber } : t
        ),
        queue: state.queue
          ? {
              ...state.queue,
              waiting_queue: Math.max(0, state.queue.waiting_queue - 1),
            }
          : state.queue,
      }));

      playNotificationSound();
      return ticketNum;
    } catch {
      set({ callingLoading: false });
      return null;
    }
  },

  reset: () => {
    set({
      queue: null,
      counterNumber: 1,
      currentCallingTicket: null,
      loading: false,
      callingLoading: false,
      error: null,
    });
  },
}));
