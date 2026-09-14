export interface OfficeQueueData {
  office_id: string;
  waiting_queue: number;
  active_counters: number;
  estimated_wait_minutes: number;
  updated_at: string;
}

export interface QueueTicket {
  ticket_number: string;
  tracking_code: string;
  service_title: string;
  citizen_name?: string | undefined;
  waiting_minutes: number;
  counter_number?: number | undefined;
  status: 'waiting' | 'called' | 'in_service' | 'completed';
}
