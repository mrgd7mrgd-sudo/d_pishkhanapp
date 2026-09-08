import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css';
export function App() {
    return (_jsx("div", { className: "min-h-screen bg-slate-50 flex flex-col items-center justify-center p-4 text-center", children: _jsxs("div", { className: "max-w-md w-full p-6 bg-white/80 backdrop-blur-md rounded-2xl shadow-sm border border-slate-200", children: [_jsx("h1", { className: "text-xl font-bold text-slate-900 mb-2", children: "\u0633\u0627\u0645\u0627\u0646\u0647 \u062C\u0627\u0645\u0639 \u062E\u062F\u0645\u0627\u062A \u0634\u0647\u0631\u0648\u0646\u062F\u06CC \u0648 \u067E\u06CC\u0634\u062E\u0648\u0627\u0646 \u0647\u0648\u0634\u0645\u0646\u062F" }), _jsx("p", { className: "text-sm text-slate-600", children: "\u0633\u0648\u067E\u0631\u0627\u067E\u0644\u06CC\u06A9\u06CC\u0634\u0646 \u0634\u0647\u0631\u0648\u0646\u062F\u06CC PWA \u2014 \u0646\u0633\u0644 \u062C\u062F\u06CC\u062F" })] }) }));
}
const rootElement = document.getElementById('root');
if (rootElement) {
    ReactDOM.createRoot(rootElement).render(_jsx(React.StrictMode, { children: _jsx(App, {}) }));
}
