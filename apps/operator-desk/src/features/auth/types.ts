export type OperatorStep = 'credentials' | 'otp_verify';

export interface Operator {
  id: string;
  office_id: string;
  username: string;
  full_name: string;
  national_id_masked?: string | undefined;
  mobile_masked?: string | undefined;
  role: string;
  role_name: string;
  counter_number: number;
  is_active: boolean;
  last_login_at: string | null;
}

export interface OperatorLoginPayload {
  office_code: string;
  username: string;
  password: string;
}

export interface OperatorLoginData {
  challenge_id: string;
  masked_mobile: string;
  expires_at: string;
}

export interface OperatorVerifyOtpPayload {
  challenge_id: string;
  code: string;
}

export interface OperatorVerifyOtpData {
  operator: Operator;
  session_id: string;
}

export interface ApiResponse<T> {
  data: T;
}

export interface ApiProblemDetails {
  type: string;
  title: string;
  status: number;
  code: string;
  detail: string;
  instance: string;
  request_id: string;
  errors?: Record<string, string[]> | undefined;
}
