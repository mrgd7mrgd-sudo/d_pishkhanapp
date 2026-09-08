import React, { Suspense } from 'react';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { QueryClientProvider } from '@tanstack/react-query';
import { deskQueryClient } from '@/shared/api/query-client';
import { deskRoutes } from '@/routes';
import { ErrorBoundary } from '@/shared/ui/ErrorBoundary';
import '@/shared/i18n';

const router = createBrowserRouter(deskRoutes);

export function App(): React.JSX.Element {
  return (
    <ErrorBoundary>
      <QueryClientProvider client={deskQueryClient}>
        <Suspense
          fallback={
            <div className="min-h-screen flex items-center justify-center bg-slate-100">
              <div className="w-8 h-8 border-4 border-slate-300 border-t-slate-900 rounded-full animate-spin" />
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
