import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { operatorAuthApi, OperatorAuthApiError } from '../api/authApi';

describe('Operator Auth API Client (§4.4, §5.6, §7.2)', () => {
  const mockFetch = vi.fn();

  beforeEach(() => {
    vi.stubGlobal('fetch', mockFetch);
    vi.restoreAllMocks();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('calls login with credentials and credentials: include', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        data: {
          challenge_id: 'ch_op_123',
          masked_mobile: '۰۹۱۲***۶۷۸۹',
          expires_at: '2026-09-09T15:00:00Z',
        },
      }),
    });

    const res = await operatorAuthApi.login({
      office_code: '72161001',
      username: 'operator1',
      password: 'SecurePassword123!',
    });

    expect(res.challenge_id).toBe('ch_op_123');
    expect(res.masked_mobile).toBe('۰۹۱۲***۶۷۸۹');
    expect(mockFetch).toHaveBeenCalledWith(
      '/api/v1/operator/auth/login',
      expect.objectContaining({
        method: 'POST',
        credentials: 'include',
        body: JSON.stringify({
          office_code: '72161001',
          username: 'operator1',
          password: 'SecurePassword123!',
        }),
      })
    );
  });

  it('calls verifyOtp with challenge_id, code, and credentials: include', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        data: {
          operator: {
            id: 'op_uuid_1',
            office_id: '72161001',
            username: 'operator1',
            full_name: 'رضا محمدی',
            role: 'counter_operator',
            role_name: 'متصدی باجه',
            counter_number: 3,
            is_active: true,
            last_login_at: '2026-09-09T14:00:00Z',
          },
          session_id: 'sess_123',
        },
      }),
    });

    const res = await operatorAuthApi.verifyOtp({
      challenge_id: 'ch_op_123',
      code: '12345',
    });

    expect(res.operator.full_name).toBe('رضا محمدی');
    expect(res.operator.counter_number).toBe(3);
    expect(mockFetch).toHaveBeenCalledWith(
      '/api/v1/operator/auth/verify-otp',
      expect.objectContaining({
        method: 'POST',
        credentials: 'include',
        body: JSON.stringify({
          challenge_id: 'ch_op_123',
          code: '12345',
        }),
      })
    );
  });

  it('throws OperatorAuthApiError with problem details on error response', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 401,
      json: async () => ({
        title: 'احراز هویت ناموفق',
        status: 401,
        code: 'AUTH_FAILED',
        detail: 'کد دفتر، نام کاربری یا رمز عبور اشتباه است',
        instance: '/operator/auth/login',
        request_id: 'req_err_1',
        errors: { username: ['نام کاربری نامعتبر است'] },
      }),
    });

    await expect(
      operatorAuthApi.login({
        office_code: '00000000',
        username: 'wrong',
        password: 'wrong',
      })
    ).rejects.toThrow(OperatorAuthApiError);
  });

  it('handles non-JSON error responses gracefully', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 503,
      json: async () => {
        throw new Error('Service Unavailable HTML response');
      },
    });

    await expect(
      operatorAuthApi.verifyOtp({ challenge_id: 'ch_1', code: '12345' })
    ).rejects.toThrow('خطایی در ارتباط با سرور رخ داده است');
  });

  it('calls logout with credentials: include', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: { success: true } }),
    });

    await operatorAuthApi.logout();
    expect(mockFetch).toHaveBeenCalledWith(
      '/api/v1/operator/auth/logout',
      expect.objectContaining({
        method: 'POST',
        credentials: 'include',
      })
    );
  });

  it('calls getMe and returns current operator profile', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        data: {
          id: 'op_uuid_1',
          office_id: '72161001',
          username: 'operator1',
          full_name: 'رضا محمدی',
          role: 'counter_operator',
          role_name: 'متصدی باجه',
          counter_number: 3,
          is_active: true,
          last_login_at: null,
        },
      }),
    });

    const op = await operatorAuthApi.getMe();
    expect(op.id).toBe('op_uuid_1');
    expect(op.counter_number).toBe(3);
    expect(mockFetch).toHaveBeenCalledWith(
      '/api/v1/operator/auth/me',
      expect.objectContaining({
        credentials: 'include',
      })
    );
  });
});
