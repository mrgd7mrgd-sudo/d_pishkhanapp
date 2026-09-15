import React, { useState } from 'react';
import { Button, CurrencyText, Input } from '@pishkhan/ui-kit';
import { useTopupIntent } from '../hooks/useWallet';
import { CreditCard, X, AlertCircle } from 'lucide-react';

interface TopupModalProps {
  isOpen: boolean;
  onClose: () => void;
}

const PRESET_AMOUNTS_TOMAN = [50_000, 100_000, 200_000, 500_000];

export const TopupModal: React.FC<TopupModalProps> = ({ isOpen, onClose }) => {
  const [selectedToman, setSelectedToman] = useState<number>(100_000);
  const [customToman, setCustomToman] = useState<string>('');
  const [gateway, setGateway] = useState<'zarinpal' | 'zibal'>('zarinpal');
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const topupMutation = useTopupIntent();

  if (!isOpen) return null;

  const effectiveToman = customToman !== '' ? parseInt(customToman, 10) || 0 : selectedToman;
  const effectiveRials = effectiveToman * 10;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);

    if (effectiveToman < 10_000) {
      setErrorMsg('حداقل مبلغ شارژ ۱۰٬۰۰۰ تومان (۱۰۰٬۰۰۰ ریال) است.');
      return;
    }

    if (effectiveToman > 50_000_000) {
      setErrorMsg('حداکثر مبلغ شارژ در هر تراکنش ۵۰٬۰۰۰٬۰۰۰ تومان است.');
      return;
    }

    const returnUrl = `${window.location.origin}/wallet`;

    topupMutation.mutate(
      {
        amount_rials: effectiveRials,
        return_url: returnUrl,
        gateway,
      },
      {
        onSuccess: (data) => {
          if (data.redirect_url) {
            window.location.href = data.redirect_url;
          }
        },
        onError: (err) => {
          setErrorMsg(err.message || 'خطا در ارتباط با درگاه پرداخت.');
        },
      }
    );
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs"
      role="dialog"
      aria-modal="true"
      aria-labelledby="topup-modal-title"
      dir="rtl"
    >
      <div className="w-full max-w-md rounded-2xl bg-surface-card p-6 shadow-xl border border-border-default space-y-5">
        <div className="flex items-center justify-between border-b border-border-subtle pb-3">
          <div className="flex items-center gap-2">
            <CreditCard className="w-5 h-5 text-brand-primary" aria-hidden="true" />
            <h2 id="topup-modal-title" className="text-lg font-bold text-text-primary">
              افزایش موجودی کیف پول
            </h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg p-1 text-text-muted hover:bg-surface-hover hover:text-text-primary"
            aria-label="بستن پنجره"
          >
            <X className="w-5 h-5" aria-hidden="true" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <label className="text-sm font-medium text-text-secondary block">
              انتخاب مبالغ پیشنهادی (تومان)
            </label>
            <div className="grid grid-cols-2 gap-2">
              {PRESET_AMOUNTS_TOMAN.map((amount) => (
                <button
                  key={amount}
                  type="button"
                  onClick={() => {
                    setSelectedToman(amount);
                    setCustomToman('');
                  }}
                  className={`py-2 px-3 rounded-xl border text-sm font-medium transition-all ${
                    selectedToman === amount && customToman === ''
                      ? 'border-brand-primary bg-brand-primary/10 text-brand-primary font-bold'
                      : 'border-border-default bg-surface-muted text-text-secondary hover:bg-surface-hover'
                  }`}
                >
                  <CurrencyText amountRials={amount * 10} unit="toman" />
                </button>
              ))}
            </div>
          </div>

          <div className="space-y-1">
            <label htmlFor="custom-toman-input" className="text-sm font-medium text-text-secondary block">
              یا مبلغ دلخواه (تومان)
            </label>
            <Input
              id="custom-toman-input"
              type="number"
              placeholder="مثال: ۱۰۰۰۰۰"
              value={customToman}
              onChange={(e) => {
                setCustomToman(e.target.value);
                setSelectedToman(0);
              }}
              min={10_000}
              max={50_000_000}
            />
          </div>

          {effectiveToman > 0 && (
            <div className="rounded-xl bg-surface-muted p-3 text-center border border-border-subtle">
              <span className="text-xs text-text-muted block">مبلغ نهایی پرداختی در درگاه (ریال):</span>
              <span className="text-base font-bold text-brand-primary" data-testid="final-payment-amount">
                <CurrencyText amountRials={effectiveRials} unit="rial" />
              </span>
            </div>
          )}

          <div className="space-y-2">
            <label className="text-sm font-medium text-text-secondary block">
              انتخاب درگاه پرداخت
            </label>
            <div className="grid grid-cols-2 gap-2">
              <label
                className={`flex items-center gap-2 p-3 rounded-xl border cursor-pointer text-sm font-medium ${
                  gateway === 'zarinpal'
                    ? 'border-brand-primary bg-brand-primary/5 text-brand-primary'
                    : 'border-border-default'
                }`}
              >
                <input
                  type="radio"
                  name="gateway"
                  value="zarinpal"
                  checked={gateway === 'zarinpal'}
                  onChange={() => setGateway('zarinpal')}
                  className="sr-only"
                />
                <span>زرین‌پال</span>
              </label>

              <label
                className={`flex items-center gap-2 p-3 rounded-xl border cursor-pointer text-sm font-medium ${
                  gateway === 'zibal'
                    ? 'border-brand-primary bg-brand-primary/5 text-brand-primary'
                    : 'border-border-default'
                }`}
              >
                <input
                  type="radio"
                  name="gateway"
                  value="zibal"
                  checked={gateway === 'zibal'}
                  onChange={() => setGateway('zibal')}
                  className="sr-only"
                />
                <span>زیبال</span>
              </label>
            </div>
          </div>

          {errorMsg && (
            <div className="flex items-center gap-2 text-sm text-status-danger bg-status-danger/10 p-3 rounded-xl" role="alert">
              <AlertCircle className="w-4 h-4 shrink-0" aria-hidden="true" />
              <span>{errorMsg}</span>
            </div>
          )}

          <div className="flex items-center justify-end gap-3 pt-2">
            <Button variant="ghost" type="button" onClick={onClose} disabled={topupMutation.isPending}>
              انصراف
            </Button>
            <Button
              variant="primary"
              type="submit"
              isLoading={topupMutation.isPending}
              disabled={topupMutation.isPending || effectiveToman < 10_000}
              data-testid="submit-topup-btn"
            >
              انتقال به درگاه پرداخت
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};
