/**
 * Liquid Glass Design System Tokens (Architecture §4.7)
 */
export const tokens = {
  color: {
    brand: {
      50: '#ecfdf5',
      500: '#10b981',
      600: '#059669',
      700: '#047857',
    },
    surface: {
      base: '#f8fafc',
      raised: '#ffffff',
      sunken: '#f1f5f9',
      inverse: '#0f172a',
    },
    status: {
      done: '#10b981',
      current: '#3b82f6',
      pending: '#94a3b8',
      warning: '#f59e0b',
      failed: '#ef4444',
    },
    turnOwner: {
      citizen: '#3b82f6',
      office: '#10b981',
      government: '#8b5cf6',
      postal: '#f59e0b',
      system: '#64748b',
    },
  },
  glass: {
    dock: {
      blur: '28px',
      saturate: '190%',
      bg: 'rgba(255, 255, 255, 0.62)',
      border: 'rgba(255, 255, 255, 0.55)',
    },
    lens: {
      blur: '22px',
      saturate: '175%',
      bg: 'rgba(255, 255, 255, 0.42)',
    },
    button: {
      blur: '24px',
      saturate: '190%',
      bg: 'rgba(255, 255, 255, 0.58)',
    },
    dispersion:
      'conic-gradient(from 180deg, #ff008040, #ffae0040, #00ffd140, #7a00ff40, #ff008040)',
  },
  radius: {
    sm: '8px',
    md: '12px',
    lg: '16px',
    xl: '22px',
    pill: '999px',
  },
  space: {
    1: '4px',
    2: '8px',
    3: '12px',
    4: '16px',
    5: '20px',
    6: '24px',
    8: '32px',
  },
  motion: {
    fast: '140ms',
    base: '240ms',
    slow: '420ms',
    ease: 'cubic-bezier(0.25, 1, 0.5, 1)',
  },
  font: {
    family: '"Vazirmatn", system-ui, sans-serif',
  },
  z: {
    nav: 40,
    sheet: 50,
    modal: 60,
    toast: 70,
  },
} as const;

export type Tokens = typeof tokens;
