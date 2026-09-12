import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@pishkhan/ui-kit';
import type { CaseReturnReasonInfo } from '../types';

export interface CaseStatusBannerProps {
  returnReason?: CaseReturnReasonInfo | null | undefined;
  onFixAction?: (() => void) | undefined;
}

interface SampleImageModalProps {
  imageUrl: string;
  onClose: () => void;
}

function SampleImageModal({ imageUrl, onClose }: SampleImageModalProps): React.JSX.Element {
  const { t } = useTranslation();
  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label={t('cases.sample_image_modal_title')}
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
    >
      <div className="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-4 border border-slate-200 dark:border-slate-800 shadow-xl space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">
            {t('cases.sample_image_modal_title')}
          </h3>
          <button
            type="button"
            onClick={onClose}
            className="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 px-2 py-1"
            aria-label={t('common.close')}
          >
            {t('common.close')}
          </button>
        </div>

        <div className="rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center min-h-[180px]">
          <img
            src={imageUrl}
            alt={t('cases.sample_image_alt')}
            className="max-h-72 w-auto object-contain"
          />
        </div>

        <p className="text-xs text-slate-500 text-center leading-relaxed">
          {t('cases.sample_image_guide')}
        </p>
      </div>
    </div>
  );
}

function ReturnReasonDetails({ returnReason }: { returnReason: CaseReturnReasonInfo }): React.JSX.Element {
  const { t } = useTranslation();
  const reasonCodeText = `کد نقص: ${returnReason.code}`;
  const operatorNotePrefix = 'توضیح کارشناس: ';

  return (
    <div className="space-y-1.5 flex-1">
      <div className="flex items-center gap-2">
        <span className="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse" aria-hidden="true" />
        <h2 className="text-sm font-bold text-amber-900 dark:text-amber-200">
          {returnReason.title || t('cases.action_required_title')}
        </h2>
        <span className="text-xs px-2 py-0.5 rounded bg-amber-200/80 dark:bg-amber-900/80 text-amber-900 dark:text-amber-100 font-mono font-medium">
          {reasonCodeText}
        </span>
      </div>

      <p className="text-xs text-amber-800 dark:text-amber-300 leading-relaxed">
        {returnReason.message}
      </p>

      {returnReason.operator_note ? (
        <div className="mt-2 p-2.5 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-amber-200/60 dark:border-amber-800/40 text-xs text-amber-900 dark:text-amber-200">
          <span className="font-semibold">{operatorNotePrefix}</span>
          <span>{returnReason.operator_note}</span>
        </div>
      ) : null}
    </div>
  );
}

function ReturnReasonActions({
  hasSample,
  onOpenSample,
  onFix,
}: {
  hasSample: boolean;
  onOpenSample: () => void;
  onFix?: (() => void) | undefined;
}): React.JSX.Element {
  const { t } = useTranslation();
  return (
    <div className="flex items-center gap-2 self-end sm:self-center shrink-0">
      {hasSample ? (
        <Button variant="secondary" size="sm" onClick={onOpenSample} aria-label={t('cases.view_sample_image')}>
          {t('cases.view_sample_image')}
        </Button>
      ) : null}
      {onFix ? (
        <Button variant="primary" size="sm" onClick={onFix} aria-label={t('cases.fix_document_btn')}>
          {t('cases.fix_document_btn')}
        </Button>
      ) : null}
    </div>
  );
}

export function CaseStatusBanner({ returnReason, onFixAction }: CaseStatusBannerProps): React.JSX.Element | null {
  const [showModal, setShowModal] = useState<boolean>(false);

  if (!returnReason) return null;

  return (
    <div
      role="alert"
      className="p-4 rounded-2xl bg-amber-500/10 border-2 border-amber-500/30 dark:bg-amber-950/40 dark:border-amber-700/60 shadow-sm transition-all"
    >
      <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
        <ReturnReasonDetails returnReason={returnReason} />
        <ReturnReasonActions
          hasSample={Boolean(returnReason.sample_image_url)}
          onOpenSample={() => setShowModal(true)}
          onFix={onFixAction}
        />
      </div>

      {showModal && returnReason.sample_image_url ? (
        <SampleImageModal imageUrl={returnReason.sample_image_url} onClose={() => setShowModal(false)} />
      ) : null}
    </div>
  );
}
