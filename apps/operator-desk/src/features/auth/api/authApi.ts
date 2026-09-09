import type {
  ApiResponse,
  ApiProblemDetails,
  Operator,
  OperatorLoginData,
  OperatorLoginPayload,
  OperatorVerifyOtpData,
  OperatorVerifyOtpPayload,
} from '../types';

export class OperatorAuthApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    public readonly detail: string,
    public readonly errors?: Record<string, string[]> | undefined
  ) {
    super(detail);
    this.name = 'OperatorAuthApiError';
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
      // Non-JSON fallback
    }

    throw new OperatorAuthApiError(res.status, errorCode, errorDetail, validationErrors);
  }

  const json = (await res.json()) as ApiResponse<T>;
  return json.data;
};

export const operatorAuthApi = {
  async login(payload: OperatorLoginPayload): Promise<OperatorLoginData> {
    const res = await fetch(`${API_PREFIX}/operator/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify(payload),
    });

    return handleResponse<OperatorLoginData>(res);
  },

  async verifyOtp(payload: OperatorVerifyOtpPayload): Promise<OperatorVerifyOtpData> {
    const res = await fetch(`${API_PREFIX}/operator/auth/verify-otp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify(payload),
    });

    return handleResponse<OperatorVerifyOtpData>(res);
  },

  async logout(): Promise<void> {
    await fetch(`${API_PREFIX}/operator/auth/logout`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
      },
      credentials: 'include',
    });
  },

  async getMe(): Promise<Operator> {
    const res = await fetch(`${API_PREFIX}/operator/auth/me`, {
      headers: {
        Accept: 'application/json',
      },
      credentials: 'include',
    });

    return handleResponse<Operator>(res);
  },
};
