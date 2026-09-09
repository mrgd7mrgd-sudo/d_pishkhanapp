import React from 'react';
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import { AuthFlow } from '../ui/AuthFlow';
import { useAuthStore } from '../model/useAuthStore';
import { getAccessToken, setAccessToken, clearAccessToken } from '../model/tokenStorage';

describe('Citizen Auth Slice & 3-Step Flow (§4.1, §4.9, §7.2, D-12)', () => {
  const mockFetch = vi.fn();

  beforeEach(() => {
    vi.stubGlobal('fetch', mockFetch);
    clearAccessToken();
    useAuthStore.getState().reset();
    vi.restoreAllMocks();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
  });

  it('completes the full 3-step flow with Persian digit normalization and logs in', async () => {
    const localStorageSpy = vi.spyOn(Storage.prototype, 'setItem');

    mockFetch.mockImplementation(async (url: string) => {
      if (url.includes('/auth/otp/request')) {
        return {
          ok: true,
          json: async () => ({
            data: {
              challenge_id: 'test_challenge_uuid',
              expires_at: new Date(Date.now() + 120_000).toISOString(),
              masked_mobile: '۰۹۱۲***۶۷۸۹',
            },
          }),
        };
      }
      if (url.includes('/auth/otp/verify')) {
        return {
          ok: true,
          json: async () => ({
            data: {
              token: 'test_memory_access_token_abc',
              token_type: 'Bearer',
              expires_at: new Date(Date.now() + 86400_000).toISOString(),
              citizen: {
                id: 'citizen_uuid_1',
                mobile: '09123456789',
                tier: 'bronze',
              },
              abilities: ['citizen:read', 'citizen:write'],
            },
          }),
        };
      }
      if (url.includes('/auth/refresh')) {
        return {
          ok: true,
          json: async () => ({
            data: {
              token: 'sw_pat_short_lived_test',
              token_type: 'Bearer',
              expires_at: new Date(Date.now() + 900_000).toISOString(),
              ttl_seconds: 900,
            },
          }),
        };
      }
      return { ok: false, status: 404, json: async () => ({}) };
    });

    const { container } = render(<AuthFlow />);

    // --- STEP 1: Phone Input ---
    expect(screen.getByLabelText('شماره تلفن همراه', { exact: true })).toBeInTheDocument();

    // Persian digits entered: ۰۹۱۲۳۴۵۶۷۸۹
    const phoneInput = screen.getByLabelText('شماره تلفن همراه', { exact: true });
    fireEvent.change(phoneInput, { target: { value: '۰۹۱۲۳۴۵۶۷۸۹' } });

    // Submit step 1
    fireEvent.click(screen.getByRole('button', { name: /ادامه/ }));

    // --- STEP 2: National ID Input ---
    await waitFor(() => {
      expect(screen.getByLabelText('کد ملی', { exact: true })).toBeInTheDocument();
    });

    // Enter mathematically valid Iranian National ID with Persian digits: ۰۰۱۰۳۵۰۸۰۲
    const nationalIdInput = screen.getByLabelText('کد ملی', { exact: true });
    fireEvent.change(nationalIdInput, { target: { value: '۰۰۱۰۳۵۰۸۰۲' } });

    // Submit step 2
    fireEvent.click(screen.getByRole('button', { name: /ادامه/ }));

    // --- STEP 3: OTP Verify ---
    await waitFor(() => {
      expect(screen.getByRole('group', { name: /رمز یکبار مصرف/ })).toBeInTheDocument();
    });

    // Verify OTP input fields
    const digitInputs = screen.getAllByRole('textbox');
    expect(digitInputs.length).toBe(5);

    // Enter 5 digits
    fireEvent.change(digitInputs[0]!, { target: { value: '۱' } });
    fireEvent.change(digitInputs[1]!, { target: { value: '۲' } });
    fireEvent.change(digitInputs[2]!, { target: { value: '۳' } });
    fireEvent.change(digitInputs[3]!, { target: { value: '۴' } });
    fireEvent.change(digitInputs[4]!, { target: { value: '۵' } });

    // Submit OTP verification
    fireEvent.click(screen.getByRole('button', { name: /تأیید و ورود/ }));

    // Assert successful login screen rendered
    await waitFor(() => {
      expect(screen.getByText(/ورود با موفقیت انجام شد/)).toBeInTheDocument();
    });

    // Verify in-memory token is populated
    expect(getAccessToken()).toBe('test_memory_access_token_abc');

    // CRITICAL SECURITY CONSTRAINT (D-12): localStorage MUST NEVER be touched!
    expect(localStorageSpy).not.toHaveBeenCalled();
  });

  it('navigates back from national ID step to phone input step', async () => {
    render(<AuthFlow />);

    const phoneInput = screen.getByLabelText('شماره تلفن همراه', { exact: true });
    fireEvent.change(phoneInput, { target: { value: '09123456789' } });
    fireEvent.click(screen.getByRole('button', { name: /ادامه/ }));

    await waitFor(() => {
      expect(screen.getByLabelText('کد ملی', { exact: true })).toBeInTheDocument();
    });

    // Click back button
    fireEvent.click(screen.getByRole('button', { name: /بازگشت/ }));

    await waitFor(() => {
      expect(screen.getByLabelText('شماره تلفن همراه', { exact: true })).toBeInTheDocument();
    });
  });

  it('navigates back to phone step from OTP step via change phone button', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        data: { challenge_id: 'ch_test', expires_at: '...', masked_mobile: '۰۹۱۲***۶۷۸۹' },
      }),
    });

    render(<AuthFlow />);

    fireEvent.change(screen.getByLabelText('شماره تلفن همراه', { exact: true }), { target: { value: '09123456789' } });
    fireEvent.click(screen.getByRole('button', { name: /ادامه/ }));

    await waitFor(() => screen.getByLabelText('کد ملی', { exact: true }));
    fireEvent.change(screen.getByLabelText('کد ملی', { exact: true }), { target: { value: '0010350802' } });
    fireEvent.click(screen.getByRole('button', { name: /ادامه/ }));

    await waitFor(() => screen.getByRole('group', { name: /رمز یکبار مصرف/ }));

    // Click change phone button
    fireEvent.click(screen.getByRole('button', { name: /تغییر شماره همراه/ }));

    await waitFor(() => {
      expect(screen.getByLabelText('شماره تلفن همراه', { exact: true })).toBeInTheDocument();
    });
  });

  it('validates invalid phone number format and displays error message', async () => {
    render(<AuthFlow />);

    const phoneInput = screen.getByLabelText('شماره تلفن همراه', { exact: true });
    fireEvent.change(phoneInput, { target: { value: '08123456789' } }); // doesn't start with 09
    fireEvent.click(screen.getByRole('button', { name: /ادامه/ }));

    expect(screen.getByText(/شماره تلفن همراه باید با ۰۹ شروع شده و ۱۱ رقم باشد/)).toBeInTheDocument();
  });

  it('validates invalid national ID checksum and displays error message', async () => {
    render(<AuthFlow />);

    fireEvent.change(screen.getByLabelText('شماره تلفن همراه', { exact: true }), { target: { value: '09123456789' } });
    fireEvent.click(screen.getByRole('button', { name: /ادامه/ }));

    await waitFor(() => screen.getByLabelText('کد ملی', { exact: true }));

    const nationalIdInput = screen.getByLabelText('کد ملی', { exact: true });
    fireEvent.change(nationalIdInput, { target: { value: '1111111111' } }); // invalid check digit
    fireEvent.click(screen.getByRole('button', { name: /ادامه/ }));

    expect(screen.getByText(/کد ملی ۱۰ رقمی وارد شده نامعتبر است/)).toBeInTheDocument();
  });

  it('activates the resend button after countdown timer expires', async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => ({
        data: { challenge_id: 'ch_test_timer', expires_at: '...', masked_mobile: '۰۹۱۲***۶۷۸۹' },
      }),
    });

    render(<AuthFlow />);

    fireEvent.change(screen.getByLabelText('شماره تلفن همراه', { exact: true }), { target: { value: '09123456789' } });
    fireEvent.click(screen.getByRole('button', { name: /ادامه/ }));

    await waitFor(() => screen.getByLabelText('کد ملی', { exact: true }));
    fireEvent.change(screen.getByLabelText('کد ملی', { exact: true }), { target: { value: '0010350802' } });
    fireEvent.click(screen.getByRole('button', { name: /ادامه/ }));

    await waitFor(() => screen.getByRole('group', { name: /رمز یکبار مصرف/ }));

    // Initially, countdown is visible and resend button is not available
    expect(screen.getByText(/ارسال مجدد تا/)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /ارسال مجدد کد/ })).not.toBeInTheDocument();

    // Fast-forward countdown by decrementing store state to 0
    act(() => {
      useAuthStore.setState({ countdown: 0, resendAvailable: true });
    });

    // Now resend button must be visible and interactive
    const resendBtn = screen.getByRole('button', { name: /ارسال مجدد کد/ });
    expect(resendBtn).toBeInTheDocument();

    // Clicking resend triggers API request again
    fireEvent.click(resendBtn);
    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining('/auth/otp/request'),
      expect.anything()
    );
  });

  it('satisfies accessibility requirements with 0 axe violations across all steps', async () => {
    const { container } = render(<AuthFlow />);

    // Step 1 accessibility
    let results = await axe(container);
    expect(results).toHaveNoViolations();

    // Transition to Step 2
    act(() => {
      useAuthStore.setState({ step: 'national_id_input', mobile: '09123456789' });
    });
    results = await axe(container);
    expect(results).toHaveNoViolations();

    // Transition to Step 3
    act(() => {
      useAuthStore.setState({
        step: 'otp_verify',
        nationalId: '0010350802',
        challengeId: 'ch_accessibility',
        maskedMobile: '۰۹۱۲***۶۷۸۹',
      });
    });
    results = await axe(container);
    expect(results).toHaveNoViolations();

    // Transition to Authenticated Step
    act(() => {
      useAuthStore.setState({
        isAuthenticated: true,
        citizen: {
          id: 'cit_1',
          mobile: '09123456789',
          tier: 'bronze',
        },
      });
    });
    results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('handles logout and clears auth store and in-memory tokens', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: { success: true } }),
    });

    useAuthStore.setState({
      isAuthenticated: true,
      citizen: { id: 'c1', mobile: '09123456789', tier: 'bronze' },
    });
    setAccessToken('token_before_logout');

    await useAuthStore.getState().logout();

    expect(getAccessToken()).toBeNull();
    expect(useAuthStore.getState().isAuthenticated).toBe(false);
    expect(useAuthStore.getState().step).toBe('phone_input');
  });

  it('handles OTP verification failure with server error message', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 422,
      json: async () => ({
        code: 'AUTH_OTP_INVALID',
        detail: 'کد تأیید وارد شده نامعتبر است',
      }),
    });

    useAuthStore.setState({
      step: 'otp_verify',
      challengeId: 'ch_invalid',
    });

    const success = await useAuthStore.getState().verifyOtpCode('99999');
    expect(success).toBe(false);
    expect(useAuthStore.getState().error).toBe('کد تأیید وارد شده نامعتبر است');
  });
});
