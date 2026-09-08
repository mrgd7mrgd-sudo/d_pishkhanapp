import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useTranslation } from 'react-i18next';
export function PlaceholderPage({ titleKey }) {
    const { t } = useTranslation();
    return (_jsx("div", { className: "flex flex-col items-center justify-center p-8 text-center min-h-[50vh]", children: _jsxs("div", { className: "max-w-md w-full p-6 bg-white rounded-2xl border border-slate-200 shadow-sm", children: [_jsx("h1", { className: "text-xl font-bold text-slate-900 mb-2", children: t(`routes.${titleKey}`) }), _jsx("p", { className: "text-xs text-slate-400", children: "\u0645\u0633\u06CC\u0631 \u0645\u0639\u062A\u0628\u0631 \u0633\u0627\u0645\u0627\u0646\u0647 \u0634\u0647\u0631\u0648\u0646\u062F" })] }) }));
}
