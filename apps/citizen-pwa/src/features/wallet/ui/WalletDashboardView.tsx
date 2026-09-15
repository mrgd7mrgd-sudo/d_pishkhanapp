import React from 'react';
import { Link } from 'react-router-dom';
import { WalletBalanceCard } from '../components/WalletBalanceCard';
import { GatewayCallbackBanner } from '../components/GatewayCallbackBanner';
import { TransactionItem } from '../components/TransactionItem';
import { useWalletTransactions } from '../hooks/useWallet';
import { Skeleton } from '@pishkhan/ui-kit';
import { ArrowLeft, Inbox } from 'lucide-react';

export const WalletDashboardView: React.FC = () => {
  const { data: transactions, isLoading } = useWalletTransactions();

  const recentTransactions = transactions?.slice(0, 5) ?? [];

  return (
    <div className="max-w-2xl mx-auto px-4 py-6 space-y-6" dir="rtl">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-text-primary">کیف پول شهروندی</h1>
        <Link
          to="/services"
          className="text-xs text-brand-primary font-medium hover:underline flex items-center gap-1"
        >
          <span>ثبت درخواست خدمت</span>
          <ArrowLeft className="w-3.5 h-3.5" aria-hidden="true" />
        </Link>
      </div>

      <GatewayCallbackBanner />

      <WalletBalanceCard />

      <div className="space-y-3 pt-2">
        <div className="flex items-center justify-between">
          <h2 className="text-base font-bold text-text-primary">تراکنش‌های اخیر</h2>
          <Link
            to="/wallet/transactions"
            className="text-xs text-brand-primary font-medium hover:underline"
          >
            مشاهده همه
          </Link>
        </div>

        {isLoading ? (
          <div className="space-y-2">
            <Skeleton className="h-16 w-full rounded-xl" />
            <Skeleton className="h-16 w-full rounded-xl" />
          </div>
        ) : recentTransactions.length > 0 ? (
          <div className="space-y-2.5">
            {recentTransactions.map((tx) => (
              <TransactionItem key={tx.id} transaction={tx} />
            ))}
          </div>
        ) : (
          <div className="text-center py-8 rounded-2xl border border-dashed border-border-default bg-surface-muted p-6 space-y-2">
            <Inbox className="w-8 h-8 text-text-muted mx-auto" aria-hidden="true" />
            <p className="text-sm text-text-secondary font-medium">هنوز تراکنشی ثبت نشده است</p>
            <p className="text-xs text-text-muted">
              با افزایش موجودی یا پرداخت هزینه خدمات، تاریخچه تراکنش‌ها در اینجا نمایش داده می‌شود.
            </p>
          </div>
        )}
      </div>
    </div>
  );
};
