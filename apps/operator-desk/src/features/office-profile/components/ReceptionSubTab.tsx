import React, { useState } from 'react';
import { Button } from '@pishkhan/ui-kit';
import { Users, UserPlus, UserCheck, UserX, Clock, Shield } from 'lucide-react';
import type { OfficeOperatorItem, CreateOperatorPayload } from '../types';

export interface ReceptionSubTabProps {
  operators: OfficeOperatorItem[];
  onCreateOperator: (payload: CreateOperatorPayload) => Promise<void>;
  onToggleOperator: (id: string) => Promise<void>;
  isProcessing: boolean;
}

export const ReceptionSubTab: React.FC<ReceptionSubTabProps> = ({
  operators,
  onCreateOperator,
  onToggleOperator,
  isProcessing,
}) => {
  const [showAddForm, setShowAddForm] = useState(false);
  const [username, setUsername] = useState('');
  const [fullName, setFullName] = useState('');
  const [counterNumber, setCounterNumber] = useState(1);
  const [password, setPassword] = useState('');
  const [role, setRole] = useState<'operator' | 'manager'>('operator');
  const [error, setError] = useState<string | null>(null);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (password.length < 8) {
      setError('رمز عبور باید حداقل ۸ کاراکتر باشد.');
      return;
    }
    setError(null);
    try {
      await onCreateOperator({
        username: username.trim(),
        full_name: fullName.trim(),
        counter_number: Number(counterNumber),
        password,
        role,
      });
      setShowAddForm(false);
      setUsername('');
      setFullName('');
      setPassword('');
      setCounterNumber(1);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'خطا در ثبت اپراتور');
    }
  };

  return (
    <section aria-labelledby="reception-subtab-title" className="space-y-6">
      <h2 id="reception-subtab-title" className="sr-only">مدیریت پرسنل و باجه‌های پذیرش</h2>

      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <Users className="w-4 h-4 text-blue-600" aria-hidden="true" />
            <span>پرسنل و اپراتورهای باجه‌های دفتر</span>
          </h3>
          <p className="text-xs text-slate-500 mt-0.5">
            تعریف حساب‌های کاربری اپراتورها و تعیین باجه تخصیص‌یافته برای پاسخگویی حضوری
          </p>
        </div>

        <Button
          variant="primary"
          size="sm"
          onClick={() => setShowAddForm(!showAddForm)}
          data-testid="add-operator-btn"
        >
          <UserPlus className="w-3.5 h-3.5 me-1" aria-hidden="true" />
          <span>{showAddForm ? 'بستن فرم' : 'افزودن اپراتور جدید'}</span>
        </Button>
      </div>

      {showAddForm && (
        <form onSubmit={handleCreate} className="p-4 rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50/40 dark:bg-blue-950/20 space-y-4">
          <h4 className="text-xs font-bold text-blue-900 dark:text-blue-300">
            مشخصات اپراتور یا مدیر باجه جدید
          </h4>

          {error && (
            <div role="alert" className="p-2 text-xs rounded bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400">
              {error}
            </div>
          )}

          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
              <label htmlFor="op-username-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                نام کاربری <span className="text-rose-500">*</span>
              </label>
              <input
                id="op-username-input"
                type="text"
                required
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                placeholder="op_counter3"
                className="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden font-mono"
              />
            </div>

            <div>
              <label htmlFor="op-fullname-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                نام و نام خانوادگی <span className="text-rose-500">*</span>
              </label>
              <input
                id="op-fullname-input"
                type="text"
                required
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
                placeholder="حامد صادقی"
                className="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden"
              />
            </div>

            <div>
              <label htmlFor="op-counter-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                شماره باجه <span className="text-rose-500">*</span>
              </label>
              <input
                id="op-counter-input"
                type="number"
                min={1}
                max={50}
                required
                value={counterNumber}
                onChange={(e) => setCounterNumber(Number(e.target.value))}
                className="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden font-mono"
              />
            </div>

            <div>
              <label htmlFor="op-role-select" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                سطح دسترسی
              </label>
              <select
                id="op-role-select"
                value={role}
                onChange={(e) => setRole(e.target.value as 'operator' | 'manager')}
                className="w-full text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden"
              >
                <option value="operator">اپراتور عادی باجه</option>
                <option value="manager">مدیر دفتر</option>
              </select>
            </div>
          </div>

          <div>
            <label htmlFor="op-password-input" className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              رمز عبور اولیه <span className="text-rose-500">*</span>
            </label>
            <input
              id="op-password-input"
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="حداقل ۸ کاراکتر"
              className="w-full max-w-xs text-xs p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-hidden font-mono"
            />
          </div>

          <div className="flex justify-end gap-2">
            <Button variant="secondary" size="sm" type="button" onClick={() => setShowAddForm(false)}>
              انصراف
            </Button>
            <Button variant="primary" size="sm" type="submit" disabled={isProcessing}>
              ثبت و ایجاد حساب
            </Button>
          </div>
        </form>
      )}

      {/* Operators Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
        {operators.map((op) => (
          <div
            key={op.id}
            data-testid={`operator-card-${op.id}`}
            className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs flex items-center justify-between"
          >
            <div className="space-y-1">
              <div className="flex items-center gap-2">
                <span className="text-xs font-bold text-slate-900 dark:text-white">
                  {op.full_name}
                </span>
                <span className="text-[10px] font-mono text-slate-500">(@{op.username})</span>
                {op.role === 'manager' && (
                  <span className="inline-flex items-center gap-0.5 text-[9px] bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 px-1.5 py-0.5 rounded-sm">
                    <Shield className="w-2.5 h-2.5" aria-hidden="true" />
                    مدیر دفتر
                  </span>
                )}
              </div>
              <div className="flex items-center gap-3 text-xs text-slate-500">
                <span>باجه شماره {op.counter_number}</span>
                {op.last_login_at && (
                  <span className="flex items-center gap-1 text-[10px]">
                    <Clock className="w-3 h-3" aria-hidden="true" />
                    آخرین ورود: {new Date(op.last_login_at).toLocaleDateString('fa-IR')}
                  </span>
                )}
              </div>
            </div>

            <button
              type="button"
              onClick={() => onToggleOperator(op.id)}
              disabled={isProcessing}
              data-testid={`toggle-op-${op.id}`}
              className={`p-2 rounded-lg text-xs font-medium flex items-center gap-1 transition-colors ${
                op.is_active
                  ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 hover:bg-emerald-100'
                  : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 hover:bg-rose-100'
              }`}
            >
              {op.is_active ? (
                <>
                  <UserCheck className="w-3.5 h-3.5" aria-hidden="true" />
                  <span>فعال</span>
                </>
              ) : (
                <>
                  <UserX className="w-3.5 h-3.5" aria-hidden="true" />
                  <span>غیرفعال</span>
                </>
              )}
            </button>
          </div>
        ))}
      </div>
    </section>
  );
};
