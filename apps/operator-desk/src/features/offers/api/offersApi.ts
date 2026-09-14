import {
  AcceptOfferResponse,
  DeclineOfferResponse,
  DispatchOfferItem,
  OfferConflictError,
} from '../types';

const API_PREFIX = '/api/v1';

export const offersApi = {
  async fetchActiveOffers(): Promise<DispatchOfferItem[]> {
    const res = await fetch(`${API_PREFIX}/desk/offers`, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
      },
    });

    if (!res.ok) {
      throw new Error(`Failed to fetch offers: ${res.status}`);
    }

    const json = (await res.json()) as { data: DispatchOfferItem[] };
    return json.data;
  },

  async acceptOffer(offerId: string): Promise<AcceptOfferResponse> {
    const res = await fetch(`${API_PREFIX}/offers/${offerId}/accept`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    });

    if (res.status === 409) {
      throw new OfferConflictError('این پرونده را دفتر دیگری پذیرفت');
    }

    if (!res.ok) {
      const errorJson = (await res.json().catch(() => ({}))) as { detail?: string };
      throw new Error(errorJson.detail || 'خطا در پذیرش پیشنهاد پرونده.');
    }

    return (await res.json()) as AcceptOfferResponse;
  },

  async declineOffer(offerId: string): Promise<DeclineOfferResponse> {
    const res = await fetch(`${API_PREFIX}/offers/${offerId}/decline`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    });

    if (!res.ok) {
      const errorJson = (await res.json().catch(() => ({}))) as { detail?: string };
      throw new Error(errorJson.detail || 'خطا در رد پیشنهاد پرونده.');
    }

    return (await res.json()) as DeclineOfferResponse;
  },
};
