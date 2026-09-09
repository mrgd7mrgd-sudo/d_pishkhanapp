/**
 * Image Optimization & Budget Verification Test Suite (§4.8, TASK-046, TASK-046-T).
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';
import { optimizeImages } from './optimize-images';
import { verifyImageBudget } from './check-image-budget';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ROOT_DIR = path.resolve(__dirname, '..');
const OPTIMIZED_DIR = path.resolve(ROOT_DIR, 'apps/citizen-pwa/public/images/optimized');
const MANIFEST_TS_PATH = path.resolve(ROOT_DIR, 'apps/citizen-pwa/src/assets/images/manifest.ts');

describe('Image Optimization & Performance Budget (§4.8, TASK-046, TASK-046-T)', () => {
  it('optimizes 12 prototype JPGs into 108 variants (AVIF/WebP/JPEG across 320/640/1024)', async () => {
    const result = await optimizeImages();

    expect(result.filesGenerated).toBe(108); // 12 images * 3 widths * 3 formats
    expect(result.items).toHaveLength(12);

    // Verify significant size reduction from 6.5MB prototype
    expect(result.totalOriginalBytes).toBeGreaterThan(6 * 1024 * 1024);
    // Even all 108 combined files should be well compressed
    expect(result.totalOptimizedBytes).toBeLessThan(result.totalOriginalBytes);

    // Verify manifest exists
    expect(fs.existsSync(path.join(OPTIMIZED_DIR, 'manifest.json'))).toBe(true);
    expect(fs.existsSync(MANIFEST_TS_PATH)).toBe(true);
  }, 45000);

  it('guarantees that every single image file is <= 80 KB', async () => {
    const report = await verifyImageBudget();

    expect(report.totalFiles).toBe(108);
    expect(report.allFilesPassed).toBe(true);
    expect(report.maxFileKb).toBeLessThanOrEqual(80);

    // Verify format diversity
    const avifFiles = report.files.filter((f) => f.format === 'avif');
    const webpFiles = report.files.filter((f) => f.format === 'webp');
    const jpgFiles = report.files.filter((f) => f.format === 'jpg');

    expect(avifFiles).toHaveLength(36);
    expect(webpFiles).toHaveLength(36);
    expect(jpgFiles).toHaveLength(36);

    // AVIF at 320 width should be extremely lightweight (<= 25 KB)
    const smallAvif = avifFiles.filter((f) => f.width === 320);
    for (const f of smallAvif) {
      expect(f.sizeKb).toBeLessThan(25);
    }
  });

  it('guarantees that initial home page images payload is <= 120 KB', async () => {
    const report = await verifyImageBudget();

    expect(report.homePagePassed).toBe(true);
    expect(report.homePageAvifKb).toBeLessThanOrEqual(120);

    // Even WebP fallback on home page should be well budgeted
    expect(report.homePageWebpKb).toBeLessThan(120);
  });

  it('generates valid blurhash strings for all 12 images', async () => {
    const manifestJson = JSON.parse(
      await fs.promises.readFile(path.join(OPTIMIZED_DIR, 'manifest.json'), 'utf-8'),
    ) as Array<{ key: string; blurhash: string }>;

    expect(manifestJson).toHaveLength(12);
    for (const item of manifestJson) {
      expect(item.blurhash).toBeDefined();
      expect(typeof item.blurhash).toBe('string');
      expect(item.blurhash.length).toBeGreaterThan(6);
    }
  });
});
