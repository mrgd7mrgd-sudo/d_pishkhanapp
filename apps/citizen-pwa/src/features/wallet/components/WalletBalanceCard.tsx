import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Button, CurrencyText, Skeleton } from '@pishkhan/ui-kit';
import { useWalletBalance } from '../hooks/useWallet';
import { useNetworkStatus } from '@/shared/offline/useNetworkStatus';
import { TopupModal } from './TopupModal';
import { Wallet, PlusCircle, History, RefreshCw, WifiOff } from 'lucide-react';

export const WalletBalanceCard: React.FC = () => {
  const { data: balance, isLoading, isFetching, refetch } = useWalletBalance();
  const { isOnline } = useNetworkStatus();
  const [isTopupOpen, setIsTopupOpen] = useState<boolean>(false);

  const balanceRials = balance?.balance_rials ?? 0;

  return (
    <>
      <div
        className="relative overflow-hidden rounded-3xl p-6 bg-gradient-to-br from-brand-primary/15 via-surface-card to-brand-primary/5 border border-brand-primary/20 shadow-lg space-y-6"
        dir="rtl"
        data-testid="wallet-balance-card"
      >
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <div className="w-10 h-10 rounded-2xl bg-brand-primary/10 flex items-center justify-center text-brand-primary">
              <Wallet className="w-5 h-5" aria-hidden="true" />
            </div>
            <div>
              <h2 className="text-sm font-semibold text-text-secondary">موجودی کیف پول شما</h2>
              <p className="text-xs text-text-muted">سامانه خدمات شهروندی و پیشخوان هوشمند</p>
            </div>
          </div>

          <button
            type="button"
            onClick={() => void refetch()}
            disabled={isFetching}
            className="p-2 rounded-xl text-text-muted hover:bg-surface-hover hover:text-text-primary transition-all disabled:opacity-50"
            aria-label="به‌روزرسانی موجودی"
          >
            <RefreshCw className={`w-4 h-4 ${isFetching ? 'animate-spin text-brand-primary' : ''}`} aria-hidden="true" />
          </button>
        </div>

        <div className="space-y-1">
          {isLoading ? (
            <Skeleton className="h-12 w-48 rounded-xl" />
          ) : (
            <div className="flex items-baseline gap-2">
              <span className="text-3xl sm:text-4xl font-black text-text-primary tracking-tight font-vazirmatn" data-testid="wallet-balance-display">
                <CurrencyText amountRials={balanceRials} unit="toman" />
              </span>
            </div>
          )}
          <p className="text-xs text-text-muted">
            معادل <CurrencyText amountRials={balanceRials} unit="rial" />
          </p>
        </div>

        {!isOnline && (
          <div
            className="flex items-center gap-2 text-xs font-medium text-status-warning bg-status-warning/10 p-2.5 rounded-xl border border-status-warning/20"
            role="status"
            data-testid="offline-wallet-warning"
          >
            <WifiOff className="w-4 h-4 shrink-0" aria-hidden="true" />
            <span>شارژ کیف پول نیازمند اتصال اینترنت است. (حالت آفلاین)</span>
          </div>
        )}

        <div className="flex items-center gap-3 pt-2">
          <Button
            variant="primary"
            onClick={() => setIsTopupOpen(true)}
            disabled={!isOnline}
            className="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl shadow-md"
            data-testid="open-topup-btn"
            title={!isOnline ? 'شارژ کیف پول نیازمند اتصال اینترنت است' : undefined}
          >
            <PlusCircle className="w-4 h-4" aria-hidden="true" />
            <span>افزایش موجودی</span>
          </Button>

          <Link
            to="/wallet/transactions"
            className="flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-border-default bg-surface-card hover:bg-surface-hover text-text-secondary text-sm font-medium transition-all"
            data-testid="view-transactions-link"
          >
            <History className="w-4 h-4" aria-hidden="true" />
            <span>تراکنش‌ها</span>
          </Link>
        </div>
      </div>

      <TopupModal isOpen={isTopupOpen} onClose={() => setIsTopupOpen(false)} />
    </>
  );
};
