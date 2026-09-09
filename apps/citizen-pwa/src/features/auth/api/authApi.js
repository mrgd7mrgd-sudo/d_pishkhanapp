import { getAccessToken } from '../model/tokenStorage';
export class AuthApiError extends Error {
    status;
    code;
    detail;
    errors;
    constructor(status, code, detail, errors) {
        super(detail);
        this.status = status;
        this.code = code;
        this.detail = detail;
        this.errors = errors;
        this.name = 'AuthApiError';
    }
}
const API_PREFIX = '/api/v1';
const handleResponse = async (res) => {
    if (!res.ok) {
        let errorDetail = 'خطایی در ارتباط با سرور رخ داده است';
        let errorCode = 'UNKNOWN_ERROR';
        let validationErrors;
        try {
            const problem = (await res.json());
            if (problem.detail) {
                errorDetail = problem.detail;
            }
            if (problem.code) {
                errorCode = problem.code;
            }
            if (problem.errors) {
                validationErrors = problem.errors;
            }
        }
        catch {
            // Non-JSON error body fallback
        }
        throw new AuthApiError(res.status, errorCode, errorDetail, validationErrors);
    }
    const json = (await res.json());
    return json.data;
};
export const authApi = {
    async requestOtp(payload) {
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
        return handleResponse(res);
    },
    async verifyOtp(payload) {
        const res = await fetch(`${API_PREFIX}/auth/otp/verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(payload),
        });
        return handleResponse(res);
    },
    async refreshToken() {
        const token = getAccessToken();
        const headers = {
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
        return handleResponse(res);
    },
    async logout() {
        const token = getAccessToken();
        const headers = {
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
    async getMe() {
        const token = getAccessToken();
        const headers = {
            Accept: 'application/json',
        };
        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }
        const res = await fetch(`${API_PREFIX}/auth/me`, {
            headers,
            credentials: 'include',
        });
        return handleResponse(res);
    },
};
