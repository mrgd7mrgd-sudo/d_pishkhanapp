import { create } from 'zustand';
import { normalizeDigits, isValidIranianNationalId } from '@pishkhan/domain';
import { authApi, AuthApiError } from '../api/authApi';
import { setAccessToken, clearAccessToken, storeSwPat, clearSwPat, } from './tokenStorage';
const INITIAL_COUNTDOWN = 120;
const initialState = {
    step: 'phone_input',
    mobile: '',
    nationalId: '',
    challengeId: null,
    maskedMobile: '',
    countdown: INITIAL_COUNTDOWN,
    resendAvailable: false,
    isLoading: false,
    error: null,
    citizen: null,
    isAuthenticated: false,
};
const createFlowActions = (set, get) => ({
    setStep: (step) => set({ step, error: null }),
    setMobile: (raw) => set({ mobile: normalizeDigits(raw).trim() }),
    setNationalId: (raw) => set({ nationalId: normalizeDigits(raw).trim() }),
    setError: (error) => set({ error }),
    decrementCountdown: () => {
        const { countdown } = get();
        if (countdown <= 1) {
            set({ countdown: 0, resendAvailable: true });
        }
        else {
            set({ countdown: countdown - 1 });
        }
    },
    resetTimer: (seconds = INITIAL_COUNTDOWN) => {
        set({ countdown: seconds, resendAvailable: false });
    },
    submitPhone: (phoneInput) => {
        const normalized = normalizeDigits(phoneInput).trim();
        if (!/^09\d{9}$/.test(normalized)) {
            set({ error: 'invalid_phone' });
            return false;
        }
        set({ mobile: normalized, step: 'national_id_input', error: null });
        return true;
    },
    submitNationalId: async (idInput) => {
        const normalized = normalizeDigits(idInput).trim();
        if (!isValidIranianNationalId(normalized)) {
            set({ error: 'invalid_national_id' });
            return false;
        }
        set({ nationalId: normalized, error: null });
        return get().requestOtpChallenge();
    },
    reset: () => set(initialState),
});
const executeRequestOtp = async (set, get) => {
    const { mobile, nationalId } = get();
    set({ isLoading: true, error: null });
    try {
        const data = await authApi.requestOtp({
            mobile,
            national_id: nationalId || undefined,
            purpose: 'citizen_login',
        });
        set({
            challengeId: data.challenge_id,
            maskedMobile: data.masked_mobile,
            countdown: INITIAL_COUNTDOWN,
            resendAvailable: false,
            step: 'otp_verify',
            isLoading: false,
            error: null,
        });
        return true;
    }
    catch (err) {
        const msg = err instanceof AuthApiError ? err.detail : 'خطایی در ارسال کد تأیید رخ داد';
        set({ error: msg, isLoading: false });
        return false;
    }
};
const executeVerifyOtp = async (codeInput, set, get) => {
    const { challengeId } = get();
    const normalizedCode = normalizeDigits(codeInput).replace(/\D/g, '');
    if (normalizedCode.length !== 5) {
        set({ error: 'invalid_otp' });
        return false;
    }
    if (!challengeId) {
        set({ error: 'جلسه احراز هویت نامعتبر است' });
        return false;
    }
    set({ isLoading: true, error: null });
    try {
        const data = await authApi.verifyOtp({ challenge_id: challengeId, code: normalizedCode });
        setAccessToken(data.token);
        void authApi.refreshToken().then((r) => {
            void storeSwPat(r.token, r.expires_at, r.ttl_seconds);
        }).catch(() => undefined);
        set({ citizen: data.citizen, isAuthenticated: true, isLoading: false, error: null });
        return true;
    }
    catch (err) {
        const msg = err instanceof AuthApiError ? err.detail : 'کد وارد شده نامعتبر یا منقضی است';
        set({ error: msg, isLoading: false });
        return false;
    }
};
const executeLogout = async (set) => {
    set({ isLoading: true });
    try {
        await authApi.logout();
    }
    catch {
        // Ignore network error on logout
    }
    finally {
        clearAccessToken();
        await clearSwPat();
        set(initialState);
    }
};
const createApiActions = (set, get) => ({
    requestOtpChallenge: () => executeRequestOtp(set, get),
    verifyOtpCode: (code) => executeVerifyOtp(code, set, get),
    resendOtp: async () => {
        if (!get().resendAvailable) {
            return false;
        }
        return executeRequestOtp(set, get);
    },
    logout: () => executeLogout(set),
});
export const useAuthStore = create((set, get) => ({
    ...initialState,
    ...createFlowActions(set, get),
    ...createApiActions(set, get),
}));
