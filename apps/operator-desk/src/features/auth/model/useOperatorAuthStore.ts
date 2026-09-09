import { create, type StateCreator } from 'zustand';
import { normalizeDigits } from '@pishkhan/domain';
import type { Operator, OperatorStep } from '../types';
import { operatorAuthApi, OperatorAuthApiError } from '../api/authApi';

export interface OperatorAuthState {
  step: OperatorStep;
  officeCode: string;
  username: string;
  password: string;
  challengeId: string | null;
  maskedMobile: string;
  countdown: number;
  resendAvailable: boolean;
  isLoading: boolean;
  error: string | null;
  operator: Operator | null;
  isAuthenticated: boolean;

  setStep: (step: OperatorStep) => void;
  setOfficeCode: (officeCode: string) => void;
  setUsername: (username: string) => void;
  setPassword: (password: string) => void;
  setError: (error: string | null) => void;
  decrementCountdown: () => void;

  submitCredentials: (officeCode: string, user: string, pass: string) => Promise<boolean>;
  verifyOtpCode: (code: string) => Promise<boolean>;
  resendOtp: () => Promise<boolean>;
  logout: () => Promise<void>;
  reset: () => void;
}

type SetState = Parameters<StateCreator<OperatorAuthState>>[0];
type GetState = Parameters<StateCreator<OperatorAuthState>>[1];

const INITIAL_COUNTDOWN = 120;

const initialState = {
  step: 'credentials' as OperatorStep,
  officeCode: '',
  username: '',
  password: '',
  challengeId: null,
  maskedMobile: '',
  countdown: INITIAL_COUNTDOWN,
  resendAvailable: false,
  isLoading: false,
  error: null,
  operator: null,
  isAuthenticated: false,
};

const createFlowActions = (set: SetState, get: GetState) => ({
  setStep: (step: OperatorStep) => set({ step, error: null }),
  setOfficeCode: (raw: string) => set({ officeCode: normalizeDigits(raw).trim() }),
  setUsername: (username: string) => set({ username: username.trim() }),
  setPassword: (password: string) => set({ password }),
  setError: (error: string | null) => set({ error }),

  decrementCountdown: () => {
    const { countdown } = get();
    if (countdown <= 1) {
      set({ countdown: 0, resendAvailable: true });
    } else {
      set({ countdown: countdown - 1 });
    }
  },

  reset: () => set(initialState),
});

const executeLogin = async (
  officeCode: string,
  user: string,
  pass: string,
  set: SetState
): Promise<boolean> => {
  const normCode = normalizeDigits(officeCode).trim();
  const normUser = user.trim();

  if (!normCode || !normUser || !pass) {
    set({ error: 'invalid_credentials' });
    return false;
  }

  set({ isLoading: true, error: null });
  try {
    const data = await operatorAuthApi.login({
      office_code: normCode,
      username: normUser,
      password: pass,
    });
    set({
      officeCode: normCode,
      username: normUser,
      password: pass,
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
    const msg = err instanceof OperatorAuthApiError ? err.detail : 'اطلاعات ورود نامعتبر است';
    set({ error: msg, isLoading: false });
    return false;
  }
};

const executeVerify = async (
  code: string,
  set: SetState,
  get: GetState
): Promise<boolean> => {
  const { challengeId } = get();
  const normalizedCode = normalizeDigits(code).replace(/\D/g, '');

  if (normalizedCode.length !== 5) {
    set({ error: 'invalid_otp' });
    return false;
  }
  if (!challengeId) {
    set({ error: 'نشست ورود نامعتبر است' });
    return false;
  }

  set({ isLoading: true, error: null });
  try {
    const data = await operatorAuthApi.verifyOtp({
      challenge_id: challengeId,
      code: normalizedCode,
    });
    set({
      operator: data.operator,
      isAuthenticated: true,
      isLoading: false,
      error: null,
    });
    return true;
  } catch (err) {
    const msg = err instanceof OperatorAuthApiError ? err.detail : 'کد تأیید نامعتبر یا منقضی است';
    set({ error: msg, isLoading: false });
    return false;
  }
};

const executeLogout = async (set: SetState): Promise<void> => {
  set({ isLoading: true });
  try {
    await operatorAuthApi.logout();
  } catch {
    // Graceful error ignore on logout
  } finally {
    set(initialState);
  }
};

const createApiActions = (set: SetState, get: GetState) => ({
  submitCredentials: (officeCode: string, user: string, pass: string) =>
    executeLogin(officeCode, user, pass, set),

  verifyOtpCode: (code: string) => executeVerify(code, set, get),

  resendOtp: async () => {
    const { officeCode, username, password, resendAvailable } = get();
    if (!resendAvailable) {
      return false;
    }
    return executeLogin(officeCode, username, password, set);
  },

  logout: () => executeLogout(set),
});

export const useOperatorAuthStore = create<OperatorAuthState>((set, get) => ({
  ...initialState,
  ...createFlowActions(set, get),
  ...createApiActions(set, get),
}));
