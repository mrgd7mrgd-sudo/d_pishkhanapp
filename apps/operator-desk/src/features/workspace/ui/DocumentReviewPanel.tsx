import React, { useState } from 'react';
import { ZoomIn, ZoomOut, RotateCw, ExternalLink, ShieldCheck, AlertCircle } from 'lucide-react';
import { Button } from '@pishkhan/ui-kit';
import { CaseDocumentItem } from '../types';
import { workspaceApi } from '../api/workspaceApi';

export interface DocumentReviewPanelProps {
  documents: CaseDocumentItem[];
  onReturnDocument?: (doc: CaseDocumentItem) => void;
}

export const DocumentReviewPanel: React.FC<DocumentReviewPanelProps> = ({
  documents,
  onReturnDocument,
}) => {
  const [selectedDocId, setSelectedDocId] = useState<string>(documents[0]?.id || '');
  const [signedUrl, setSignedUrl] = useState<string | null>(null);
  const [loadingUrl, setLoadingUrl] = useState(false);
  const [zoom, setZoom] = useState(1);
  const [rotation, setRotation] = useState(0);
  const [urlError, setUrlError] = useState<string | null>(null);

  const activeDoc = documents.find((d) => d.id === selectedDocId) || documents[0];

  const handleSelectDoc = async (doc: CaseDocumentItem) => {
    setSelectedDocId(doc.id);
    setZoom(1);
    setRotation(0);
    setUrlError(null);
    setLoadingUrl(true);

    try {
      const url = await workspaceApi.fetchDocumentSignedUrl(doc.id);
      setSignedUrl(url);
    } catch {
      setUrlError('دریافت پیوند امن مدرک با خطا مواجه شد.');
    } finally {
      setLoadingUrl(false);
    }
  };

  React.useEffect(() => {
    if (activeDoc && !signedUrl && !loadingUrl) {
      void handleSelectDoc(activeDoc);
    }
  }, [activeDoc?.id]);

  const handleZoomIn = () => setZoom((z) => Math.min(3, Number((z + 0.25).toFixed(2))));
  const handleZoomOut = () => setZoom((z) => Math.max(0.5, Number((z - 0.25).toFixed(2))));
  const handleRotate = () => setRotation((r) => (r + 90) % 360);

  if (!documents || documents.length === 0) {
    return (
      <div className="p-8 text-center border-2 border-dashed border-slate-200 rounded-xl">
        <p className="text-sm text-slate-500">هیچ مدرکی برای این پرونده پیوست نشده است.</p>
      </div>
    );
  }

  return (
    <div className="bg-white border border-slate-200 rounded-xl overflow-hidden flex flex-col h-full shadow-2xs">
      {/* Document tabs */}
      <div className="flex items-center gap-1 p-2 bg-slate-50 border-b border-slate-200 overflow-x-auto">
        {documents.map((doc) => {
          const isActive = doc.id === (activeDoc?.id || '');
          const hasWarnings = Array.isArray(doc.quality_warnings) && doc.quality_warnings.length > 0;

          return (
            <button
              key={doc.id}
              type="button"
              onClick={() => void handleSelectDoc(doc)}
              className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap ${
                isActive
                  ? 'bg-blue-600 text-white shadow-2xs'
                  : 'bg-white text-slate-700 hover:bg-slate-200/70 border border-slate-200'
              }`}
            >
              <span>{doc.title}</span>
              <span
                className={`text-[10px] px-1 rounded ${
                  isActive ? 'bg-blue-700 text-blue-100' : 'bg-slate-100 text-slate-500'
                }`}
              >
                نسخه {doc.version}
              </span>
              {hasWarnings && (
                <span title="هشدار کیفیت تصویر">
                  <AlertCircle
                    className={`w-3.5 h-3.5 ${isActive ? 'text-amber-300' : 'text-amber-500'}`}
                    aria-hidden="true"
                  />
                </span>
              )}
            </button>
          );
        })}
      </div>

      {/* Toolbar */}
      <div className="flex items-center justify-between px-4 py-2 border-b border-slate-200 bg-slate-50/50">
        <div className="flex items-center gap-2">
          <span className="text-xs font-bold text-slate-800">{activeDoc?.title}</span>
          <span className="text-xs text-slate-400 font-mono">({activeDoc?.document_type_code})</span>
        </div>

        <div className="flex items-center gap-1">
          <button
            type="button"
            onClick={handleZoomIn}
            aria-label="بزرگ‌نمایی"
            className="p-1.5 rounded-lg text-slate-600 hover:bg-slate-200 active:bg-slate-300 transition-colors"
          >
            <ZoomIn className="w-4 h-4" />
          </button>
          <span className="text-[11px] font-mono text-slate-600 min-w-10 text-center">
            {Math.round(zoom * 100)}%
          </span>
          <button
            type="button"
            onClick={handleZoomOut}
            aria-label="کوچک‌نمایی"
            className="p-1.5 rounded-lg text-slate-600 hover:bg-slate-200 active:bg-slate-300 transition-colors"
          >
            <ZoomOut className="w-4 h-4" />
          </button>
          <button
            type="button"
            onClick={handleRotate}
            aria-label="چرخش تصویر"
            className="p-1.5 rounded-lg text-slate-600 hover:bg-slate-200 active:bg-slate-300 transition-colors mr-1"
          >
            <RotateCw className="w-4 h-4" />
          </button>

          {signedUrl && (
            <a
              href={signedUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="p-1.5 rounded-lg text-slate-600 hover:bg-slate-200 active:bg-slate-300 transition-colors"
              title="مشاهده در زبانه جدید"
            >
              <ExternalLink className="w-4 h-4" />
            </a>
          )}

          {onReturnDocument && activeDoc && (
            <Button
              variant="danger"
              size="sm"
              className="mr-2 text-xs py-1 h-8"
              onClick={() => onReturnDocument(activeDoc)}
            >
              ثبت نقص این مدرک
            </Button>
          )}
        </div>
      </div>

      {/* Viewer Canvas Area */}
      <div className="relative flex-1 bg-slate-900/90 min-h-80 max-h-[500px] overflow-auto flex items-center justify-center p-4 select-none">
        {loadingUrl && (
          <div className="text-white text-xs flex items-center gap-2">
            <span className="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
            <span>در حال آماده‌سازی امن سند...</span>
          </div>
        )}

        {urlError && !loadingUrl && (
          <div className="bg-rose-900/40 border border-rose-500/40 text-rose-200 text-xs p-4 rounded-xl max-w-sm text-center">
            {urlError}
          </div>
        )}

        {signedUrl && !loadingUrl && (
          <div
            style={{
              transform: `scale(${zoom}) rotate(${rotation}deg)`,
              transition: 'transform 0.2s ease-out',
            }}
            className="max-w-full max-h-full flex items-center justify-center origin-center"
          >
            <img
              src={signedUrl}
              alt={activeDoc?.title || 'سند پرونده'}
              className="max-h-[440px] w-auto object-contain rounded shadow-lg pointer-events-none"
            />
          </div>
        )}
      </div>

      {/* Footer warning bar */}
      <div className="px-4 py-2 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-600 flex items-center justify-between">
        <div className="flex items-center gap-1.5 text-emerald-700">
          <ShieldCheck className="w-4 h-4" />
          <span>امضای ۶۰ ثانیه‌ای موقت (Architecture §۶.۸)</span>
        </div>
        {activeDoc?.quality_warnings && activeDoc.quality_warnings.length > 0 && (
          <div className="text-amber-700 flex items-center gap-1">
            <AlertCircle className="w-3.5 h-3.5" />
            <span>هشدارهای کیفیت: {activeDoc.quality_warnings.join('، ')}</span>
          </div>
        )}
      </div>
    </div>
  );
};
