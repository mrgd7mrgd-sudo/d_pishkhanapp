import { jsx as _jsx } from "react/jsx-runtime";
import { Suspense } from 'react';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/shared/api/query-client';
import { routes } from '@/routes';
import { ErrorBoundary } from '@/shared/ui/ErrorBoundary';
import '@/shared/i18n';
const router = createBrowserRouter(routes);
/**
 * Root Application Shell (Architecture §4.3 & §4.4 — must be ≤ 80 lines)
 */
export function App() {
    return (_jsx(ErrorBoundary, { children: _jsx(QueryClientProvider, { client: queryClient, children: _jsx(Suspense, { fallback: _jsx("div", { className: "min-h-screen flex items-center justify-center bg-slate-50", children: _jsx("div", { className: "w-8 h-8 border-4 border-slate-200 border-t-sky-600 rounded-full animate-spin" }) }), children: _jsx(RouterProvider, { router: router }) }) }) }));
}
export default App;
