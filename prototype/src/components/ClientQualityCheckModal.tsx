import React, { useState } from 'react';
import { 
  Scan, 
  X, 
  CheckCircle2, 
  AlertTriangle, 
  Sparkles, 
  Eye, 
  RefreshCw,
  Camera,
  Upload
} from 'lucide-react';

interface ClientQualityCheckModalProps {
  documentName: string;
  onClose: () => void;
  onConfirmUpload: (fileUrl: string) => void;
}

export const ClientQualityCheckModal: React.FC<ClientQualityCheckModalProps> = ({
  documentName,
  onClose,
  onConfirmUpload
}) => {
  const [analyzing, setAnalyzing] = useState<boolean>(true);
  const [qualityScore, setQualityScore] = useState<number>(92);
  const [blurDetected, setBlurDetected] = useState<boolean>(false);
  const [glareDetected, setGlareDetected] = useState<boolean>(false);
  const [edgesFound, setEdgesFound] = useState<boolean>(true);

  React.useEffect(() => {
    const timer = setTimeout(() => {
      setAnalyzing(false);
    }, 900);
    return () => clearTimeout(timer);
  }, []);

  return (
    <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" dir="rtl">
      <div className="bg-white rounded-2xl max-w-md w-full p-5 shadow-2xl space-y-4 text-right animate-in fade-in zoom-in-95 duration-200">
        <div className="flex items-center justify-between pb-2 border-b border-slate-100">
          <div className="flex items-center gap-2 text-emerald-800">
            <Sparkles className="w-5 h-5 text-emerald-600" />
            <h3 className="font-bold text-sm">ارزیابی هوشمند کیفیت سند قبل از ارسال</h3>
          </div>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600 p-1">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Scan Frame Simulation */}
        <div className="relative h-48 bg-slate-900 rounded-xl overflow-hidden flex items-center justify-center border border-slate-800">
          <img
            src="https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=600&q=80"
            alt="پیش‌نمایش سند"
            className="w-full h-full object-cover opacity-80"
          />

          {analyzing ? (
            <div className="absolute inset-0 bg-black/50 flex flex-col items-center justify-center text-white space-y-2">
              <Scan className="w-8 h-8 text-emerald-400 animate-pulse" />
              <span className="text-xs font-bold">در حال سنجش وضوح و خوانایی سریال...</span>
            </div>
          ) : (
            <div className="absolute top-2 right-2 bg-emerald-600/90 text-white text-[11px] font-bold px-2.5 py-1 rounded-lg backdrop-blur-xs flex items-center gap-1">
              <CheckCircle2 className="w-3.5 h-3.5" />
              <span>کیفیت اسکن: {qualityScore}٪ (تایید هوشمند)</span>
            </div>
          )}
        </div>

        {/* Quality Criteria Matrix */}
        <div className="space-y-2 text-xs">
          <div className="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200">
            <span className="text-slate-700">وضوح شماره سریال و نوشته‌ها:</span>
            <span className="text-emerald-700 font-bold flex items-center gap-1">
              <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" /> بسیار شفاف
            </span>
          </div>

          <div className="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200">
            <span className="text-slate-700">انعکاس نور و بازتاب (Glare):</span>
            <span className="text-emerald-700 font-bold flex items-center gap-1">
              <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" /> بدون انعکاس
            </span>
          </div>

          <div className="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200">
            <span className="text-slate-700">کادر و لبه‌های چهارگانه سند:</span>
            <span className="text-emerald-700 font-bold flex items-center gap-1">
              <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" /> کامل در کادر
            </span>
          </div>
        </div>

        <div className="flex items-center gap-2 pt-2">
          <button
            disabled={analyzing}
            onClick={() => onConfirmUpload('https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=600&q=80')}
            className="flex-1 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl text-xs flex items-center justify-center gap-1.5 transition shadow"
          >
            <CheckCircle2 className="w-4 h-4" />
            <span>تایید و ارسال مدرک</span>
          </button>

          <button
            onClick={onClose}
            className="px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium py-2.5 rounded-xl text-xs transition"
          >
            اسکن مجدد
          </button>
        </div>
      </div>
    </div>
  );
};
