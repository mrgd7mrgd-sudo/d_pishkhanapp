import React, { useState } from 'react';
import { LegalDelegation } from '../types';
import { 
  Users, 
  X, 
  ShieldCheck, 
  Plus, 
  CheckCircle2, 
  FileText, 
  AlertCircle, 
  KeyRound,
  ArrowRight
} from 'lucide-react';

interface LegalDelegationModalProps {
  delegations: LegalDelegation[];
  onClose: () => void;
  onAddDelegation: (delegation: LegalDelegation) => void;
}

export const LegalDelegationModal: React.FC<LegalDelegationModalProps> = ({
  delegations,
  onClose,
  onAddDelegation
}) => {
  const [showAddForm, setShowAddForm] = useState<boolean>(false);
  const [principalName, setPrincipalName] = useState<string>('');
  const [principalNationalId, setPrincipalNationalId] = useState<string>('');
  const [relation, setRelation] = useState<string>('پدر / مادر');
  const [documentNumber, setDocumentNumber] = useState<string>('');
  const [otpSent, setOtpSent] = useState<boolean>(false);
  const [otpCode, setOtpCode] = useState<string>('');
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  const handleSendOtp = () => {
    if (!principalName || !principalNationalId) return;
    setOtpSent(true);
  };

  const handleConfirmOtp = () => {
    const newDelegation: LegalDelegation = {
      id: `del-${Date.now()}`,
      principalName,
      principalNationalId,
      agentName: 'محمدرضا رضایی دهکردی',
      agentNationalId: '0019845621',
      relation,
      validUntil: '۱۴۰۵/۰۱/۰۱',
      allowedServices: ['کلیه خدمات سجلی و خودرویی'],
      maxAmountTomans: 3000000,
      status: 'active',
      documentNumber: documentNumber || 'تاییدیه برخط شاهکار و سامانه ثنا'
    };

    onAddDelegation(newDelegation);
    setSuccessMsg('نیابت قانونی با موفقیت ثبت و تایید شد.');
    setShowAddForm(false);
    setTimeout(() => setSuccessMsg(null), 3000);
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" dir="rtl">
      <div className="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl space-y-4 text-right animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between pb-2 border-b border-slate-100">
          <div className="flex items-center gap-2 text-emerald-800">
            <Users className="w-5 h-5 text-emerald-600" />
            <h3 className="font-bold text-sm">نیابت و پیگیری امور اعضای خانواده</h3>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600 p-1">
            <X className="w-5 h-5" />
          </button>
        </div>

        {successMsg && (
          <div className="bg-emerald-50 border border-emerald-200 text-emerald-800 p-3 rounded-xl text-xs flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
            <span>{successMsg}</span>
          </div>
        )}

        <p className="text-xs text-slate-500 leading-relaxed">
          شما می‌توانید با احراز برخط یا بارگذاری وکالت‌نامه رسمی، پرونده‌های بستگان درجه یک (والدین، همسر و فرزندان) را ثبت و پیگیری نمایید.
        </p>

        {!showAddForm ? (
          <div className="space-y-3">
            <div className="space-y-2">
              {delegations.map((del) => (
                <div key={del.id} className="bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-xs space-y-2">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <span className="font-bold text-slate-900">{del.principalName}</span>
                      <span className="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold">
                        {del.relation}
                      </span>
                    </div>
                    <span className="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded font-bold">
                      فعال و معتبر
                    </span>
                  </div>

                  <div className="grid grid-cols-2 gap-2 text-slate-600 text-[11px]">
                    <div>
                      <span className="text-slate-400 block text-[10px]">کد ملی موکل:</span>
                      <strong>{del.principalNationalId}</strong>
                    </div>
                    <div>
                      <span className="text-slate-400 block text-[10px]">اعتبار تا:</span>
                      <strong>{del.validUntil}</strong>
                    </div>
                  </div>

                  <div className="text-[11px] text-slate-500 pt-1 border-t border-slate-200 flex items-center gap-1">
                    <FileText className="w-3.5 h-3.5 text-slate-400" />
                    <span>مستند: {del.documentNumber}</span>
                  </div>
                </div>
              ))}
            </div>

            <button
              onClick={() => setShowAddForm(true)}
              className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-xs flex items-center justify-center gap-1.5 transition shadow"
            >
              <Plus className="w-4 h-4" />
              <span>افزودن عضو جدید / ثبت نیابت الکترونیک</span>
            </button>
          </div>
        ) : (
          /* Add Form with OTP */
          <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3 text-xs">
            <h4 className="font-bold text-slate-800">مشخصات موکل / عضو خانواده:</h4>

            <div>
              <label className="block text-slate-600 mb-1">نام و نام‌خانوادگی:</label>
              <input
                type="text"
                value={principalName}
                onChange={(e) => setPrincipalName(e.target.value)}
                placeholder="مثال: زهرا رضایی"
                className="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-emerald-600"
              />
            </div>

            <div className="grid grid-cols-2 gap-2">
              <div>
                <label className="block text-slate-600 mb-1">کد ملی موکل:</label>
                <input
                  type="text"
                  value={principalNationalId}
                  onChange={(e) => setPrincipalNationalId(e.target.value)}
                  placeholder="۱۰ رقم کد ملی"
                  className="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-emerald-600"
                />
              </div>

              <div>
                <label className="block text-slate-600 mb-1">نسبت خانوادگی:</label>
                <select
                  value={relation}
                  onChange={(e) => setRelation(e.target.value)}
                  className="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-emerald-600"
                >
                  <option value="پدر / مادر">پدر / مادر</option>
                  <option value="همسر">همسر</option>
                  <option value="فرزند">فرزند</option>
                  <option value="موکل رسمی (وکالت‌نامه محضری)">موکل رسمی (وکالت‌نامه محضری)</option>
                </select>
              </div>
            </div>

            <div>
              <label className="block text-slate-600 mb-1">شماره وکالت‌نامه یا استعلام ثنا (اختیاری):</label>
              <input
                type="text"
                value={documentNumber}
                onChange={(e) => setDocumentNumber(e.target.value)}
                placeholder="مثال: شناسه یکتای وکالت‌نامه ثبت الکترونیک..."
                className="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-emerald-600"
              />
            </div>

            {!otpSent ? (
              <button
                onClick={handleSendOtp}
                disabled={!principalName || !principalNationalId}
                className="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl text-xs transition"
              >
                ارسال پیامک تایید و رمز یکبارمصرف به موکل
              </button>
            ) : (
              <div className="space-y-2 pt-2 border-t border-slate-200">
                <label className="block text-slate-700 font-bold">کد تایید پیامک‌شده به موکل:</label>
                <input
                  type="text"
                  value={otpCode}
                  onChange={(e) => setOtpCode(e.target.value)}
                  placeholder="کد ۵ رقمی"
                  className="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-center text-sm font-mono tracking-widest focus:outline-none focus:border-emerald-600"
                />
                <button
                  onClick={handleConfirmOtp}
                  className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-xs transition"
                >
                  تایید نهایی و اتصال نیابت
                </button>
              </div>
            )}

            <button
              onClick={() => setShowAddForm(false)}
              className="w-full bg-slate-100 text-slate-600 font-medium py-2 rounded-xl text-xs transition hover:bg-slate-200"
            >
              بازگشت به فهرست نیابت‌ها
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
