import { jsx as _jsx } from "react/jsx-runtime";
import { Suspense } from 'react';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { QueryClientProvider } from '@tanstack/react-query';
import { deskQueryClient } from '@/shared/api/query-client';
import { deskRoutes } from '@/routes';
import { ErrorBoundary } from '@/shared/ui/ErrorBoundary';
import '@/shared/i18n';
const router = createBrowserRouter(deskRoutes);
export function App() {
    return (_jsx(ErrorBoundary, { children: _jsx(QueryClientProvider, { client: deskQueryClient, children: _jsx(Suspense, { fallback: _jsx("div", { className: "min-h-screen flex items-center justify-center bg-slate-100", children: _jsx("div", { className: "w-8 h-8 border-4 border-slate-300 border-t-slate-900 rounded-full animate-spin" }) }), children: _jsx(RouterProvider, { router: router }) }) }) }));
}
export default App;
