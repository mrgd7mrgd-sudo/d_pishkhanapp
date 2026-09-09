import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Input, Button } from '@pishkhan/ui-kit';
import { normalizeDigits } from '@pishkhan/domain';
import { useAuthStore } from '../model/useAuthStore';
export const PhoneInputStep = () => {
    const { t } = useTranslation();
    const mobile = useAuthStore((s) => s.mobile);
    const error = useAuthStore((s) => s.error);
    const submitPhone = useAuthStore((s) => s.submitPhone);
    const [localPhone, setLocalPhone] = useState(mobile);
    const handleChange = (e) => {
        const normalized = normalizeDigits(e.target.value);
        setLocalPhone(normalized);
    };
    const handleSubmit = (e) => {
        e.preventDefault();
        submitPhone(localPhone);
    };
    const errorMessage = error === 'invalid_phone' ? t('auth.invalid_phone') : error;
    return (_jsxs("form", { onSubmit: handleSubmit, className: "space-y-6", "aria-labelledby": "phone-step-heading", children: [_jsxs("div", { className: "space-y-2", children: [_jsx("h2", { id: "phone-step-heading", className: "text-xl font-bold text-slate-900 dark:text-slate-100", children: t('auth.phone_step_title') }), _jsx("p", { className: "text-sm text-slate-600 dark:text-slate-400", children: t('auth.phone_step_subtitle') })] }), _jsxs("div", { className: "space-y-4", children: [_jsx(Input, { id: "phone-input", type: "tel", inputMode: "numeric", autoComplete: "tel", dir: "ltr", label: t('auth.phone_label'), placeholder: t('auth.phone_placeholder'), value: localPhone, onChange: handleChange, error: errorMessage ?? undefined, autoFocus: true }), _jsx(Button, { type: "submit", variant: "primary", className: "w-full", children: t('auth.continue') })] })] }));
};
