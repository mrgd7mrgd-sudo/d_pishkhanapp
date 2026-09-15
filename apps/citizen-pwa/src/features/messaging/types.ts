export type MessageSenderType = 'citizen' | 'operator' | 'system';
export type MessageDeliveryStatus = 'sending' | 'sent' | 'pending_offline' | 'failed';

export interface CaseMessage {
  id: string;
  case_id: string;
  sender_type: MessageSenderType;
  sender_id: string;
  sender_name: string;
  body: string;
  attachment_key?: string | null | undefined;
  read_at?: string | null | undefined;
  created_at: string;
  status?: MessageDeliveryStatus | undefined;
}

export interface CaseMessagesResponse {
  case_id: string;
  unread_count: number;
  items: CaseMessage[];
}

export interface SendMessagePayload {
  body: string;
  attachment_key?: string | undefined;
}

export interface NotificationItem {
  id: string;
  type: string;
  title: string;
  body: string;
  payload?: Record<string, unknown> | null | undefined;
  read_at?: string | null | undefined;
  created_at: string;
}

export interface NotificationsResponse {
  unread_count: number;
  items: NotificationItem[];
}
