import React, { useState } from 'react';
import {
  RETURN_REASON_CODES,
  RETURN_REASON_META,
  ReturnReasonCode,
} from '@pishkhan/domain';
import { Dialog, Button } from '@pishkhan/ui-kit';
import { AlertTriangle, Send, X } from 'lucide-react';
import { ReturnCasePayload, CaseDocumentItem } from '../types';

export interface ReturnModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (payload: ReturnCasePayload) => Promise<void>;
  documents?: CaseDocumentItem[];
  isLoading?: boolean;
}

export const ReturnModal: React.FC<ReturnModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  documents = [],
  isLoading = false,
}) => {
  const [selectedCode, setSelectedCode] = useState<ReturnReasonCode>('DOC_BLUR');
  const [message, setMessage] = useState<string>(RETURN_REASON_META.DOC_BLUR.defaultMessage);
  const [targetDocCode, setTargetDocCode] = useState<string>(
    documents[0]?.document_type_code || ''
  );
  const [isCustomEdited, setIsCustomEdited] = useState(false);

  const handleCodeChange = (code: ReturnReasonCode) => {
    setSelectedCode(code);
    if (!isCustomEdited) {
      setMessage(RETURN_REASON_META[code].defaultMessage);
    }
  };

  const handleResetToDefault = () => {
    setMessage(RETURN_REASON_META[selectedCode].defaultMessage);
    setIsCustomEdited(false);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await onSubmit({
      reason_code: selectedCode,
      operator_note: message,
      target_document_type_code: targetDocCode || undefined,
    });
  };

  return (
    <Dialog
      isOpen={isOpen}
      onClose={onClose}
      title="بازگرداندن پرونده جهت اصلاح مدارک"
      className="max-w-2xl"
    >
      <form onSubmit={handleSubmit} className="space-y-4 text-start">
        <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-start gap-2.5 text-amber-800 text-xs leading-relaxed">
          <AlertTriangle className="w-5 h-5 shrink-0 text-amber-600 mt-0.5" />
          <p>
            با بازگرداندن پرونده، وضعیت پرونده به «نیازمند اقدام شهروند» تغییر یافته و مهلت ۷۲ ساعته
            اصلاح به همراه پیامک و اعلان برای متقاضی فعال می‌شود.
          </p>
        </div>

        {/* Target Document Selector */}
        {documents.length > 0 && (
          <div>
            <label
              htmlFor="target-doc-select"
              className="block text-xs font-bold text-slate-700 mb-1"
            >
              مدرک دارای نقص
            </label>
            <select
              id="target-doc-select"
              value={targetDocCode}
              onChange={(e) => setTargetDocCode(e.target.value)}
              className="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-900 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-blue-500"
            >
              {documents.map((doc) => (
                <option key={doc.id} value={doc.document_type_code}>
                  {doc.title} ({doc.document_type_code})
                </option>
              ))}
            </select>
          </div>
        )}

        {/* 10 Return Reason Codes Radio / Grid */}
        <div>
          <label className="block text-xs font-bold text-slate-700 mb-1.5">
            علت بازگشت (از میان ۱۰ کد استاندارد سامانه)
          </label>
          <div
            className="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-52 overflow-y-auto p-1 border border-slate-200 rounded-xl bg-slate-50"
            role="radiogroup"
            aria-label="کدهای علت بازگشت مدرک"
          >
            {RETURN_REASON_CODES.map((code) => {
              const meta = RETURN_REASON_META[code];
              const isSelected = selectedCode === code;
              return (
                <label
                  key={code}
                  className={`flex items-start gap-2.5 p-2.5 rounded-lg border text-xs cursor-pointer transition-colors ${
                    isSelected
                      ? 'bg-blue-50 border-blue-500 text-blue-900 font-semibold shadow-2xs'
                      : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100'
                  }`}
                >
                  <input
                    type="radio"
                    name="return_reason_code"
                    value={code}
                    checked={isSelected}
                    onChange={() => handleCodeChange(code)}
                    className="mt-0.5 text-blue-600 focus:ring-blue-500"
                  />
                  <div>
                    <span className="block font-medium">{meta.label}</span>
                    <span className="text-[10px] text-slate-400 font-mono">{code}</span>
                  </div>
                </label>
              );
            })}
          </div>
        </div>

        {/* Editable Message Textarea */}
        <div>
          <div className="flex items-center justify-between mb-1">
            <label htmlFor="return-message" className="text-xs font-bold text-slate-700">
              متن راهنما و توضیحات به متقاضی (قابل ویرایش)
            </label>
            {isCustomEdited && (
              <button
                type="button"
                onClick={handleResetToDefault}
                className="text-[11px] text-blue-600 hover:underline"
              >
                بازنشانی به متن پیش‌فرض
              </button>
            )}
          </div>
          <textarea
            id="return-message"
            rows={3}
            value={message}
            onChange={(e) => {
              setMessage(e.target.value);
              setIsCustomEdited(true);
            }}
            className="w-full bg-white border border-slate-300 rounded-lg p-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-hidden leading-relaxed"
            placeholder="متن پیام توضیحی به شهروند جهت نحوه اصلاح مدرک..."
            required
          />
        </div>

        {/* Modal Actions */}
        <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <Button
            type="button"
            variant="secondary"
            size="sm"
            onClick={onClose}
            disabled={isLoading}
          >
            <X className="w-4 h-4 ml-1" />
            انصراف
          </Button>
          <Button
            type="submit"
            variant="danger"
            size="sm"
            isLoading={isLoading}
          >
            <Send className="w-4 h-4 ml-1" />
            ثبت بازگشت و ابلاغ به شهروند
          </Button>
        </div>
      </form>
    </Dialog>
  );
};
