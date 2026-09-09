import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { RefreshCw, X } from 'lucide-react';
import { setupServiceWorker } from './registerSW';
export function UpdatePrompt() {
    const { t } = useTranslation();
    const [needRefresh, setNeedRefresh] = useState(false);
    const [updateFunction, setUpdateFunction] = useState(null);
    useEffect(() => {
        const update = setupServiceWorker({
            onNeedRefresh() {
                setNeedRefresh(true);
            },
        });
        setUpdateFunction(() => update);
    }, []);
    const handleUpdate = async () => {
        if (updateFunction) {
            await updateFunction(true);
        }
    };
    const handleDismiss = () => {
        setNeedRefresh(false);
    };
    if (!needRefresh) {
        return null;
    }
    return (_jsxs("aside", { "aria-label": t('pwa.update_available'), className: "fixed bottom-4 inset-x-4 max-w-md mx-auto z-50 p-4 rounded-2xl bg-slate-900/95 text-white shadow-2xl border border-slate-700 flex items-center justify-between gap-3", role: "alert", children: [_jsxs("div", { className: "flex items-center gap-3", children: [_jsx(RefreshCw, { "aria-hidden": "true", className: "w-5 h-5 text-emerald-400 animate-spin" }), _jsx("span", { className: "text-sm font-medium", children: t('pwa.new_version_ready') })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("button", { className: "px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-xs font-semibold text-white transition-colors", onClick: handleUpdate, type: "button", children: t('pwa.update_now') }), _jsx("button", { "aria-label": t('pwa.close'), className: "p-1 rounded-lg text-slate-400 hover:text-white transition-colors", onClick: handleDismiss, type: "button", children: _jsx(X, { "aria-hidden": "true", className: "w-4 h-4" }) })] })] }));
}
