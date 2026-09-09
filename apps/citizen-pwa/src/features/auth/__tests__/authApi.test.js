import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { authApi, AuthApiError } from '../api/authApi';
import { setAccessToken, clearAccessToken } from '../model/tokenStorage';
describe('Auth API Client (§5.6, §7.2)', () => {
    const mockFetch = vi.fn();
    beforeEach(() => {
        vi.stubGlobal('fetch', mockFetch);
        clearAccessToken();
        vi.restoreAllMocks();
    });
    afterEach(() => {
        vi.unstubAllGlobals();
    });
    it('calls requestOtp and returns response data', async () => {
        mockFetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                data: {
                    challenge_id: 'ch_1',
                    expires_at: '2026-09-09T14:00:00Z',
                    masked_mobile: '۰۹۱۲***۶۷۸۹',
                },
            }),
        });
        const res = await authApi.requestOtp({ mobile: '09123456789', national_id: '0010350802' });
        expect(res.challenge_id).toBe('ch_1');
        expect(res.masked_mobile).toBe('۰۹۱۲***۶۷۸۹');
        expect(mockFetch).toHaveBeenCalledWith('/api/v1/auth/otp/request', expect.objectContaining({
            method: 'POST',
        }));
    });
    it('throws AuthApiError with problem details on failed response', async () => {
        mockFetch.mockResolvedValueOnce({
            ok: false,
            status: 422,
            json: async () => ({
                title: 'اعتبارسنجی ناموفق',
                status: 422,
                code: 'VALIDATION_FAILED',
                detail: 'شماره تلفن همراه نامعتبر است',
                instance: '/auth/otp/request',
                request_id: 'req_123',
                errors: { mobile: ['فرمت شماره همراه نامعتبر است'] },
            }),
        });
        await expect(authApi.requestOtp({ mobile: '123' })).rejects.toThrow(AuthApiError);
    });
    it('handles non-JSON error responses gracefully', async () => {
        mockFetch.mockResolvedValueOnce({
            ok: false,
            status: 502,
            json: async () => {
                throw new Error('Unexpected token < in JSON');
            },
        });
        await expect(authApi.verifyOtp({ challenge_id: 'ch_1', code: '12345' })).rejects.toThrow('خطایی در ارتباط با سرور رخ داده است');
    });
    it('calls refreshToken with Bearer token if token exists in memory', async () => {
        setAccessToken('existing_access_token');
        mockFetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                data: {
                    token: 'sw_new_token',
                    token_type: 'Bearer',
                    expires_at: '2026-09-09T14:15:00Z',
                    ttl_seconds: 900,
                },
            }),
        });
        const res = await authApi.refreshToken();
        expect(res.token).toBe('sw_new_token');
        expect(mockFetch).toHaveBeenCalledWith('/api/v1/auth/refresh', expect.objectContaining({
            headers: expect.objectContaining({
                Authorization: 'Bearer existing_access_token',
            }),
        }));
    });
    it('calls logout with in-memory Bearer token', async () => {
        setAccessToken('token_to_logout');
        mockFetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({ data: { success: true } }),
        });
        await authApi.logout();
        expect(mockFetch).toHaveBeenCalledWith('/api/v1/auth/logout', expect.objectContaining({
            method: 'POST',
        }));
    });
    it('calls getMe and returns citizen profile', async () => {
        setAccessToken('bearer_token');
        mockFetch.mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                data: {
                    id: 'cit_1',
                    mobile: '09123456789',
                    tier: 'silver',
                },
            }),
        });
        const citizen = await authApi.getMe();
        expect(citizen.id).toBe('cit_1');
        expect(citizen.tier).toBe('silver');
    });
});
