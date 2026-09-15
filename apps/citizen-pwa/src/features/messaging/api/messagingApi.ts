import type {
  CaseMessagesResponse,
  CaseMessage,
  SendMessagePayload,
  NotificationsResponse,
  NotificationItem,
} from '../types';

const API_PREFIX = '/api/v1';

export class MessagingApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    public readonly detail: string
  ) {
    super(detail);
    this.name = 'MessagingApiError';
  }
}

const parseError = async (res: Response, defaultMsg: string): Promise<MessagingApiError> => {
  let detail = defaultMsg;
  let code = 'MESSAGING_ERROR';
  try {
    const json = (await res.json()) as { detail?: string; code?: string; message?: string };
    if (json.detail) detail = json.detail;
    else if (json.message) detail = json.message;
    if (json.code) code = json.code;
  } catch {
    // Non-JSON response fallback
  }
  return new MessagingApiError(res.status, code, detail);
};

export const messagingApi = {
  async getCaseMessages(caseId: string): Promise<CaseMessagesResponse> {
    const res = await fetch(`${API_PREFIX}/cases/${caseId}/messages`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw await parseError(res, 'خطا در دریافت پیام‌های پرونده');
    const json = (await res.json()) as CaseMessagesResponse;
    return json;
  },

  async sendCaseMessage(caseId: string, payload: SendMessagePayload): Promise<CaseMessage> {
    const res = await fetch(`${API_PREFIX}/cases/${caseId}/messages`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(payload),
    });
    if (!res.ok) throw await parseError(res, 'خطا در ارسال پیام');
    const json = (await res.json()) as CaseMessage;
    return json;
  },

  async getNotifications(unreadOnly?: boolean): Promise<NotificationsResponse> {
    const params = unreadOnly ? '?unread_only=1' : '';
    const res = await fetch(`${API_PREFIX}/notifications${params}`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw await parseError(res, 'خطا در دریافت اعلان‌ها');
    const json = (await res.json()) as NotificationsResponse;
    return json;
  },

  async markNotificationRead(id: string): Promise<NotificationItem> {
    const res = await fetch(`${API_PREFIX}/notifications/${id}/read`, {
      method: 'PATCH',
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw await parseError(res, 'خطا در علامت‌گذاری اعلان');
    const json = (await res.json()) as NotificationItem;
    return json;
  },
};
