import React from 'react';
import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { axe } from 'vitest-axe';
import 'vitest-axe/extend-expect';
import { OperatorAuthFlow } from '../ui/OperatorAuthFlow';
import { useOperatorAuthStore } from '../model/useOperatorAuthStore';

describe('Operator Desk Auth Slice & 2-Step Flow (§4.4, §7.2, D-12)', () => {
  const mockFetch = vi.fn();

  beforeEach(() => {
    vi.stubGlobal('fetch', mockFetch);
    useOperatorAuthStore.getState().reset();
    vi.restoreAllMocks();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
  });

  it('verifies manual office code entry without dropdown and completes 2-step login', async () => {
    mockFetch.mockImplementation(async (url: string) => {
      if (url.includes('/operator/auth/login')) {
        return {
          ok: true,
          json: async () => ({
            data: {
              challenge_id: 'op_challenge_999',
              masked_mobile: '۰۹۱۲***۶۷۸۹',
              expires_at: new Date(Date.now() + 120_000).toISOString(),
            },
          }),
        };
      }
      if (url.includes('/operator/auth/verify-otp')) {
        return {
          ok: true,
          json: async () => ({
            data: {
              operator: {
                id: 'op_uuid_42',
                office_id: '72161001',
                username: 'op_ahmad',
                full_name: 'احمد رضایی',
                role: 'counter_operator',
                role_name: 'متصدی باجه',
                counter_number: 4,
                is_active: true,
                last_login_at: '2026-09-09T14:00:00Z',
              },
              session_id: 'session_cookie_token_777',
            },
          }),
        };
      }
      return { ok: false, status: 404, json: async () => ({}) };
    });

    const { container } = render(<OperatorAuthFlow />);

    // REQUIREMENT: Verify NO office selection dropdown/select exists (manual text input only)
    const selects = container.querySelectorAll('select');
    expect(selects.length).toBe(0);

    // Verify manual office code text input exists
    const officeCodeInput = screen.getByLabelText('کد دفتر پیشخوان', { exact: true });
    expect(officeCodeInput).toBeInTheDocument();
    expect(officeCodeInput.tagName).toBe('INPUT');

    const usernameInput = screen.getByLabelText('نام کاربری', { exact: true });
    const passwordInput = screen.getByLabelText('رمز عبور', { exact: true });

    // Enter credentials with Persian digits for office code (should normalize)
    fireEvent.change(officeCodeInput, { target: { value: '۷۲۱۶۱۰۰۱' } });
    fireEvent.change(usernameInput, { target: { value: 'op_ahmad' } });
    fireEvent.change(passwordInput, { target: { value: 'MySecretPassword123!' } });

    // Submit credentials
    fireEvent.click(screen.getByRole('button', { name: /ادامه و دریافت کد تأیید/ }));

    // Verify transition to OTP Step
    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /تأیید هویت دو مرحله‌ای/ })).toBeInTheDocument();
    });

    // Enter 5 digits in OtpInput
    const digitInputs = screen.getAllByRole('textbox');
    expect(digitInputs.length).toBe(5);

    fireEvent.change(digitInputs[0]!, { target: { value: '۱' } });
    fireEvent.change(digitInputs[1]!, { target: { value: '۲' } });
    fireEvent.change(digitInputs[2]!, { target: { value: '۳' } });
    fireEvent.change(digitInputs[3]!, { target: { value: '۴' } });
    fireEvent.change(digitInputs[4]!, { target: { value: '۵' } });

    // Submit OTP
    fireEvent.click(screen.getByRole('button', { name: /تأیید و ورود به پنل/ }));

    // Verify Authenticated State & Profile Summary (office ID and counter number)
    await waitFor(() => {
      expect(screen.getByText('احمد رضایی')).toBeInTheDocument();
    });

    expect(screen.getByText(/باجه شماره (4|۴)/)).toBeInTheDocument();
    expect(screen.getByText(/دفتر: 72161001/)).toBeInTheDocument();
    expect(screen.getByText(/متصدی باجه/)).toBeInTheDocument();
  });

  it('allows navigating back from OTP step to credentials step', async () => {
    useOperatorAuthStore.setState({
      step: 'otp_verify',
      officeCode: '72161001',
      username: 'op_test',
      challengeId: 'ch_back_test',
      maskedMobile: '۰۹۱۲***۶۷۸۹',
    });

    render(<OperatorAuthFlow />);

    expect(screen.getByRole('heading', { name: /تأیید هویت دو مرحله‌ای/ })).toBeInTheDocument();

    const backButton = screen.getByRole('button', { name: /ویرایش مشخصات ورود/ });
    fireEvent.click(backButton);

    expect(screen.getByRole('heading', { name: /ورود به میز کار پیشخوان/ })).toBeInTheDocument();
    expect(useOperatorAuthStore.getState().step).toBe('credentials');
  });

  it('handles logout and clears session and operator state', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ data: { success: true } }),
    });

    useOperatorAuthStore.setState({
      isAuthenticated: true,
      operator: {
        id: 'op_logout_test',
        office_id: '72161001',
        username: 'op_user',
        full_name: 'سارا حسینی',
        role: 'office_admin',
        role_name: 'مدیر دفتر',
        counter_number: 1,
        is_active: true,
        last_login_at: null,
      },
    });

    render(<OperatorAuthFlow />);

    expect(screen.getByText('سارا حسینی')).toBeInTheDocument();
    expect(screen.getByText(/باجه شماره (1|۱)/)).toBeInTheDocument();

    const logoutButton = screen.getByRole('button', { name: /خروج از حساب/ });
    fireEvent.click(logoutButton);

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /ورود به میز کار پیشخوان/ })).toBeInTheDocument();
    });

    expect(useOperatorAuthStore.getState().isAuthenticated).toBe(false);
    expect(useOperatorAuthStore.getState().operator).toBeNull();
    expect(useOperatorAuthStore.getState().step).toBe('credentials');
  });

  it('validates empty inputs and displays error on credentials step', async () => {
    render(<OperatorAuthFlow />);

    const submitButton = screen.getByRole('button', { name: /ادامه و دریافت کد تأیید/ });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(screen.getByRole('alert')).toBeInTheDocument();
    });
  });

  it('handles API errors gracefully during login and OTP verification', async () => {
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 401,
      json: async () => ({
        code: 'AUTH_INVALID_CREDENTIALS',
        detail: 'اطلاعات کاربری صحیح نمی‌باشد',
      }),
    });

    render(<OperatorAuthFlow />);

    const officeCodeInput = screen.getByLabelText('کد دفتر پیشخوان', { exact: true });
    const usernameInput = screen.getByLabelText('نام کاربری', { exact: true });
    const passwordInput = screen.getByLabelText('رمز عبور', { exact: true });

    fireEvent.change(officeCodeInput, { target: { value: '72161001' } });
    fireEvent.change(usernameInput, { target: { value: 'wrong_user' } });
    fireEvent.change(passwordInput, { target: { value: 'wrong_pass' } });

    fireEvent.click(screen.getByRole('button', { name: /ادامه و دریافت کد تأیید/ }));

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent('اطلاعات کاربری صحیح نمی‌باشد');
    });

    // Test OTP error
    mockFetch.mockResolvedValueOnce({
      ok: false,
      status: 422,
      json: async () => ({
        code: 'OTP_INVALID',
        detail: 'کد ۵ رقمی اشتباه است',
      }),
    });

    useOperatorAuthStore.setState({
      step: 'otp_verify',
      challengeId: 'ch_otp_err',
      maskedMobile: '۰۹۱۲***۶۷۸۹',
    });

    const success = await useOperatorAuthStore.getState().verifyOtpCode('11111');
    expect(success).toBe(false);
    expect(useOperatorAuthStore.getState().error).toBe('کد ۵ رقمی اشتباه است');
  });

  it('handles 120s countdown and OTP resend', async () => {
    vi.useFakeTimers();

    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => ({
        data: {
          challenge_id: 'ch_resend_new',
          masked_mobile: '۰۹۱۲***۶۷۸۹',
          expires_at: new Date(Date.now() + 120_000).toISOString(),
        },
      }),
    });

    useOperatorAuthStore.setState({
      step: 'otp_verify',
      officeCode: '72161001',
      username: 'op_resend',
      password: 'mypassword',
      challengeId: 'ch_resend_old',
      maskedMobile: '۰۹۱۲***۶۷۸۹',
      countdown: 120,
      resendAvailable: false,
    });

    render(<OperatorAuthFlow />);

    expect(screen.getByText(/ارسال مجدد تا (120|۱۲۰) ثانیه/)).toBeInTheDocument();

    // Fast-forward 120 seconds
    act(() => {
      vi.advanceTimersByTime(120_000);
    });

    // Resend button should now appear
    const resendBtn = screen.getByRole('button', { name: /ارسال مجدد کد/ });
    expect(resendBtn).toBeInTheDocument();

    await act(async () => {
      fireEvent.click(resendBtn);
    });

    expect(mockFetch).toHaveBeenCalledWith(
      '/api/v1/operator/auth/login',
      expect.anything()
    );
  });

  it('passes accessibility audits with zero axe violations across all states', async () => {
    const { container } = render(<OperatorAuthFlow />);

    // Step 1: Credentials
    let results = await axe(container);
    expect(results).toHaveNoViolations();

    // Step 2: OTP
    act(() => {
      useOperatorAuthStore.setState({
        step: 'otp_verify',
        challengeId: 'ch_axe_test',
        maskedMobile: '۰۹۱۲***۶۷۸۹',
      });
    });
    results = await axe(container);
    expect(results).toHaveNoViolations();

    // Step 3: Authenticated Operator Profile Summary
    act(() => {
      useOperatorAuthStore.setState({
        isAuthenticated: true,
        operator: {
          id: 'op_axe',
          office_id: '72161001',
          username: 'op_axe_user',
          full_name: 'حسین احمدی',
          role: 'counter_operator',
          role_name: 'متصدی باجه',
          counter_number: 2,
          is_active: true,
          last_login_at: null,
        },
      });
    });
    results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});
