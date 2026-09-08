import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Component } from 'react';
/**
 * Multi-layer Error Boundary compliant with Architecture §4.11
 */
export class ErrorBoundary extends Component {
    state = {
        hasError: false,
        error: null,
    };
    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }
    componentDidCatch(error, errorInfo) {
        // Structured error logging without PII
        console.error('ErrorBoundary caught an unhandled error:', error, errorInfo);
    }
    handleReset = () => {
        this.setState({ hasError: false, error: null });
    };
    render() {
        if (this.state.hasError) {
            if (this.props.fallback) {
                return this.props.fallback;
            }
            return (_jsx("div", { className: "min-h-screen bg-slate-50 flex items-center justify-center p-4", children: _jsxs("div", { className: "max-w-md w-full bg-white p-6 rounded-2xl border border-rose-200 shadow-sm text-center", children: [_jsx("div", { className: "w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 font-bold text-xl", children: "!" }), _jsx("h2", { className: "text-lg font-bold text-slate-900 mb-2", children: "\u062E\u0637\u0627\u06CC\u06CC \u0631\u062E \u062F\u0627\u062F\u0647 \u0627\u0633\u062A" }), _jsx("p", { className: "text-sm text-slate-600 mb-6", children: "\u0645\u062A\u0623\u0633\u0641\u0627\u0646\u0647 \u0645\u0634\u06A9\u0644\u06CC \u062F\u0631 \u0628\u0627\u0631\u06AF\u0630\u0627\u0631\u06CC \u0627\u06CC\u0646 \u0635\u0641\u062D\u0647 \u067E\u06CC\u0634 \u0622\u0645\u062F\u0647 \u0627\u0633\u062A." }), _jsx("button", { type: "button", onClick: this.handleReset, className: "px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-xl hover:bg-slate-800 transition-colors", children: "\u062A\u0644\u0627\u0634 \u0645\u062C\u062F\u062F" })] }) }));
        }
        return this.props.children;
    }
}
