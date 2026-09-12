import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, Input, FileDropzone } from '@pishkhan/ui-kit';

export interface VaultUploadModalProps {
  isOpen: boolean;
  onClose: () => void;
  onUpload: (formData: FormData) => Promise<void>;
}

interface VaultUploadFormProps {
  onClose: () => void;
  onUpload: (formData: FormData) => Promise<void>;
  onError: (msg: string) => void;
}

function VaultUploadForm({ onClose, onUpload, onError }: VaultUploadFormProps): React.JSX.Element {
  const { t } = useTranslation();
  const [title, setTitle] = useState<string>('');
  const [category, setCategory] = useState<string>('identity');
  const [docNumber, setDocNumber] = useState<string>('');
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [isUploading, setIsUploading] = useState<boolean>(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim() || !selectedFile) {
      onError(t('vault.upload_missing_fields'));
      return;
    }
    setIsUploading(true);
    try {
      const fd = new FormData();
      fd.append('title', title);
      fd.append('category', category);
      if (docNumber) fd.append('doc_number', docNumber);
      fd.append('file', selectedFile);
      await onUpload(fd);
      onClose();
    } catch {
      onError(t('vault.upload_failed'));
    } finally {
      setIsUploading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      <Input placeholder={t('vault.input_title_placeholder')} value={title} onChange={(e) => setTitle(e.target.value)} aria-label={t('vault.input_title_label')} />
      <Input placeholder={t('vault.input_doc_number_placeholder')} value={docNumber} onChange={(e) => setDocNumber(e.target.value)} aria-label={t('vault.input_doc_number_label')} />
      <div className="space-y-1">
        <label className="text-xs font-medium text-slate-700 dark:text-slate-300">{t('vault.category_select_label')}</label>
        <select value={category} onChange={(e) => setCategory(e.target.value)} className="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm" aria-label={t('vault.category_select_label')}>
          <option value="identity">{t('vault.cat_identity')}</option>
          <option value="education">{t('vault.cat_education')}</option>
          <option value="finance">{t('vault.cat_finance')}</option>
          <option value="legal">{t('vault.cat_legal')}</option>
          <option value="medical">{t('vault.cat_medical')}</option>
          <option value="general">{t('vault.cat_general')}</option>
        </select>
      </div>
      <FileDropzone onFileSelect={(f) => setSelectedFile(f)} selectedFileName={selectedFile?.name} onClear={() => setSelectedFile(null)} label={t('vault.dropzone_label')} description={t('vault.dropzone_desc')} />
      <div className="flex items-center justify-end gap-2 pt-2">
        <Button variant="ghost" size="sm" type="button" onClick={onClose} aria-label={t('common.cancel')}>{t('common.cancel')}</Button>
        <Button variant="primary" size="sm" type="submit" isLoading={isUploading} aria-label={t('vault.submit_upload')}>{t('vault.submit_upload')}</Button>
      </div>
    </form>
  );
}

export function VaultUploadModal({ isOpen, onClose, onUpload }: VaultUploadModalProps): React.JSX.Element | null {
  const { t } = useTranslation();
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  if (!isOpen) return null;

  return (
    <div role="dialog" aria-modal="true" aria-label={t('vault.upload_modal_title')} className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
      <div className="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-5 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">{t('vault.upload_modal_title')}</h3>
          <button type="button" onClick={onClose} className="text-xs text-slate-500 px-2 py-1" aria-label={t('common.close')}>{t('common.close')}</button>
        </div>
        {errorMsg ? <div className="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/30 text-rose-700 text-xs border border-rose-200">{errorMsg}</div> : null}
        <VaultUploadForm onClose={onClose} onUpload={onUpload} onError={setErrorMsg} />
      </div>
    </div>
  );
}
