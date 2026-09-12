import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { Wallet, CreditCard, AlertTriangle, ArrowUpRight, CheckCircle2 } from 'lucide-react';
import type { PaymentMethod, ServiceDetail, ServiceRequestFormData } from '../types';
import { serviceRequestApi } from '../api/serviceRequestApi';

interface StepPaymentProps {
  service: ServiceDetail;
  formData: ServiceRequestFormData;
  onUpdateFormData: (updates: Partial<ServiceRequestFormData>) => void;
}

const PaymentFeeHeader: React.FC<{ feeRials: number; walletBalance: number; isLoading: boolean }> = ({
  feeRials,
  walletBalance,
  isLoading,
}) => {
  const { t } = useTranslation();
  return (
    <div className="p-4 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-between">
      <div>
        <span className="text-xs text-slate-500 dark:text-slate-400 block">{t('request.payable_amount')}</span>
        <span className="text-base font-bold text-slate-900 dark:text-slate-100">
          {feeRials.toLocaleString('fa-IR')} {t('catalog.rials')}
        </span>
      </div>
      <div className="text-end">
        <span className="text-xs text-slate-500 dark:text-slate-400 block">{t('request.current_wallet_balance')}</span>
        <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400" data-testid="wallet-balance-display">
          {isLoading ? '...' : `${walletBalance.toLocaleString('fa-IR')} ${t('catalog.rials')}`}
        </span>
      </div>
    </div>
  );
};

const PaymentMethodOptions: React.FC<{
  selected: PaymentMethod;
  onSelect: (m: PaymentMethod) => void;
}> = ({ selected, onSelect }) => {
  const { t } = useTranslation();
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
      <button
        type="button"
        onClick={() => onSelect('wallet')}
        className={`p-4 rounded-2xl border text-start transition-all flex flex-col justify-between ${
          selected === 'wallet'
            ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/30 ring-2 ring-emerald-500/20'
            : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50'
        }`}
      >
        <div className="flex items-start justify-between w-full mb-3">
          <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600">
            <Wallet className="w-5 h-5" aria-hidden="true" />
          </div>
          {selected === 'wallet' && <CheckCircle2 className="w-5 h-5 text-emerald-600" aria-hidden="true" />}
        </div>
        <div>
          <span className="text-sm font-bold text-slate-900 dark:text-slate-100 block mb-1">{t('request.payment_wallet')}</span>
          <span className="text-xs text-slate-500">{t('request.wallet_desc')}</span>
        </div>
      </button>

      <button
        type="button"
        onClick={() => onSelect('gateway')}
        className={`p-4 rounded-2xl border text-start transition-all flex flex-col justify-between ${
          selected === 'gateway'
            ? 'border-sky-500 bg-sky-50/50 dark:bg-sky-950/30 ring-2 ring-sky-500/20'
            : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50'
        }`}
      >
        <div className="flex items-start justify-between w-full mb-3">
          <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-900/50 text-sky-600">
            <CreditCard className="w-5 h-5" aria-hidden="true" />
          </div>
          {selected === 'gateway' && <CheckCircle2 className="w-5 h-5 text-sky-600" aria-hidden="true" />}
        </div>
        <div>
          <span className="text-sm font-bold text-slate-900 dark:text-slate-100 block mb-1">{t('request.payment_gateway')}</span>
          <span className="text-xs text-slate-500">{t('request.gateway_desc')}</span>
        </div>
      </button>
    </div>
  );
};

export const StepPayment: React.FC<StepPaymentProps> = ({ service, formData, onUpdateFormData }) => {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [walletBalance, setWalletBalance] = useState<number>(0);
  const [isLoadingBalance, setIsLoadingBalance] = useState(true);

  useEffect(() => {
    let isMounted = true;
    const fetchBalance = async (): Promise<void> => {
      setIsLoadingBalance(true);
      try {
        const balance = await serviceRequestApi.getWalletBalance();
        if (isMounted) setWalletBalance(balance);
      } finally {
        if (isMounted) setIsLoadingBalance(false);
      }
    };
    void fetchBalance();
    return () => {
      isMounted = false;
    };
  }, []);

  const isWalletInsufficient = walletBalance < service.fee_rials;

  return (
    <div className="space-y-5">
      <PaymentFeeHeader feeRials={service.fee_rials} walletBalance={walletBalance} isLoading={isLoadingBalance} />
      <PaymentMethodOptions selected={formData.paymentMethod} onSelect={(m) => onUpdateFormData({ paymentMethod: m })} />

      {formData.paymentMethod === 'wallet' && isWalletInsufficient && (
        <div
          role="alert"
          className="p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-2xl space-y-3"
          data-testid="wallet-insufficient-warning"
        >
          <div className="flex items-start gap-2.5 text-amber-800 dark:text-amber-200">
            <AlertTriangle className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" aria-hidden="true" />
            <p className="text-xs leading-relaxed font-medium">{t('request.wallet_insufficient')}</p>
          </div>
          <button
            type="button"
            onClick={() => navigate('/wallet')}
            className="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition-colors"
          >
            <span>{t('request.charge_wallet')}</span>
            <ArrowUpRight className="w-4 h-4" aria-hidden="true" />
          </button>
        </div>
      )}
    </div>
  );
};
