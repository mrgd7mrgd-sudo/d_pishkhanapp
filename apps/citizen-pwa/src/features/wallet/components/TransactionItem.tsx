import React from 'react';
import type { WalletTransaction } from '../types';
import { CurrencyText } from '@pishkhan/ui-kit';
import { ArrowDownLeft, ArrowUpRight, RotateCcw } from 'lucide-react';

interface TransactionItemProps {
  transaction: WalletTransaction;
}

const getTxMeta = (tx: WalletTransaction) => {
  switch (tx.type) {
    case 'topup':
      return {
        label: 'شارژ کیف پول',
        icon: ArrowDownLeft,
        color: 'text-status-success bg-status-success/10',
      };
    case 'refund':
      return {
        label: 'استرداد وجه',
        icon: RotateCcw,
        color: 'text-brand-primary bg-brand-primary/10',
      };
    case 'service_fee':
      return {
        label: 'کارمزد خدمت',
        icon: ArrowUpRight,
        color: 'text-status-danger bg-status-danger/10',
      };
    case 'consultation_fee':
      return {
        label: 'کارمزد مشاوره',
        icon: ArrowUpRight,
        color: 'text-status-danger bg-status-danger/10',
      };
    case 'shipping_fee':
      return {
        label: 'هزینه ارسال پیک',
        icon: ArrowUpRight,
        color: 'text-status-danger bg-status-danger/10',
      };
    default:
      return {
        label: tx.direction === 'credit' ? 'واریز' : 'برداشت',
        icon: tx.direction === 'credit' ? ArrowDownLeft : ArrowUpRight,
        color: tx.direction === 'credit' ? 'text-status-success bg-status-success/10' : 'text-status-danger bg-status-danger/10',
      };
  }
};

export const TransactionItem: React.FC<TransactionItemProps> = ({ transaction }) => {
  const meta = getTxMeta(transaction);
  const Icon = meta.icon;
  const isCredit = transaction.direction === 'credit';

  const formattedDate = new Date(transaction.created_at).toLocaleDateString('fa-IR', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });

  return (
    <div
      className="flex items-center justify-between p-3.5 rounded-xl border border-border-default bg-surface-card hover:bg-surface-hover transition-colors"
      dir="rtl"
      data-testid={`transaction-item-${transaction.id}`}
    >
      <div className="flex items-center gap-3">
        <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${meta.color}`}>
          <Icon className="w-5 h-5" aria-hidden="true" />
        </div>
        <div className="space-y-0.5">
          <p className="text-sm font-bold text-text-primary">{meta.label}</p>
          <p className="text-xs text-text-muted">
            {formattedDate} · <span className="font-mono text-xs">{transaction.reference}</span>
          </p>
        </div>
      </div>

      <div className="text-left space-y-0.5">
        <div className={`text-sm font-bold flex items-center gap-0.5 justify-end ${isCredit ? 'text-status-success' : 'text-status-danger'}`}>
          <span>{isCredit ? '+' : '-'}</span>
          <CurrencyText amountRials={transaction.amount_rials} unit="toman" />
        </div>
        {transaction.description && (
          <p className="text-xs text-text-muted truncate max-w-[140px] text-left">
            {transaction.description}
          </p>
        )}
      </div>
    </div>
  );
};
