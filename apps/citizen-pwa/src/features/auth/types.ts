export type AuthStep = 'phone_input' | 'national_id_input' | 'otp_verify';

export interface Citizen {
  id: string;
  mobile: string;
  national_id_masked?: string;
  full_name?: string;
  tier: string;
  created_at?: string;
}

export interface OtpRequestPayload {
  mobile: string;
  national_id?: string | undefined;
  purpose?: string | undefined;
}

export interface OtpRequestData {
  challenge_id: string;
  expires_at: string;
  masked_mobile: string;
}

export interface OtpVerifyPayload {
  challenge_id: string;
  code: string;
  device_name?: string | undefined;
}

export interface OtpVerifyData {
  token: string;
  token_type: string;
  expires_at: string;
  citizen: Citizen;
  abilities: string[];
}

export interface RefreshTokenData {
  token: string;
  token_type: string;
  expires_at: string;
  ttl_seconds: number;
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
  errors?: Record<string, string[]>;
}
