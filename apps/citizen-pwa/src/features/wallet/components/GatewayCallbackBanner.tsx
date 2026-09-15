import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useVerifyTopup } from '../hooks/useWallet';
import { CurrencyText } from '@pishkhan/ui-kit';
import { CheckCircle2, AlertCircle, RefreshCw, X } from 'lucide-react';
import type { VerifyTopupData } from '../types';

export const GatewayCallbackBanner: React.FC = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const authority = searchParams.get('authority') || searchParams.get('Authority');
  const status = searchParams.get('status') || searchParams.get('Status');

  const [verificationResult, setVerificationResult] = useState<VerifyTopupData | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [dismissed, setDismissed] = useState<boolean>(false);

  const verifyMutation = useVerifyTopup();

  useEffect(() => {
    if (!authority || dismissed) return;

    if (status === 'NOK') {
      setErrorMessage('پرداخت در درگاه لغو شد یا ناموفق بود.');
      return;
    }

    // Status is OK or authority present -> call verify
    verifyMutation.mutate(
      { authority },
      {
        onSuccess: (data) => {
          setVerificationResult(data);
          setErrorMessage(null);
        },
        onError: (err) => {
          setErrorMessage(err.message || 'خطا در تأیید تراکنش با درگاه.');
        },
      }
    );
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [authority, status]);

  if (dismissed || (!authority && !errorMessage && !verificationResult)) {
    return null;
  }

  const handleDismiss = () => {
    setDismissed(true);
    // Clear URL query parameters
    const newParams = new URLSearchParams(searchParams);
    newParams.delete('authority');
    newParams.delete('Authority');
    newParams.delete('status');
    newParams.delete('Status');
    setSearchParams(newParams, { replace: true });
  };

  if (verifyMutation.isPending) {
    return (
      <div className="flex items-center gap-3 p-4 rounded-2xl bg-brand-primary/10 border border-brand-primary/20 text-brand-primary" role="status">
        <RefreshCw className="w-5 h-5 animate-spin shrink-0" aria-hidden="true" />
        <div className="text-sm font-medium">
          در حال استعلام و تأیید امن تراکنش از درگاه پرداخت...
        </div>
      </div>
    );
  }

  if (verificationResult) {
    return (
      <div
        className="flex items-center justify-between p-4 rounded-2xl bg-status-success/10 border border-status-success/30 text-status-success"
        role="alert"
        data-testid="callback-success-banner"
      >
        <div className="flex items-center gap-3">
          <CheckCircle2 className="w-6 h-6 shrink-0 text-status-success" aria-hidden="true" />
          <div className="text-sm space-y-0.5">
            <p className="font-bold">شارژ کیف پول با موفقیت انجام شد!</p>
            <p className="text-xs text-text-secondary">
              مبلغ: <CurrencyText amountRials={verificationResult.amount_rials} unit="toman" />{' '}
              {verificationResult.ref_id && `· شماره پیگیری: ${verificationResult.ref_id}`}
            </p>
          </div>
        </div>
        <button
          type="button"
          onClick={handleDismiss}
          className="p-1 rounded-lg hover:bg-status-success/20 text-text-muted hover:text-text-primary"
          aria-label="بستن پیام"
        >
          <X className="w-4 h-4" aria-hidden="true" />
        </button>
      </div>
    );
  }

  if (errorMessage) {
    return (
      <div
        className="flex items-center justify-between p-4 rounded-2xl bg-status-danger/10 border border-status-danger/30 text-status-danger"
        role="alert"
        data-testid="callback-error-banner"
      >
        <div className="flex items-center gap-3">
          <AlertCircle className="w-6 h-6 shrink-0 text-status-danger" aria-hidden="true" />
          <div className="text-sm space-y-0.5">
            <p className="font-bold">تراکنش ناموفق</p>
            <p className="text-xs text-text-secondary">{errorMessage}</p>
          </div>
        </div>
        <button
          type="button"
          onClick={handleDismiss}
          className="p-1 rounded-lg hover:bg-status-danger/20 text-text-muted hover:text-text-primary"
          aria-label="بستن پیام"
        >
          <X className="w-4 h-4" aria-hidden="true" />
        </button>
      </div>
    );
  }

  return null;
};
