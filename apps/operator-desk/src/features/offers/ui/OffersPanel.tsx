import React, { useEffect } from 'react';
import { useOffersStore } from '../model/useOffersStore';
import { OfferCard } from './OfferCard';
import { realtimeManager } from '../../../shared/realtime/echo';
import { Bell, Inbox } from 'lucide-react';
import { DispatchOfferItem } from '../types';

interface OffersPanelProps {
  officeId?: string;
  autoFetch?: boolean;
}

export const OffersPanel: React.FC<OffersPanelProps> = ({ officeId, autoFetch = true }) => {
  const {
    offers,
    loading,
    actionLoadingId,
    conflicts,
    fetchOffers,
    addOffer,
    removeOffer,
    tickCounters,
    acceptOffer,
    declineOffer,
    clearConflict,
  } = useOffersStore();

  // Initial fetch on mount
  useEffect(() => {
    if (autoFetch) {
      void fetchOffers();
    }
  }, [autoFetch, fetchOffers]);

  // Live countdown timer ticking every 1 second
  useEffect(() => {
    const timer = setInterval(() => {
      tickCounters();
    }, 1000);

    return () => clearInterval(timer);
  }, [tickCounters]);

  // Realtime subscription to private-office.{id}
  useEffect(() => {
    if (!officeId) return;

    const channelName = `office.${officeId}`;
    const channel = realtimeManager.private(channelName);

    const handleNewOffer = (payload: unknown) => {
      const data = (payload as { offer?: DispatchOfferItem } | DispatchOfferItem);
      const offer = 'offer' in data && data.offer ? data.offer : (data as DispatchOfferItem);
      if (offer && offer.id) {
        addOffer(offer);
      }
    };

    const handleOfferTaken = (payload: unknown) => {
      const data = payload as { offer_id?: string; id?: string };
      const id = data.offer_id || data.id;
      if (id) {
        removeOffer(id);
      }
    };

    const handleOfferExpired = (payload: unknown) => {
      const data = payload as { offer_id?: string; id?: string };
      const id = data.offer_id || data.id;
      if (id) {
        removeOffer(id);
      }
    };

    channel.listen('.offer.new', handleNewOffer);
    channel.listen('offer.new', handleNewOffer);
    channel.listen('.offer.taken', handleOfferTaken);
    channel.listen('offer.taken', handleOfferTaken);
    channel.listen('.offer.expired', handleOfferExpired);
    channel.listen('offer.expired', handleOfferExpired);

    return () => {
      channel.stopListening('offer.new');
      channel.stopListening('.offer.new');
      channel.stopListening('offer.taken');
      channel.stopListening('.offer.taken');
      channel.stopListening('offer.expired');
      channel.stopListening('.offer.expired');
      realtimeManager.leave(channelName);
    };
  }, [officeId, addOffer, removeOffer]);

  return (
    <section
      className="bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-sm"
      aria-labelledby="offers-panel-title"
      role="region"
    >
      {/* Panel Header */}
      <div className="flex items-center justify-between gap-2 mb-4 pb-3 border-b border-gray-200">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-700">
            <Bell className="w-4 h-4" aria-hidden="true" />
          </div>
          <div>
            <h2 id="offers-panel-title" className="text-sm font-bold text-gray-900">
              پیشنهادهای ورودی پرونده
            </h2>
            <p className="text-xs text-gray-500">
              فرصت رقابتی ۹۰ ثانیه‌ای برای پذیرش پرونده‌های شهروندان
            </p>
          </div>
        </div>

        <span
          className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${
            offers.length > 0
              ? 'bg-blue-600 text-white animate-pulse'
              : 'bg-gray-200 text-gray-700'
          }`}
        >
          {offers.length.toString().replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[parseInt(d, 10)])} پیشنهاد فعال
        </span>
      </div>

      {/* Loading state */}
      {loading && offers.length === 0 && (
        <div className="py-8 text-center text-sm text-gray-500">
          در حال بارگذاری پیشنهادها...
        </div>
      )}

      {/* Empty state */}
      {!loading && offers.length === 0 && (
        <div className="py-8 px-4 text-center border-2 border-dashed border-gray-200 rounded-lg">
          <Inbox className="w-8 h-8 mx-auto text-gray-400 mb-2" aria-hidden="true" />
          <p className="text-sm text-gray-600 font-medium">پیشنهاد فعالی در این لحظه وجود ندارد.</p>
          <p className="text-xs text-gray-400 mt-1">
            با ارسال درخواست از سوی شهروندان، کارت‌های پیشنهاد بلافاصله در اینجا نمایش داده می‌شوند.
          </p>
        </div>
      )}

      {/* Offers Cards Grid */}
      <div className="space-y-3" aria-live="polite">
        {(Array.isArray(offers) ? offers : []).map((offer) => (
          <OfferCard
            key={offer.id}
            offer={offer}
            isLoading={actionLoadingId === offer.id}
            conflict={conflicts[offer.id]}
            onAccept={(id) => void acceptOffer(id)}
            onDecline={(id) => void declineOffer(id)}
            onDismissConflict={clearConflict}
          />
        ))}
      </div>
    </section>
  );
};
