import React, { Suspense } from 'react';
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
export function App(): React.JSX.Element {
  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <Suspense
          fallback={
            <div className="min-h-screen flex items-center justify-center bg-slate-50">
              <div className="w-8 h-8 border-4 border-slate-200 border-t-sky-600 rounded-full animate-spin" />
            </div>
          }
        >
          <RouterProvider router={router} />
        </Suspense>
      </QueryClientProvider>
    </ErrorBoundary>
  );
}

export default App;
