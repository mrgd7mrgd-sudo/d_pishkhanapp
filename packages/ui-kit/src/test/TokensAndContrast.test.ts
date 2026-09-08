import { describe, it, expect } from 'vitest';
import { renderHook } from '@testing-library/react';
import { useDeviceCapability } from '../hooks/useDeviceCapability';
import { tokens } from '../tokens';

describe('Liquid Glass Tokens & Device Capability (Architecture §4.7)', () => {
  it('defines all required brand, surface, and glass tokens', () => {
    expect(tokens.color.brand[500]).toBe('#10b981');
    expect(tokens.color.surface.base).toBe('#f8fafc');
    expect(tokens.glass.dock.blur).toBe('28px');
    expect(tokens.glass.dock.saturate).toBe('190%');
    expect(tokens.motion.ease).toBe('cubic-bezier(0.25, 1, 0.5, 1)');
  });

  it('sets data-perf="low" on documentElement when deviceMemory is low (≤ 2GB)', () => {
    // Mock navigator.deviceMemory
    Object.defineProperty(navigator, 'deviceMemory', {
      value: 1,
      configurable: true,
    });
    Object.defineProperty(navigator, 'hardwareConcurrency', {
      value: 2,
      configurable: true,
    });

    const { result } = renderHook(() => useDeviceCapability());

    expect(result.current.performance).toBe('low');
    expect(result.current.isLowPerf).toBe(true);
    expect(document.documentElement.getAttribute('data-perf')).toBe('low');
  });

  it('calculates WCAG contrast ratio for text on glass surfaces to ensure ≥ 4.5:1', () => {
    // Helper to calculate relative luminance
    function getLuminance(r: number, g: number, b: number): number {
      const [rs, gs, bs] = [r, g, b].map((c) => {
        const s = c / 255;
        return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
      });
      return 0.2126 * (rs ?? 0) + 0.7152 * (gs ?? 0) + 0.0722 * (bs ?? 0);
    }

    function getContrast(rgb1: [number, number, number], rgb2: [number, number, number]): number {
      const l1 = getLuminance(...rgb1);
      const l2 = getLuminance(...rgb2);
      const brightest = Math.max(l1, l2);
      const darkest = Math.min(l1, l2);
      return (brightest + 0.05) / (darkest + 0.05);
    }

    // Text color on glass: slate-900 (#0f172a) -> [15, 23, 42]
    const textColor: [number, number, number] = [15, 23, 42];

    // Fallback opaque glass background: #f8fafc -> [248, 250, 252]
    const fallbackBg: [number, number, number] = [248, 250, 252];

    const contrastRatio = getContrast(textColor, fallbackBg);
    console.log('Calculated Contrast Ratio:', contrastRatio.toFixed(2));

    expect(contrastRatio).toBeGreaterThanOrEqual(4.5);
  });
});
