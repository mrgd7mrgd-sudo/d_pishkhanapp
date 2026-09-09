import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Component } from 'react';
import i18n from '@/shared/i18n';
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
            return (_jsx("div", { className: "min-h-screen bg-slate-50 flex items-center justify-center p-4", children: _jsxs("div", { className: "max-w-md w-full bg-white p-6 rounded-2xl border border-rose-200 shadow-sm text-center", children: [_jsx("div", { className: "w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 font-bold text-xl", children: "!" }), _jsx("h2", { className: "text-lg font-bold text-slate-900 mb-2", children: i18n.t('app.error_title') }), _jsx("p", { className: "text-sm text-slate-600 mb-6", children: i18n.t('app.error_description') }), _jsx("button", { type: "button", onClick: this.handleReset, className: "px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-xl hover:bg-slate-800 transition-colors", children: i18n.t('app.retry') })] }) }));
        }
        return this.props.children;
    }
}
