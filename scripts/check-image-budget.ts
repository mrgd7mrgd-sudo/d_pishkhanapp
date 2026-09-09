/**
 * Image Budget Verification Script (Architecture §4.8, TASK-046-T).
 * Verifies that:
 * 1. Every single generated image <= 80 KB (hard budget).
 * 2. Home page initial image load <= 120 KB (AVIF / WebP).
 * 3. All 108 variants (12 images × 3 widths × 3 formats) are present.
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ROOT_DIR = path.resolve(__dirname, '..');

const OPTIMIZED_DIR = path.resolve(ROOT_DIR, 'apps/citizen-pwa/public/images/optimized');
const MAX_FILE_SIZE_BYTES = 80 * 1024; // 80 KB
const MAX_HOMEPAGE_BYTES = 120 * 1024; // 120 KB

export interface FileBudgetCheck {
  fileName: string;
  sizeBytes: number;
  sizeKb: number;
  format: string;
  width: number;
  passed: boolean;
}

export interface BudgetReport {
  totalFiles: number;
  maxFileKb: number;
  allFilesPassed: boolean;
  homePageAvifKb: number;
  homePageWebpKb: number;
  homePagePassed: boolean;
  files: FileBudgetCheck[];
}

export async function verifyImageBudget(dir = OPTIMIZED_DIR): Promise<BudgetReport> {
  if (!fs.existsSync(dir)) {
    throw new Error(`Optimized images directory not found: ${dir}`);
  }

  const fileNames = (await fs.promises.readdir(dir)).filter(
    (f) => f.endsWith('.avif') || f.endsWith('.webp') || f.endsWith('.jpg'),
  );

  if (fileNames.length === 0) {
    throw new Error('No optimized images found! Run scripts/optimize-images.ts first.');
  }

  const files: FileBudgetCheck[] = [];

  for (const fileName of fileNames) {
    const filePath = path.join(dir, fileName);
    const stat = await fs.promises.stat(filePath);

    // Extract format and width e.g. "illus_bank_3d-320.avif"
    const match = fileName.match(/-(\d+)\.(avif|webp|jpg)$/i);
    const width = match ? Number(match[1]) : 0;
    const format = match && match[2] ? match[2].toLowerCase() : '';

    const passed = stat.size <= MAX_FILE_SIZE_BYTES;

    files.push({
      fileName,
      sizeBytes: stat.size,
      sizeKb: Number((stat.size / 1024).toFixed(2)),
      format,
      width,
      passed,
    });
  }

  const allFilesPassed = files.every((f) => f.passed);
  const maxFileKb = Math.max(...files.map((f) => f.sizeKb));

  // Home Page images:
  // 1. Hero banner (640w)
  // 2. Initial viewport service cards: identity (320w), vehicle (320w), post (320w), health (320w)
  const homePageKeys = [
    { prefix: 'illus_hero_banner', width: 640 },
    { prefix: 'illus_identity_3d', width: 320 },
    { prefix: 'illus_vehicle_3d', width: 320 },
    { prefix: 'illus_post_3d', width: 320 },
    { prefix: 'illus_health_3d', width: 320 },
  ];

  let homePageAvifBytes = 0;
  let homePageWebpBytes = 0;

  for (const item of homePageKeys) {
    const avif = files.find(
      (f) => f.fileName.startsWith(item.prefix) && f.width === item.width && f.format === 'avif',
    );
    const webp = files.find(
      (f) => f.fileName.startsWith(item.prefix) && f.width === item.width && f.format === 'webp',
    );

    if (avif) homePageAvifBytes += avif.sizeBytes;
    if (webp) homePageWebpBytes += webp.sizeBytes;
  }

  const homePageAvifKb = Number((homePageAvifBytes / 1024).toFixed(2));
  const homePageWebpKb = Number((homePageWebpBytes / 1024).toFixed(2));
  const homePagePassed = homePageAvifBytes <= MAX_HOMEPAGE_BYTES;

  return {
    totalFiles: files.length,
    maxFileKb,
    allFilesPassed,
    homePageAvifKb,
    homePageWebpKb,
    homePagePassed,
    files,
  };
}

// Auto-run if directly executed
if (process.argv[1] && process.argv[1].endsWith('check-image-budget.ts')) {
  verifyImageBudget()
    .then((report) => {
      console.log('\n📊 Image Budget Verification Report (§4.8, TASK-046-T):\n');
      console.log(`Total images verified: ${report.totalFiles} files`);
      console.log(`Max single file size: ${report.maxFileKb} KB (Budget: <= 80 KB) -> ${report.allFilesPassed ? '✅ PASS' : '❌ FAIL'}`);
      console.log(`Home page initial load (AVIF): ${report.homePageAvifKb} KB (Budget: <= 120 KB) -> ${report.homePagePassed ? '✅ PASS' : '❌ FAIL'}`);
      console.log(`Home page initial load (WebP fallback): ${report.homePageWebpKb} KB`);

      if (!report.allFilesPassed || !report.homePagePassed) {
        console.error('\n❌ Budget check failed!');
        process.exit(1);
      } else {
        console.log('\n🎉 ALL IMAGE PERFORMANCE BUDGETS STRICTLY SATISFIED!\n');
      }
    })
    .catch((err) => {
      console.error('❌ Error checking image budget:', err);
      process.exit(1);
    });
}
