import React from 'react';
import { useTranslation } from 'react-i18next';
import { Check } from 'lucide-react';
import type { ServiceRequestStep } from '../types';

interface StepProgressBarProps {
  currentStep: ServiceRequestStep;
}

interface StepItem {
  key: ServiceRequestStep;
  labelKey: string;
  index: number;
}

const STEPS: StepItem[] = [
  { key: 'info', labelKey: 'request.step_info', index: 1 },
  { key: 'dispatch_type', labelKey: 'request.step_dispatch', index: 2 },
  { key: 'payment', labelKey: 'request.step_payment', index: 3 },
  { key: 'searching', labelKey: 'request.step_searching', index: 4 },
  { key: 'assigned', labelKey: 'request.step_assigned', index: 5 },
];

export const StepProgressBar: React.FC<StepProgressBarProps> = ({ currentStep }) => {
  const { t } = useTranslation();
  const currentIndex = STEPS.findIndex((s) => s.key === currentStep);

  return (
    <nav aria-label={t('request.steps_nav')} className="w-full py-4 mb-6">
      <ol className="flex items-center justify-between w-full relative">
        <div
          className="absolute top-1/2 -translate-y-1/2 start-4 end-4 h-0.5 bg-slate-200 dark:bg-slate-700 -z-0"
          aria-hidden="true"
        />
        {STEPS.map((step, idx) => {
          const isDone = idx < currentIndex;
          const isCurrent = idx === currentIndex;

          return (
            <li
              key={step.key}
              aria-current={isCurrent ? 'step' : undefined}
              className="flex flex-col items-center relative z-10"
            >
              <div
                className={`flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold transition-all duration-200 ${
                  isDone
                    ? 'bg-emerald-600 text-white shadow-sm'
                    : isCurrent
                      ? 'bg-sky-600 text-white ring-4 ring-sky-100 dark:ring-sky-950'
                      : 'bg-white dark:bg-slate-800 text-slate-400 border border-slate-300 dark:border-slate-600'
                }`}
              >
                {isDone ? <Check className="w-4 h-4" aria-hidden="true" /> : step.index}
              </div>
              <span
                className={`mt-1.5 text-[11px] font-medium hidden sm:inline-block ${
                  isCurrent
                    ? 'text-sky-700 dark:text-sky-300 font-bold'
                    : isDone
                      ? 'text-slate-700 dark:text-slate-300'
                      : 'text-slate-400'
                }`}
              >
                {t(step.labelKey)}
              </span>
            </li>
          );
        })}
      </ol>
    </nav>
  );
};
