export type AppointmentStatus = 'scheduled' | 'attended' | 'cancelled' | 'expired';

export type AppointmentAttendance = 'pending' | 'attended' | 'absent';

export type AppointmentCompletion = 'pending' | 'in_progress' | 'completed' | 'not_completed';

export type AppointmentReminderType = 'sms' | 'push' | 'both' | 'none';

export interface OfficeSlot {
  time_slot: string;
  start_time: string;
  end_time: string;
  capacity: number;
  booked_count: number;
  is_available: boolean;
}

export interface OfficeSlotsResponse {
  office_id: string;
  office_name: string;
  date: string;
  is_open: boolean;
  capacity_per_slot: number;
  slots: OfficeSlot[];
}

export interface AppointmentItem {
  id: string;
  citizen_id: string;
  citizen_name?: string | null | undefined;
  office_id: string;
  office_name?: string | null | undefined;
  office_code?: string | null | undefined;
  office_address?: string | null | undefined;
  service_id: string;
  service_title?: string | null | undefined;
  service_category?: string | null | undefined;
  appointment_date: string;
  time_slot: string;
  tracking_code: string;
  status: AppointmentStatus;
  status_label: string;
  attendance: AppointmentAttendance;
  attendance_label: string;
  completion: AppointmentCompletion;
  completion_label: string;
  completion_reason?: string | null | undefined;
  queue_number: string;
  ticket_number: string;
  counter_number: number;
  reminder_enabled: boolean;
  reminder_type: string;
  reminder_at?: string | null | undefined;
  cancelled_at?: string | null | undefined;
  cancellation_reason?: string | null | undefined;
  created_at: string;
}

export interface BookAppointmentPayload {
  office_id: string;
  service_id: string;
  appointment_date: string;
  time_slot: string;
  reminder_type?: AppointmentReminderType | undefined;
  reminder_enabled?: boolean | undefined;
}
