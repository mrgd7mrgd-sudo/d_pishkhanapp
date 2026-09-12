import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Skeleton, Button } from '@pishkhan/ui-kit';
import type { VaultDocumentItem, VaultDocumentDetail } from '../types';
import { vaultApi } from '../api/vaultApi';
import { VaultCategoryFilter } from './VaultCategoryFilter';
import { VaultDocCard } from './VaultDocCard';
import { VaultDocViewModal } from './VaultDocViewModal';
import { VaultUploadModal } from './VaultUploadModal';

interface VaultGridProps {
  isLoading: boolean;
  documents: VaultDocumentItem[];
  onView: (id: string) => void;
  onDelete: (id: string) => void;
}

function VaultGrid({ isLoading, documents, onView, onDelete }: VaultGridProps): React.JSX.Element {
  const { t } = useTranslation();
  if (isLoading) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Skeleton className="h-36 w-full rounded-2xl" />
        <Skeleton className="h-36 w-full rounded-2xl" />
      </div>
    );
  }
  if (documents.length === 0) {
    return (
      <div className="text-center py-12 p-6 rounded-2xl bg-white/50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-800 space-y-2">
        <p className="text-sm font-semibold text-slate-700 dark:text-slate-300">{t('vault.empty_title')}</p>
        <p className="text-xs text-slate-500">{t('vault.empty_desc')}</p>
      </div>
    );
  }
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
      {documents.map((doc) => (
        <VaultDocCard key={doc.id} item={doc} onView={onView} onDelete={onDelete} />
      ))}
    </div>
  );
}

export function DocumentsVaultView(): React.JSX.Element {
  const { t } = useTranslation();
  const [documents, setDocuments] = useState<VaultDocumentItem[]>([]);
  const [selectedCategory, setSelectedCategory] = useState<string>('');
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [activeDoc, setActiveDoc] = useState<VaultDocumentDetail | null>(null);
  const [showUploadModal, setShowUploadModal] = useState<boolean>(false);
  const [isOnline, setIsOnline] = useState<boolean>(navigator.onLine);

  useEffect(() => {
    const handleOnline = () => setIsOnline(true);
    const handleOffline = () => setIsOnline(false);
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  useEffect(() => {
    setIsLoading(true);
    vaultApi.getDocuments(selectedCategory || undefined)
      .then((data) => { setDocuments(data); setIsLoading(false); })
      .catch(() => setIsLoading(false));
  }, [selectedCategory]);

  const handleView = async (id: string) => {
    try {
      const detail = await vaultApi.getDocumentById(id);
      setActiveDoc(detail);
    } catch {
      const found = documents.find((d) => d.id === id);
      if (found) setActiveDoc({ ...found, versions: [] });
    }
  };

  const handleDelete = async (id: string) => {
    await vaultApi.deleteDocument(id);
    setDocuments((prev) => prev.filter((d) => d.id !== id));
  };

  return (
    <div className="max-w-4xl mx-auto px-4 py-6 space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-slate-900 dark:text-slate-100">{t('vault.page_title')}</h1>
          <p className="text-xs text-slate-500 mt-1">{t('vault.page_subtitle')}</p>
        </div>
        <Button variant="primary" size="md" onClick={() => setShowUploadModal(true)} aria-label={t('vault.add_doc_btn')}>
          + {t('vault.add_doc_btn')}
        </Button>
      </div>
      <VaultCategoryFilter activeCategory={selectedCategory} onSelectCategory={setSelectedCategory} />
      <VaultGrid isLoading={isLoading} documents={documents} onView={handleView} onDelete={handleDelete} />
      <VaultDocViewModal document={activeDoc} onClose={() => setActiveDoc(null)} isOnline={isOnline} />
      <VaultUploadModal isOpen={showUploadModal} onClose={() => setShowUploadModal(false)} onUpload={async (fd) => {
        const up = await vaultApi.uploadDocument(fd);
        setDocuments((prev) => [up, ...prev]);
      }} />
    </div>
  );
}
