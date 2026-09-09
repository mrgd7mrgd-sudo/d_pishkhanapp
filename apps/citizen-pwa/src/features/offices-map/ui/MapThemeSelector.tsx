import React from 'react';
import { useTranslation } from 'react-i18next';
import { clsx } from 'clsx';
import { Layers } from 'lucide-react';
import type { MapTheme } from '../types';
import { MAP_THEMES } from '../constants';

interface MapThemeSelectorProps {
  currentTheme: MapTheme;
  onSelectTheme: (theme: MapTheme) => void;
}

const THEME_KEYS: readonly MapTheme[] = ['standard-day', 'neshan', 'dreamy'];

export const MapThemeSelector: React.FC<MapThemeSelectorProps> = ({
  currentTheme,
  onSelectTheme,
}) => {
  const { t } = useTranslation();

  return (
    <div
      className="flex items-center gap-1 p-1 bg-white/95 backdrop-blur-xs rounded-xl shadow-md border border-slate-200"
      role="radiogroup"
      aria-label={t('offices.theme_title')}
    >
      <div className="flex items-center gap-1.5 px-2 text-xs font-medium text-slate-500">
        <Layers className="w-3.5 h-3.5" aria-hidden="true" />
        <span className="hidden sm:inline">{t('offices.theme_title')}</span>
      </div>
      {THEME_KEYS.map((theme) => {
        const isSelected = currentTheme === theme;
        return (
          <button
            key={theme}
            type="button"
            role="radio"
            aria-checked={isSelected}
            onClick={() => onSelectTheme(theme)}
            className={clsx(
              'px-2.5 py-1 text-xs font-medium rounded-lg transition-colors',
              isSelected
                ? 'bg-blue-600 text-white shadow-xs'
                : 'text-slate-600 hover:bg-slate-100',
            )}
          >
            {t(MAP_THEMES[theme].nameKey)}
          </button>
        );
      })}
    </div>
  );
};
