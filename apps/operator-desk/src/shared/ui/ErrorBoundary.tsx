import React, { Component, type ErrorInfo, type ReactNode } from 'react';
import i18n from '@/shared/i18n';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
  public override state: State = {
    hasError: false,
    error: null,
  };

  public static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  public override componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
    console.error('OperatorDesk ErrorBoundary:', error, errorInfo);
  }

  private handleReset = (): void => {
    this.setState({ hasError: false, error: null });
  };

  public override render(): ReactNode {
    if (this.state.hasError) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      return (
        <div className="min-h-screen bg-slate-100 flex items-center justify-center p-6">
          <div className="max-w-md w-full bg-white p-8 rounded-xl border border-rose-200 shadow-sm text-center">
            <h2 className="text-lg font-bold text-slate-900 mb-2">
              {i18n.t('app.error_title')}
            </h2>
            <p className="text-sm text-slate-600 mb-6">
              {i18n.t('app.error_description')}
            </p>
            <button
              type="button"
              onClick={this.handleReset}
              className="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors"
            >
              {i18n.t('app.retry')}
            </button>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}
