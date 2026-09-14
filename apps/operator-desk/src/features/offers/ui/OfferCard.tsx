import React from 'react';
import { DispatchOfferItem } from '../types';
import { OfferCountdown } from './OfferCountdown';
import { CheckCircle2, XCircle, AlertTriangle, Loader2 } from 'lucide-react';

interface OfferCardProps {
  offer: DispatchOfferItem;
  isLoading: boolean;
  conflict?: string;
  onAccept: (id: string) => void;
  onDecline: (id: string) => void;
  onDismissConflict: (id: string) => void;
}

export const OfferCard: React.FC<OfferCardProps> = ({
  offer,
  isLoading,
  conflict,
  onAccept,
  onDecline,
  onDismissConflict,
}) => {
  return (
    <article
      className="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden"
      aria-labelledby={`offer-title-${offer.id}`}
    >
      {/* Top accent line */}
      <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-600" />

      {/* Header */}
      <div className="flex items-start justify-between gap-2 mb-3">
        <div>
          <div className="flex items-center gap-2 mb-1">
            <span className="text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
              دور {offer.round.toString().replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[parseInt(d, 10)])}
            </span>
            <span
              id={`offer-title-${offer.id}`}
              className="text-sm font-bold text-gray-900 tracking-wide font-mono"
            >
              {offer.tracking_code}
            </span>
          </div>
          <h3 className="text-sm text-gray-700 font-medium">
            {offer.service_title || 'خدمت الکترونیک پیشخوان'}
          </h3>
          {(offer.city || offer.province_code) && (
            <p className="text-xs text-gray-400 mt-0.5">
              موقعیت متقاضی: {offer.city ? `${offer.city}، ` : ''}{offer.province_code || 'تهران'}
            </p>
          )}
        </div>

        {/* Live Countdown */}
        <OfferCountdown remainingSeconds={offer.remaining_seconds} totalSeconds={90} />
      </div>

      {/* Conflict / Error Banner (§5.7, TASK-077) */}
      {conflict && (
        <div
          role="alert"
          className="mb-3 p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center justify-between gap-2"
        >
          <div className="flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0" aria-hidden="true" />
            <span>{conflict}</span>
          </div>
          <button
            type="button"
            onClick={() => onDismissConflict(offer.id)}
            className="text-amber-700 hover:text-amber-900 text-xs font-bold underline px-1"
          >
            بستن
          </button>
        </div>
      )}

      {/* Action Buttons */}
      <div className="flex items-center gap-2 pt-2 border-t border-gray-100">
        <button
          type="button"
          disabled={isLoading}
          onClick={() => onAccept(offer.id)}
          className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
        >
          {isLoading ? (
            <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
          ) : (
            <CheckCircle2 className="w-4 h-4" aria-hidden="true" />
          )}
          <span>پذیرش پرونده</span>
        </button>

        <button
          type="button"
          disabled={isLoading}
          onClick={() => onDecline(offer.id)}
          className="inline-flex items-center justify-center gap-1 px-3 py-2 text-sm font-medium rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <XCircle className="w-4 h-4 text-gray-500" aria-hidden="true" />
          <span>رد</span>
        </button>
      </div>
    </article>
  );
};
