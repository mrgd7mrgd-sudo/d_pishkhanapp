import type {
  ApiResponse,
  ApiProblemDetails,
  OtpRequestData,
  OtpRequestPayload,
  OtpVerifyData,
  OtpVerifyPayload,
  RefreshTokenData,
  Citizen,
} from '../types';
import { getAccessToken } from '../model/tokenStorage';

export class AuthApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    public readonly detail: string,
    public readonly errors?: Record<string, string[]>
  ) {
    super(detail);
    this.name = 'AuthApiError';
  }
}

const API_PREFIX = '/api/v1';

const handleResponse = async <T>(res: Response): Promise<T> => {
  if (!res.ok) {
    let errorDetail = 'خطایی در ارتباط با سرور رخ داده است';
    let errorCode = 'UNKNOWN_ERROR';
    let validationErrors: Record<string, string[]> | undefined;

    try {
      const problem = (await res.json()) as ApiProblemDetails;
      if (problem.detail) {
        errorDetail = problem.detail;
      }
      if (problem.code) {
        errorCode = problem.code;
      }
      if (problem.errors) {
        validationErrors = problem.errors;
      }
    } catch {
      // Non-JSON error body fallback
    }

    throw new AuthApiError(res.status, errorCode, errorDetail, validationErrors);
  }

  const json = (await res.json()) as ApiResponse<T>;
  return json.data;
};

export const authApi = {
  async requestOtp(payload: OtpRequestPayload): Promise<OtpRequestData> {
    const res = await fetch(`${API_PREFIX}/auth/otp/request`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        mobile: payload.mobile,
        national_id: payload.national_id || undefined,
        purpose: payload.purpose ?? 'citizen_login',
      }),
    });

    return handleResponse<OtpRequestData>(res);
  },

  async verifyOtp(payload: OtpVerifyPayload): Promise<OtpVerifyData> {
    const res = await fetch(`${API_PREFIX}/auth/otp/verify`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify(payload),
    });

    return handleResponse<OtpVerifyData>(res);
  },

  async refreshToken(): Promise<RefreshTokenData> {
    const token = getAccessToken();
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    };

    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    const res = await fetch(`${API_PREFIX}/auth/refresh`, {
      method: 'POST',
      headers,
      credentials: 'include',
    });

    return handleResponse<RefreshTokenData>(res);
  },

  async logout(): Promise<void> {
    const token = getAccessToken();
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    };

    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    await fetch(`${API_PREFIX}/auth/logout`, {
      method: 'POST',
      headers,
      credentials: 'include',
    });
  },

  async getMe(): Promise<Citizen> {
    const token = getAccessToken();
    const headers: Record<string, string> = {
      Accept: 'application/json',
    };

    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    const res = await fetch(`${API_PREFIX}/auth/me`, {
      headers,
      credentials: 'include',
    });

    return handleResponse<Citizen>(res);
  },
};
