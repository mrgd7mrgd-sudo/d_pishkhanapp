import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, OtpInput } from '@pishkhan/ui-kit';
import { useAuthStore } from '../model/useAuthStore';
const OtpTimerRow = ({ isLoading, resendAvailable, countdown, onResend, onChangePhone, }) => {
    const { t } = useTranslation();
    return (_jsxs("div", { className: "flex items-center justify-between text-xs text-slate-600 dark:text-slate-400", children: [_jsx("button", { type: "button", onClick: onChangePhone, className: "text-emerald-700 dark:text-emerald-400 hover:underline font-medium focus:outline-none", disabled: isLoading, children: t('auth.change_phone') }), resendAvailable ? (_jsx("button", { type: "button", onClick: onResend, className: "text-emerald-700 dark:text-emerald-400 hover:underline font-bold focus:outline-none", disabled: isLoading, children: t('auth.resend_code') })) : (_jsx("span", { "aria-live": "polite", className: "font-mono tabular-nums", children: t('auth.resend_in', { seconds: countdown }) }))] }));
};
const OtpHeader = ({ maskedMobile }) => {
    const { t } = useTranslation();
    return (_jsxs("div", { className: "space-y-2 text-center", children: [_jsx("h2", { id: "otp-heading", className: "text-xl font-bold text-slate-900 dark:text-slate-100", children: t('auth.otp_step_title') }), _jsx("p", { className: "text-sm text-slate-600 dark:text-slate-400", children: t('auth.otp_sent_to', { mobile: maskedMobile }) })] }));
};
const useOtpTimer = (resendAvailable, decrementCountdown) => {
    useEffect(() => {
        if (resendAvailable) {
            return undefined;
        }
        const timer = setInterval(() => decrementCountdown(), 1000);
        return () => clearInterval(timer);
    }, [resendAvailable, decrementCountdown]);
};
export const OtpVerifyStep = () => {
    const { t } = useTranslation();
    const [code, setCode] = useState('');
    const { maskedMobile, countdown, resendAvailable, isLoading, error } = useAuthStore();
    const { decrementCountdown, verifyOtpCode, resendOtp, setStep } = useAuthStore();
    useOtpTimer(resendAvailable, decrementCountdown);
    const errorMessage = error === 'invalid_otp' ? t('auth.invalid_otp') : error;
    return (_jsxs("form", { onSubmit: async (e) => {
            e.preventDefault();
            await verifyOtpCode(code);
        }, className: "space-y-6", "aria-labelledby": "otp-heading", children: [_jsx(OtpHeader, { maskedMobile: maskedMobile }), _jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "flex flex-col items-center gap-2", children: [_jsx(OtpInput, { value: code, onChange: setCode, length: 5, disabled: isLoading, autoFocus: true, hasError: Boolean(errorMessage) }), errorMessage && (_jsx("p", { role: "alert", className: "text-xs text-rose-600 font-medium mt-1", children: errorMessage }))] }), _jsx(OtpTimerRow, { isLoading: isLoading, resendAvailable: resendAvailable, countdown: countdown, onResend: async () => {
                            setCode('');
                            await resendOtp();
                        }, onChangePhone: () => setStep('phone_input') }), _jsx(Button, { type: "submit", variant: "primary", className: "w-full", isLoading: isLoading, disabled: code.length !== 5 || isLoading, children: t('auth.verify_and_login') })] })] }));
};
