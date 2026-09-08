import { describe, it, expect } from 'vitest';
import fs from 'fs';
import path from 'path';

describe('Citizen PWA Offline & Installability (§4.6, TASK-020)', () => {
  const distDir = path.resolve(__dirname, '../dist');

  it('generates a valid web manifest with standalone display, RTL and icons', () => {
    const manifestPath = path.join(distDir, 'manifest.webmanifest');
    expect(fs.existsSync(manifestPath)).toBe(true);

    const manifestContent = JSON.parse(fs.readFileSync(manifestPath, 'utf-8'));
    expect(manifestContent.name).toBe('پیشخوان هوشمند شهروندی');
    expect(manifestContent.display).toBe('standalone');
    expect(manifestContent.dir).toBe('rtl');
    expect(manifestContent.lang).toBe('fa');
    expect(manifestContent.icons.length).toBeGreaterThanOrEqual(2);
    expect(manifestContent.icons.some((i: any) => i.purpose === 'maskable')).toBe(true);
  });

  it('generates Service Worker with precached app shell and runtime caches', () => {
    const swPath = path.join(distDir, 'sw.js');
    expect(fs.existsSync(swPath)).toBe(true);

    const swContent = fs.readFileSync(swPath, 'utf-8');
    // Verify workbox precaching and runtime caching strategies
    expect(swContent).toContain('precacheAndRoute');
    expect(swContent).toContain('service-catalog-cache');
    expect(swContent).toContain('cases-cache');
    expect(swContent).toContain('offices-cache');
    expect(swContent).toContain('SKIP_WAITING');
  });

  it('verifies manifest link is present in index.html', () => {
    const indexPath = path.join(distDir, 'index.html');
    const indexHtml = fs.readFileSync(indexPath, 'utf-8');
    expect(indexHtml).toContain('manifest.webmanifest');
  });
});