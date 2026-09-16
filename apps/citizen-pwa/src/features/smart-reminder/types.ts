export type ReminderChannel = 'sms' | 'push' | 'both';

export interface SmartReminderItem {
  id: string;
  title: string;
  description: string;
  due_date: string;
  due_time?: string | undefined;
  channel: ReminderChannel;
  lead_time_minutes: number;
  is_enabled: boolean;
  required_documents: string[];
}
