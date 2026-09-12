import React, { useRef, useState, type DragEvent } from 'react';
import { twMerge } from 'tailwind-merge';
import { UploadCloud, FileText, X, AlertCircle } from 'lucide-react';

export interface FileDropzoneProps {
  onFileSelect: (file: File) => void;
  accept?: string | undefined;
  maxSize?: number | undefined;
  label?: string | undefined;
  description?: string | undefined;
  selectedFileName?: string | undefined;
  onClear?: (() => void) | undefined;
  isUploading?: boolean | undefined;
  uploadProgress?: number | undefined;
  error?: string | undefined;
  disabled?: boolean | undefined;
  className?: string | undefined;
  clearAriaLabel?: string | undefined;
}

const DEFAULT_LABEL = 'انتخاب یا رها کردن مدرک';
const DEFAULT_DESC = 'فرمت‌های مجاز: JPG, PNG, PDF (حداکثر ۱۰ مگابایت)';
const DEFAULT_CLEAR_LABEL = 'حذف فایل انتخابی';
const UPLOADING_TEXT = 'در حال بارگذاری مدرک...';
const PROGRESS_LABEL = 'پیشرفت بارگذاری';
const INVALID_TYPE_MSG = 'فرمت فایل انتخابی مجاز نیست.';

const validateFile = (file: File, accept: string, maxSize: number): string | null => {
  if (file.size > maxSize) {
    return `حجم فایل بیشتر از سقف مجاز (${Math.round(maxSize / (1024 * 1024))} مگابایت) است.`;
  }
  const acceptedTypes = accept.split(',').map((t) => t.trim().toLowerCase());
  const fileType = file.type.toLowerCase();
  const isAccepted = acceptedTypes.some((type) => {
    if (type.endsWith('/*')) return fileType.startsWith(type.replace('/*', ''));
    return fileType === type;
  });
  if (!isAccepted && acceptedTypes.length > 0) return INVALID_TYPE_MSG;
  return null;
};

const DropzoneEmptyState: React.FC<{ label: string; description: string }> = ({ label, description }) => (
  <>
    <div className="flex items-center justify-center w-12 h-12 mb-3 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
      <UploadCloud className="w-6 h-6" aria-hidden="true" />
    </div>
    <p className="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1">{label}</p>
    <p className="text-xs text-slate-500 dark:text-slate-400 text-center">{description}</p>
  </>
);

const DropzonePreview: React.FC<{
  fileName: string;
  onClear?: (() => void) | undefined;
  isUploading?: boolean | undefined;
  clearLabel: string;
}> = ({ fileName, onClear, isUploading, clearLabel }) => (
  <div className="flex items-center justify-between w-full max-w-sm gap-3 p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
    <div className="flex items-center gap-2.5 overflow-hidden">
      <FileText className="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" aria-hidden="true" />
      <span className="text-xs font-medium text-slate-800 dark:text-slate-200 truncate">{fileName}</span>
    </div>
    {onClear && !isUploading && (
      <button
        type="button"
        onClick={(e) => {
          e.stopPropagation();
          onClear();
        }}
        aria-label={clearLabel}
        className="p-1 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
      >
        <X className="w-4 h-4" />
      </button>
    )}
  </div>
);

const DropzoneProgress: React.FC<{ progress: number }> = ({ progress }) => (
  <div className="w-full max-w-xs mt-4">
    <div className="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300 mb-1.5">
      <span>{UPLOADING_TEXT}</span>
      <span dir="ltr">{progress}%</span>
    </div>
    <div
      role="progressbar"
      aria-valuenow={progress}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-label={PROGRESS_LABEL}
      className="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden"
    >
      <div
        className="h-full bg-emerald-500 rounded-full transition-all duration-300"
        style={{ width: `${Math.min(100, Math.max(0, progress))}%` }}
      />
    </div>
  </div>
);

const DropzoneInnerContent: React.FC<{
  selectedFileName?: string | undefined;
  isUploading: boolean;
  uploadProgress: number;
  label: string;
  description: string;
  clearLabel: string;
  onClear?: (() => void) | undefined;
}> = ({ selectedFileName, isUploading, uploadProgress, label, description, clearLabel, onClear }) => {
  if (selectedFileName) {
    return <DropzonePreview fileName={selectedFileName} onClear={onClear} isUploading={isUploading} clearLabel={clearLabel} />;
  }
  return (
    <>
      <DropzoneEmptyState label={label} description={description} />
      {isUploading && <DropzoneProgress progress={uploadProgress} />}
    </>
  );
};

const DropzoneBox: React.FC<{
  isDragOver: boolean;
  activeError: string | null | undefined;
  disabled: boolean;
  isUploading: boolean;
  label: string;
  onClick: () => void;
  onKeyDown: (e: React.KeyboardEvent) => void;
  onDragOver: (e: DragEvent<HTMLDivElement>) => void;
  onDragLeave: () => void;
  onDrop: (e: DragEvent<HTMLDivElement>) => void;
  children: React.ReactNode;
}> = (props) => (
  <div
    role="button"
    tabIndex={props.disabled || props.isUploading ? -1 : 0}
    onClick={props.onClick}
    onKeyDown={props.onKeyDown}
    onDragOver={props.onDragOver}
    onDragLeave={props.onDragLeave}
    onDrop={props.onDrop}
    aria-label={props.label}
    aria-disabled={props.disabled || props.isUploading}
    className={twMerge(
      'relative flex flex-col items-center justify-center p-6 border-2 border-dashed rounded-2xl transition-all duration-200 cursor-pointer select-none',
      props.isDragOver
        ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 scale-[1.01]'
        : 'border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/40 hover:bg-slate-100/60 dark:hover:bg-slate-800/40',
      props.activeError && 'border-rose-500 bg-rose-50/30 dark:bg-rose-950/20',
      (props.disabled || props.isUploading) && 'opacity-60 cursor-not-allowed pointer-events-none'
    )}
  >
    {props.children}
  </div>
);

const DropzoneInput = React.forwardRef<HTMLInputElement, {
  accept: string;
  label: string;
  disabled: boolean;
  onChange: (file: File) => void;
}>(({ accept, label, disabled, onChange }, ref) => (
  <input
    ref={ref}
    type="file"
    accept={accept}
    aria-label={label}
    disabled={disabled}
    onChange={(e) => {
      const f = e.target.files?.[0];
      if (f) onChange(f);
      if (e.target) e.target.value = '';
    }}
    className="sr-only"
    id="file-dropzone-input"
    data-testid="file-dropzone-input"
  />
));
DropzoneInput.displayName = 'DropzoneInput';

const useDropzoneEvents = (
  disabled: boolean,
  isUploading: boolean,
  onFileDrop: (file: File) => void,
  inputRef: React.RefObject<HTMLInputElement | null>
) => {
  const [isDragOver, setIsDragOver] = useState(false);

  const onKeyDown = (e: React.KeyboardEvent): void => {
    if ((e.key === 'Enter' || e.key === ' ') && !disabled && !isUploading) {
      e.preventDefault();
      inputRef.current?.click();
    }
  };
  const onDragOver = (e: DragEvent<HTMLDivElement>): void => {
    e.preventDefault();
    if (!disabled && !isUploading) setIsDragOver(true);
  };
  const onDragLeave = (): void => setIsDragOver(false);
  const onDrop = (e: DragEvent<HTMLDivElement>): void => {
    e.preventDefault();
    setIsDragOver(false);
    const f = e.dataTransfer.files[0];
    if (f && !disabled && !isUploading) onFileDrop(f);
  };

  return { isDragOver, onKeyDown, onDragOver, onDragLeave, onDrop };
};

const DropzoneError: React.FC<{ error?: string | null | undefined }> = ({ error }) => {
  if (!error) return null;
  return (
    <div className="flex items-center gap-1.5 mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">
      <AlertCircle className="w-4 h-4 shrink-0" aria-hidden="true" />
      <span>{error}</span>
    </div>
  );
};

export const FileDropzone: React.FC<FileDropzoneProps> = ({
  onFileSelect,
  accept = 'image/jpeg,image/png,application/pdf',
  maxSize = 10 * 1024 * 1024,
  label = DEFAULT_LABEL,
  description = DEFAULT_DESC,
  selectedFileName,
  onClear,
  isUploading = false,
  uploadProgress = 0,
  error,
  disabled = false,
  className,
  clearAriaLabel = DEFAULT_CLEAR_LABEL,
}) => {
  const [localError, setLocalError] = useState<string | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);
  const activeError = error || localError;

  const handleFile = (file: File): void => {
    setLocalError(null);
    const err = validateFile(file, accept, maxSize);
    if (err) setLocalError(err);
    else onFileSelect(file);
  };

  const events = useDropzoneEvents(disabled, isUploading, handleFile, inputRef);

  return (
    <div className={twMerge('w-full', className)}>
      <DropzoneInput ref={inputRef} accept={accept} label={label} disabled={disabled || isUploading} onChange={handleFile} />
      <DropzoneBox
        isDragOver={events.isDragOver}
        activeError={activeError}
        disabled={disabled}
        isUploading={isUploading}
        label={label}
        onClick={() => inputRef.current?.click()}
        onKeyDown={events.onKeyDown}
        onDragOver={events.onDragOver}
        onDragLeave={events.onDragLeave}
        onDrop={events.onDrop}
      >
        <DropzoneInnerContent
          selectedFileName={selectedFileName}
          isUploading={isUploading}
          uploadProgress={uploadProgress}
          label={label}
          description={description}
          clearLabel={clearAriaLabel}
          onClear={onClear ? () => { setLocalError(null); onClear(); } : undefined}
        />
      </DropzoneBox>
      <DropzoneError error={activeError} />
    </div>
  );
};
