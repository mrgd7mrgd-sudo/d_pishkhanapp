import React, { useState } from 'react';
import { DeskCaseDetail, ReturnCasePayload } from '../types';
import { DocumentReviewPanel } from './DocumentReviewPanel';
import { ReturnModal } from './ReturnModal';
import { CaseWorkspaceHeader } from './CaseWorkspaceHeader';
import { Clock, Building2 } from 'lucide-react';
import { workspaceApi } from '../api/workspaceApi';

export interface CaseDetailWorkspaceProps {
  caseDetail: DeskCaseDetail;
  onRefresh?: () => Promise<void>;
  onClose?: () => void;
}

export const CaseDetailWorkspace: React.FC<CaseDetailWorkspaceProps> = ({
  caseDetail,
  onRefresh,
  onClose,
}) => {
  const [returnModalOpen, setReturnModalOpen] = useState(false);
  const [inquiryLoading, setInquiryLoading] = useState(false);
  const [completeLoading, setCompleteLoading] = useState(false);
  const [reviewLoading, setReviewLoading] = useState(false);
  const [rejectLoading, setRejectLoading] = useState(false);
  const [returnLoading, setReturnLoading] = useState(false);
  const [feedbackMsg, setFeedbackMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(
    null
  );

  const availableActions = caseDetail.available_actions || [];

  const handleStartReview = async () => {
    setReviewLoading(true);
    setFeedbackMsg(null);
    try {
      await workspaceApi.startReview(caseDetail.id);
      setFeedbackMsg({ type: 'success', text: 'بررسی پرونده با موفقیت آغاز شد.' });
      await onRefresh?.();
    } catch (err) {
      setFeedbackMsg({
        type: 'error',
        text: err instanceof Error ? err.message : 'خطا در آغاز بررسی پرونده',
      });
    } finally {
      setReviewLoading(false);
    }
  };

  const handleReturnSubmit = async (payload: ReturnCasePayload) => {
    setReturnLoading(true);
    setFeedbackMsg(null);
    try {
      await workspaceApi.returnCase(caseDetail.id, payload);
      setReturnModalOpen(false);
      setFeedbackMsg({
        type: 'success',
        text: 'پرونده جهت اصلاح با موفقیت برای متقاضی بازگردانده شد.',
      });
      await onRefresh?.();
    } catch (err) {
      setFeedbackMsg({
        type: 'error',
        text: err instanceof Error ? err.message : 'خطا در بازگرداندن پرونده',
      });
    } finally {
      setReturnLoading(false);
    }
  };

  const handleRequestInquiry = async (provider: 'civil_registry' | 'shahkar' | 'post') => {
    setInquiryLoading(true);
    setFeedbackMsg(null);
    try {
      await workspaceApi.requestInquiry(caseDetail.id, { provider });
      setFeedbackMsg({ type: 'success', text: `استعلام ${provider} با موفقیت ثبت شد.` });
      await onRefresh?.();
    } catch (err) {
      setFeedbackMsg({
        type: 'error',
        text: err instanceof Error ? err.message : 'خطا در استعلام دولتی',
      });
    } finally {
      setInquiryLoading(false);
    }
  };

  const handleComplete = async () => {
    setCompleteLoading(true);
    setFeedbackMsg(null);
    try {
      await workspaceApi.completeCase(caseDetail.id);
      setFeedbackMsg({ type: 'success', text: 'پرونده با موفقیت تکمیل و نهایی شد.' });
      await onRefresh?.();
    } catch (err) {
      setFeedbackMsg({
        type: 'error',
        text: err instanceof Error ? err.message : 'خطا در تکمیل نهایی پرونده',
      });
    } finally {
      setCompleteLoading(false);
    }
  };

  const handleReject = async () => {
    const reason = window.prompt('دلیل رد پرونده (اختیاری):') || 'عدم احراز شرایط قانونی خدمت';
    setRejectLoading(true);
    setFeedbackMsg(null);
    try {
      await workspaceApi.rejectCase(caseDetail.id, { reason });
      setFeedbackMsg({ type: 'success', text: 'پرونده با موفقیت رد شد.' });
      await onRefresh?.();
    } catch (err) {
      setFeedbackMsg({
        type: 'error',
        text: err instanceof Error ? err.message : 'خطا در رد پرونده (فقط مدیر دفتر مجاز است)',
      });
    } finally {
      setRejectLoading(false);
    }
  };

  return (
    <div className="flex flex-col gap-4 text-start">
      <CaseWorkspaceHeader
        caseDetail={caseDetail}
        availableActions={availableActions}
        reviewLoading={reviewLoading}
        completeLoading={completeLoading}
        inquiryLoading={inquiryLoading}
        rejectLoading={rejectLoading}
        onStartReview={() => void handleStartReview()}
        onOpenReturn={() => setReturnModalOpen(true)}
        onRequestInquiry={(p) => void handleRequestInquiry(p)}
        onComplete={() => void handleComplete()}
        onReject={() => void handleReject()}
        onClose={onClose}
      />

      {feedbackMsg && (
        <div
          className={`p-3 rounded-xl border text-xs flex items-center justify-between ${
            feedbackMsg.type === 'success'
              ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
              : 'bg-rose-50 border-rose-200 text-rose-800'
          }`}
        >
          <span>{feedbackMsg.text}</span>
          <button
            type="button"
            onClick={() => setFeedbackMsg(null)}
            className="text-slate-500 hover:text-slate-700"
          >
            ✕
          </button>
        </div>
      )}

      {/* SLA & Timeline Status */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs">
          <span className="text-xs text-slate-400 block mb-1">مهلت SLA باقی‌مانده</span>
          <div className="flex items-center gap-1.5 text-sm font-bold text-slate-800">
            <Clock className="w-4 h-4 text-amber-500" />
            <span>{Math.round(caseDetail.sla.remaining_seconds / 3600)} ساعت باقی‌مانده</span>
          </div>
        </div>

        <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs">
          <span className="text-xs text-slate-400 block mb-1">دفتر پیشخوان مجری</span>
          <div className="flex items-center gap-1.5 text-sm font-bold text-slate-800">
            <Building2 className="w-4 h-4 text-blue-500" />
            <span>{caseDetail.assigned_office?.name || 'دفتر مرکزی'}</span>
          </div>
        </div>

        <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs">
          <span className="text-xs text-slate-400 block mb-1">پیشرفت مراحل پرونده</span>
          <span className="text-sm font-bold text-slate-800">
            گام {caseDetail.current_step} از {caseDetail.total_steps}
          </span>
        </div>
      </div>

      {/* Document Review Canvas */}
      <div className="w-full">
        <h3 className="text-sm font-bold text-slate-800 mb-2">بررسی و اصالت‌سنجی مدارک متقاضی</h3>
        <DocumentReviewPanel
          documents={caseDetail.documents}
          onReturnDocument={() => setReturnModalOpen(true)}
        />
      </div>

      <ReturnModal
        isOpen={returnModalOpen}
        onClose={() => setReturnModalOpen(false)}
        onSubmit={handleReturnSubmit}
        documents={caseDetail.documents}
        isLoading={returnLoading}
      />
    </div>
  );
};
