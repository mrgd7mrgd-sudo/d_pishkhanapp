import React from 'react';
import { DeskCaseDetail } from '../types';
import { StatusPill, TurnOwnerChip, Button } from '@pishkhan/ui-kit';
import { FileText, CheckCircle2 } from 'lucide-react';

export interface CaseWorkspaceHeaderProps {
  caseDetail: DeskCaseDetail;
  availableActions: string[];
  reviewLoading: boolean;
  completeLoading: boolean;
  inquiryLoading: boolean;
  rejectLoading: boolean;
  onStartReview: () => void;
  onOpenReturn: () => void;
  onRequestInquiry: (provider: 'civil_registry' | 'shahkar' | 'post') => void;
  onComplete: () => void;
  onReject: () => void;
  onClose?: (() => void) | undefined;
}

export const CaseWorkspaceHeader: React.FC<CaseWorkspaceHeaderProps> = ({
  caseDetail,
  availableActions,
  reviewLoading,
  completeLoading,
  inquiryLoading,
  rejectLoading,
  onStartReview,
  onOpenReturn,
  onRequestInquiry,
  onComplete,
  onReject,
  onClose,
}) => {
  return (
    <div className="bg-white border border-slate-200 rounded-xl p-4 flex flex-wrap items-center justify-between gap-3 shadow-2xs">
      <div className="flex items-center gap-3">
        <div className="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-700">
          <FileText className="w-5 h-5" />
        </div>
        <div>
          <div className="flex items-center gap-2 mb-0.5">
            <h2 className="text-base font-bold text-slate-900 font-mono">
              {caseDetail.tracking_code}
            </h2>
            <StatusPill status={caseDetail.status} />
            <TurnOwnerChip owner={caseDetail.turn_owner} />
          </div>
          <p className="text-xs text-slate-500">
            {caseDetail.service?.title} · ایجاد شده در:{' '}
            {new Date(caseDetail.created_at).toLocaleDateString('fa-IR')}
          </p>
        </div>
      </div>

      <div className="flex items-center flex-wrap gap-2">
        {availableActions.includes('review') && (
          <Button
            size="sm"
            variant="primary"
            isLoading={reviewLoading}
            onClick={onStartReview}
          >
            شروع بررسی
          </Button>
        )}

        {availableActions.includes('return') && (
          <Button size="sm" variant="danger" onClick={onOpenReturn}>
            بازگرداندن مدرک
          </Button>
        )}

        {availableActions.includes('inquiry') && (
          <Button
            size="sm"
            variant="secondary"
            isLoading={inquiryLoading}
            onClick={() => onRequestInquiry('civil_registry')}
          >
            استعلام ثبت احوال
          </Button>
        )}

        {availableActions.includes('complete') && (
          <Button
            size="sm"
            variant="primary"
            className="bg-emerald-600 hover:bg-emerald-700"
            isLoading={completeLoading}
            onClick={onComplete}
          >
            <CheckCircle2 className="w-4 h-4 ml-1" />
            تکمیل نهایی پرونده
          </Button>
        )}

        {availableActions.includes('reject') && (
          <Button
            size="sm"
            variant="danger"
            isLoading={rejectLoading}
            onClick={onReject}
          >
            رد پرونده
          </Button>
        )}

        {onClose && (
          <Button size="sm" variant="ghost" onClick={onClose}>
            بستن
          </Button>
        )}
      </div>
    </div>
  );
};
