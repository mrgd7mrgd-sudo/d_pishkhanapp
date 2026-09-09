import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Input, Button } from '@pishkhan/ui-kit';
import { normalizeDigits } from '@pishkhan/domain';
import { useAuthStore } from '../model/useAuthStore';
const StepActions = ({ isLoading, onBack }) => {
    const { t } = useTranslation();
    return (_jsxs("div", { className: "flex items-center gap-3", children: [_jsx(Button, { type: "button", variant: "secondary", className: "flex-1", onClick: onBack, disabled: isLoading, children: t('auth.back') }), _jsx(Button, { type: "submit", variant: "primary", className: "flex-1", isLoading: isLoading, children: t('auth.continue') })] }));
};
export const NationalIdInputStep = () => {
    const { t } = useTranslation();
    const nationalId = useAuthStore((s) => s.nationalId);
    const error = useAuthStore((s) => s.error);
    const isLoading = useAuthStore((s) => s.isLoading);
    const submitNationalId = useAuthStore((s) => s.submitNationalId);
    const setStep = useAuthStore((s) => s.setStep);
    const [localId, setLocalId] = useState(nationalId);
    const handleSubmit = async (e) => {
        e.preventDefault();
        await submitNationalId(localId);
    };
    const errorMessage = error === 'invalid_national_id' ? t('auth.invalid_national_id') : error;
    return (_jsxs("form", { onSubmit: handleSubmit, className: "space-y-6", "aria-labelledby": "national-id-heading", children: [_jsxs("div", { className: "space-y-2", children: [_jsx("h2", { id: "national-id-heading", className: "text-xl font-bold text-slate-900 dark:text-slate-100", children: t('auth.national_id_step_title') }), _jsx("p", { className: "text-sm text-slate-600 dark:text-slate-400", children: t('auth.national_id_step_subtitle') })] }), _jsxs("div", { className: "space-y-4", children: [_jsx(Input, { id: "national-id-input", type: "text", inputMode: "numeric", maxLength: 10, dir: "ltr", label: t('auth.national_id_label'), placeholder: t('auth.national_id_placeholder'), value: localId, onChange: (e) => setLocalId(normalizeDigits(e.target.value)), error: errorMessage ?? undefined, autoFocus: true }), _jsx(StepActions, { isLoading: isLoading, onBack: () => setStep('phone_input') })] })] }));
};
