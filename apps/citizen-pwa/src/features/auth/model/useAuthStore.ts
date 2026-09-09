import { create, type StateCreator } from 'zustand';
import { normalizeDigits, isValidIranianNationalId } from '@pishkhan/domain';
import type { AuthStep, Citizen } from '../types';
import { authApi, AuthApiError } from '../api/authApi';
import {
  setAccessToken,
  clearAccessToken,
  storeSwPat,
  clearSwPat,
} from './tokenStorage';

export interface AuthState {
  step: AuthStep;
  mobile: string;
  nationalId: string;
  challengeId: string | null;
  maskedMobile: string;
  countdown: number;
  resendAvailable: boolean;
  isLoading: boolean;
  error: string | null;
  citizen: Citizen | null;
  isAuthenticated: boolean;

  setStep: (step: AuthStep) => void;
  setMobile: (mobile: string) => void;
  setNationalId: (nationalId: string) => void;
  setError: (error: string | null) => void;
  decrementCountdown: () => void;
  resetTimer: (seconds?: number) => void;

  submitPhone: (phone: string) => boolean;
  submitNationalId: (nationalId: string) => Promise<boolean>;
  requestOtpChallenge: () => Promise<boolean>;
  verifyOtpCode: (code: string) => Promise<boolean>;
  resendOtp: () => Promise<boolean>;
  logout: () => Promise<void>;
  reset: () => void;
}

type SetState = Parameters<StateCreator<AuthState>>[0];
type GetState = Parameters<StateCreator<AuthState>>[1];

const INITIAL_COUNTDOWN = 120;

const initialState = {
  step: 'phone_input' as AuthStep,
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

const createFlowActions = (set: SetState, get: GetState) => ({
  setStep: (step: AuthStep) => set({ step, error: null }),
  setMobile: (raw: string) => set({ mobile: normalizeDigits(raw).trim() }),
  setNationalId: (raw: string) => set({ nationalId: normalizeDigits(raw).trim() }),
  setError: (error: string | null) => set({ error }),

  decrementCountdown: () => {
    const { countdown } = get();
    if (countdown <= 1) {
      set({ countdown: 0, resendAvailable: true });
    } else {
      set({ countdown: countdown - 1 });
    }
  },

  resetTimer: (seconds = INITIAL_COUNTDOWN) => {
    set({ countdown: seconds, resendAvailable: false });
  },

  submitPhone: (phoneInput: string) => {
    const normalized = normalizeDigits(phoneInput).trim();
    if (!/^09\d{9}$/.test(normalized)) {
      set({ error: 'invalid_phone' });
      return false;
    }
    set({ mobile: normalized, step: 'national_id_input', error: null });
    return true;
  },

  submitNationalId: async (idInput: string) => {
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

const executeRequestOtp = async (set: SetState, get: GetState): Promise<boolean> => {
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
  } catch (err) {
    const msg = err instanceof AuthApiError ? err.detail : 'خطایی در ارسال کد تأیید رخ داد';
    set({ error: msg, isLoading: false });
    return false;
  }
};

const executeVerifyOtp = async (
  codeInput: string,
  set: SetState,
  get: GetState
): Promise<boolean> => {
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
  } catch (err) {
    const msg = err instanceof AuthApiError ? err.detail : 'کد وارد شده نامعتبر یا منقضی است';
    set({ error: msg, isLoading: false });
    return false;
  }
};

const executeLogout = async (set: SetState): Promise<void> => {
  set({ isLoading: true });
  try {
    await authApi.logout();
  } catch {
    // Ignore network error on logout
  } finally {
    clearAccessToken();
    await clearSwPat();
    set(initialState);
  }
};

const createApiActions = (set: SetState, get: GetState) => ({
  requestOtpChallenge: () => executeRequestOtp(set, get),
  verifyOtpCode: (code: string) => executeVerifyOtp(code, set, get),
  resendOtp: async () => {
    if (!get().resendAvailable) {
      return false;
    }
    return executeRequestOtp(set, get);
  },
  logout: () => executeLogout(set),
});

export const useAuthStore = create<AuthState>((set, get) => ({
  ...initialState,
  ...createFlowActions(set, get),
  ...createApiActions(set, get),
}));
