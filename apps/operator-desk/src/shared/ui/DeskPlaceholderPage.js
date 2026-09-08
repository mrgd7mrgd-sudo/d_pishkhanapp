import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useTranslation } from 'react-i18next';
export function DeskPlaceholderPage({ titleKey }) {
    const { t } = useTranslation();
    return (_jsx("div", { className: "p-8", children: _jsxs("div", { className: "bg-white p-6 rounded-xl border border-slate-200 shadow-sm", children: [_jsx("h1", { className: "text-xl font-bold text-slate-900 mb-2", children: t(`routes.${titleKey}`) }), _jsx("p", { className: "text-sm text-slate-500", children: "\u0645\u0627\u0698\u0648\u0644 \u0627\u062F\u0627\u0631\u06CC \u0627\u067E\u0631\u0627\u062A\u0648\u0631 \u0648 \u0645\u062F\u06CC\u0631\u06CC\u062A \u062F\u0641\u062A\u0631 \u067E\u06CC\u0634\u062E\u0648\u0627\u0646" })] }) }));
}
