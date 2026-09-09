import React from 'react';
import { CredentialsStep } from './CredentialsStep';
import { OperatorOtpStep } from './OperatorOtpStep';
import { OperatorProfileSummary } from './OperatorProfileSummary';
import { useOperatorAuthStore } from '../model/useOperatorAuthStore';

export const OperatorAuthFlow: React.FC = () => {
  const step = useOperatorAuthStore((s) => s.step);
  const isAuthenticated = useOperatorAuthStore((s) => s.isAuthenticated);

  if (isAuthenticated) {
    return (
      <div className="w-full max-w-md mx-auto">
        <OperatorProfileSummary />
      </div>
    );
  }

  return (
    <div className="w-full max-w-md mx-auto p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
      {step === 'credentials' && <CredentialsStep />}
      {step === 'otp_verify' && <OperatorOtpStep />}
    </div>
  );
};
