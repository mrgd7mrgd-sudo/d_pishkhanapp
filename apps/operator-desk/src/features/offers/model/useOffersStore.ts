import { create } from 'zustand';
import { offersApi } from '../api/offersApi';
import { DispatchOfferItem, OfferConflictError } from '../types';
import { playNotificationSound, showDesktopNotification } from '../../../shared/realtime/echo';

interface OffersState {
  offers: DispatchOfferItem[];
  loading: boolean;
  actionLoadingId: string | null;
  conflicts: Record<string, string>;

  // Actions
  fetchOffers: () => Promise<void>;
  addOffer: (offer: DispatchOfferItem) => void;
  removeOffer: (offerId: string) => void;
  tickCounters: () => void;
  acceptOffer: (offerId: string) => Promise<boolean>;
  declineOffer: (offerId: string) => Promise<boolean>;
  clearConflict: (offerId: string) => void;
  reset: () => void;
}

export const useOffersStore = create<OffersState>((set, get) => ({
  offers: [],
  loading: false,
  actionLoadingId: null,
  conflicts: {},

  fetchOffers: async () => {
    set({ loading: true });
    try {
      const offers = await offersApi.fetchActiveOffers();
      set({ offers: Array.isArray(offers) ? offers : [], loading: false });
    } catch {
      set({ offers: [], loading: false });
    }
  },

  addOffer: (offer) => {
    // Deduplicate by ID
    const exists = get().offers.some((o) => o.id === offer.id);
    if (!exists) {
      set((state) => ({ offers: [offer, ...state.offers] }));
      playNotificationSound();
      showDesktopNotification(
        'پیشنهاد پرونده جدید',
        `پرونده جدید با شماره پیگیری ${offer.tracking_code} به دفتر شما پیشنهاد شد.`
      );
    }
  },

  removeOffer: (offerId) => {
    set((state) => {
      const nextConflicts = { ...state.conflicts };
      delete nextConflicts[offerId];
      return {
        offers: state.offers.filter((o) => o.id !== offerId),
        conflicts: nextConflicts,
      };
    });
  },

  tickCounters: () => {
    set((state) => ({
      // Decrement counter, and automatically remove expired offers (remaining <= 0)
      offers: state.offers
        .map((offer) => ({
          ...offer,
          remaining_seconds: Math.max(0, offer.remaining_seconds - 1),
        }))
        .filter((offer) => offer.remaining_seconds > 0),
    }));
  },

  acceptOffer: async (offerId) => {
    set({ actionLoadingId: offerId });
    try {
      await offersApi.acceptOffer(offerId);
      get().removeOffer(offerId);
      set({ actionLoadingId: null });
      return true;
    } catch (err) {
      const message =
        err instanceof OfferConflictError
          ? err.message
          : err instanceof Error
            ? err.message
            : 'خطا در پذیرش پیشنهاد';

      set((state) => ({
        actionLoadingId: null,
        conflicts: {
          ...state.conflicts,
          [offerId]: message,
        },
      }));
      return false;
    }
  },

  declineOffer: async (offerId) => {
    set({ actionLoadingId: offerId });
    try {
      await offersApi.declineOffer(offerId);
      get().removeOffer(offerId);
      set({ actionLoadingId: null });
      return true;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'خطا در رد پیشنهاد';
      set((state) => ({
        actionLoadingId: null,
        conflicts: {
          ...state.conflicts,
          [offerId]: message,
        },
      }));
      return false;
    }
  },

  clearConflict: (offerId) => {
    set((state) => {
      const next = { ...state.conflicts };
      delete next[offerId];
      return { conflicts: next };
    });
  },

  reset: () => {
    set({
      offers: [],
      loading: false,
      actionLoadingId: null,
      conflicts: {},
    });
  },
}));
