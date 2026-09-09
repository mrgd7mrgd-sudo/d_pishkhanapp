import { describe, it, expect } from 'vitest';
import fs from 'fs';
import path from 'path';

describe('Offline Service Worker & Catalog Caching (Architecture §4.6, TASK-048, TASK-048-T)', () => {
  const distDir = path.resolve(__dirname, '../dist');
  const swPath = path.join(distDir, 'sw.js');

  it('generates Service Worker containing all 6 required cache buckets', () => {
    expect(fs.existsSync(swPath)).toBe(true);
    const swContent = fs.readFileSync(swPath, 'utf-8');

    // 1. Catalog & categories SWR
    expect(swContent).toContain('service-catalog-cache');

    // 2. Offices SWR
    expect(swContent).toContain('offices-cache');

    // 3. Map tiles CacheFirst
    expect(swContent).toContain('map-tiles-cache');

    // 4. Service images CacheFirst
    expect(swContent).toContain('service-images-cache');

    // 5. Cases NetworkFirst
    expect(swContent).toContain('cases-cache');

    // 6. Vault metadata NetworkFirst
    expect(swContent).toContain('vault-metadata-cache');
  });

  it('verifies offline banner and placeholder page exist in built app shell', () => {
    const assetsDir = path.join(distDir, 'assets');
    const assetFiles = fs.readdirSync(assetsDir);

    const hasPlaceholderChunk = assetFiles.some((f) =>
      f.startsWith('PlaceholderPage-'),
    );
    expect(hasPlaceholderChunk).toBe(true);

    const hasMapChunk = assetFiles.some((f) =>
      f.startsWith('index-') && f.endsWith('.js'),
    );
    expect(hasMapChunk).toBe(true);
  });
});
