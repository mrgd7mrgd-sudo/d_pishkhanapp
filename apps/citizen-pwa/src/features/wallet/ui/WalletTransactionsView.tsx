import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useWalletTransactions } from '../hooks/useWallet';
import { TransactionItem } from '../components/TransactionItem';
import { Skeleton } from '@pishkhan/ui-kit';
import { ArrowRight, Inbox } from 'lucide-react';
import type { TransactionType } from '../types';

type FilterTab = 'all' | 'topup' | 'service_fee' | 'refund';

export const WalletTransactionsView: React.FC = () => {
  const { data: transactions, isLoading } = useWalletTransactions();
  const [activeTab, setActiveTab] = useState<FilterTab>('all');

  const filteredTransactions = (transactions ?? []).filter((tx) => {
    if (activeTab === 'all') return true;
    if (activeTab === 'topup') return tx.type === 'topup';
    if (activeTab === 'service_fee') {
      return (
        tx.type === 'service_fee' ||
        tx.type === 'consultation_fee' ||
        tx.type === 'shipping_fee'
      );
    }
    if (activeTab === 'refund') return tx.type === 'refund';
    return true;
  });

  return (
    <div className="max-w-2xl mx-auto px-4 py-6 space-y-6" dir="rtl">
      <div className="flex items-center gap-3">
        <Link
          to="/wallet"
          className="p-2 rounded-xl border border-border-default bg-surface-card hover:bg-surface-hover text-text-muted hover:text-text-primary transition-all"
          aria-label="بازگشت به کیف پول"
        >
          <ArrowRight className="w-5 h-5" aria-hidden="true" />
        </Link>
        <div>
          <h1 className="text-xl font-bold text-text-primary">تاریخچه تراکنش‌های کیف پول</h1>
          <p className="text-xs text-text-muted">ریز ورود و خروج مبالغ در دفتر کل</p>
        </div>
      </div>

      <div className="flex items-center gap-2 border-b border-border-default pb-2 overflow-x-auto" role="tablist">
        <button
          type="button"
          role="tab"
          aria-selected={activeTab === 'all'}
          onClick={() => setActiveTab('all')}
          className={`px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${
            activeTab === 'all'
              ? 'bg-brand-primary text-white shadow-xs'
              : 'text-text-muted hover:text-text-primary hover:bg-surface-hover'
          }`}
        >
          همه تراکنش‌ها
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={activeTab === 'topup'}
          onClick={() => setActiveTab('topup')}
          className={`px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${
            activeTab === 'topup'
              ? 'bg-brand-primary text-white shadow-xs'
              : 'text-text-muted hover:text-text-primary hover:bg-surface-hover'
          }`}
        >
          شارژها (واریز)
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={activeTab === 'service_fee'}
          onClick={() => setActiveTab('service_fee')}
          className={`px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${
            activeTab === 'service_fee'
              ? 'bg-brand-primary text-white shadow-xs'
              : 'text-text-muted hover:text-text-primary hover:bg-surface-hover'
          }`}
        >
          کارمزدها (برداشت)
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={activeTab === 'refund'}
          onClick={() => setActiveTab('refund')}
          className={`px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${
            activeTab === 'refund'
              ? 'bg-brand-primary text-white shadow-xs'
              : 'text-text-muted hover:text-text-primary hover:bg-surface-hover'
          }`}
        >
          استردادها
        </button>
      </div>

      {isLoading ? (
        <div className="space-y-3">
          <Skeleton className="h-16 w-full rounded-xl" />
          <Skeleton className="h-16 w-full rounded-xl" />
          <Skeleton className="h-16 w-full rounded-xl" />
        </div>
      ) : filteredTransactions.length > 0 ? (
        <div className="space-y-2.5">
          {filteredTransactions.map((tx) => (
            <TransactionItem key={tx.id} transaction={tx} />
          ))}
        </div>
      ) : (
        <div className="text-center py-12 rounded-2xl border border-dashed border-border-default bg-surface-muted p-6 space-y-2">
          <Inbox className="w-10 h-10 text-text-muted mx-auto" aria-hidden="true" />
          <p className="text-sm font-semibold text-text-secondary">تراکنشی در این دسته‌بندی یافت نشد</p>
          <p className="text-xs text-text-muted">
            تراکنش‌های منطبق با فیلتر انتخابی شما در اینجا نمایش داده خواهند شد.
          </p>
        </div>
      )}
    </div>
  );
};
