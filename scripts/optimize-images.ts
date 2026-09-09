/**
 * Image Optimization Script (Architecture §4.8, TASK-046).
 * Converts 12 prototype JPGs into AVIF (q=60), WebP (q=75), and JPEG (q=75) in widths [320, 640, 1024].
 * Guarantees every file <= 80 KB, and generates Blurhash placeholders.
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';
import { encode } from 'blurhash';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ROOT_DIR = path.resolve(__dirname, '..');

const SRC_DIR = path.resolve(ROOT_DIR, 'prototype/src/assets/images');
const PUBLIC_OUT_DIR = path.resolve(ROOT_DIR, 'apps/citizen-pwa/public/images/optimized');
const ASSETS_TS_DIR = path.resolve(ROOT_DIR, 'apps/citizen-pwa/src/assets/images');

const TARGET_WIDTHS = [320, 640, 1024] as const;
const MAX_FILE_SIZE_BYTES = 80 * 1024; // 80 KB hard budget per §4.8

interface OptimizedVariant {
  width: number;
  format: 'avif' | 'webp' | 'jpg';
  path: string;
  sizeBytes: number;
  sizeKb: number;
}

interface ImageManifestItem {
  key: string;
  originalName: string;
  blurhash: string;
  aspectRatio: number;
  variants: Record<string, Record<number, string>>;
}

async function computeBlurhash(imageBuffer: Buffer): Promise<string> {
  const { data, info } = await sharp(imageBuffer)
    .resize(32, 32, { fit: 'inside' })
    .ensureAlpha()
    .raw()
    .toBuffer({ resolveWithObject: true });

  return encode(new Uint8ClampedArray(data), info.width, info.height, 4, 3);
}

async function processImage(
  fileName: string,
  outDir: string,
): Promise<{ manifestItem: ImageManifestItem; variants: OptimizedVariant[] }> {
  const filePath = path.join(SRC_DIR, fileName);
  const fileBuffer = await fs.promises.readFile(filePath);
  const metadata = await sharp(fileBuffer).metadata();

  const originalWidth = metadata.width ?? 1024;
  const originalHeight = metadata.height ?? 576;
  const aspectRatio = Number((originalWidth / originalHeight).toFixed(4));

  // Base key: clean timestamp suffix e.g. "illus_bank_3d_1788033234556" -> "illus_bank_3d"
  const cleanKey = fileName.replace(/_\d+\.jpg$/i, '').replace(/\.jpg$/i, '');
  const blurhash = await computeBlurhash(fileBuffer);

  const manifestItem: ImageManifestItem = {
    key: cleanKey,
    originalName: fileName,
    blurhash,
    aspectRatio,
    variants: {
      avif: {},
      webp: {},
      jpg: {},
    },
  };

  const variants: OptimizedVariant[] = [];

  for (const width of TARGET_WIDTHS) {
    const height = Math.round(width / aspectRatio);

    // 1. AVIF (Quality 60 as mandated by Architecture §4.8)
    const avifFileName = `${cleanKey}-${width}.avif`;
    const avifFilePath = path.join(outDir, avifFileName);
    const avifBuffer = await sharp(fileBuffer)
      .resize(width, height, { fit: 'cover' })
      .avif({ quality: 60, effort: 4 })
      .toBuffer();
    await fs.promises.writeFile(avifFilePath, avifBuffer);
    manifestItem.variants.avif![width] = `/images/optimized/${avifFileName}`;
    variants.push({
      width,
      format: 'avif',
      path: avifFileName,
      sizeBytes: avifBuffer.length,
      sizeKb: Number((avifBuffer.length / 1024).toFixed(2)),
    });

    // 2. WebP (Quality 75 with adaptive reduction to guarantee <= 80 KB)
    const webpFileName = `${cleanKey}-${width}.webp`;
    const webpFilePath = path.join(outDir, webpFileName);
    let webpQuality = 75;
    let webpBuffer = await sharp(fileBuffer)
      .resize(width, height, { fit: 'cover' })
      .webp({ quality: webpQuality, effort: 4 })
      .toBuffer();

    while (webpBuffer.length > MAX_FILE_SIZE_BYTES && webpQuality > 30) {
      webpQuality -= 5;
      webpBuffer = await sharp(fileBuffer)
        .resize(width, height, { fit: 'cover' })
        .webp({ quality: webpQuality, effort: 4 })
        .toBuffer();
    }

    await fs.promises.writeFile(webpFilePath, webpBuffer);
    manifestItem.variants.webp![width] = `/images/optimized/${webpFileName}`;
    variants.push({
      width,
      format: 'webp',
      path: webpFileName,
      sizeBytes: webpBuffer.length,
      sizeKb: Number((webpBuffer.length / 1024).toFixed(2)),
    });

    // 3. JPEG (Quality 75 with mozjpeg and adaptive reduction to guarantee <= 80 KB)
    const jpgFileName = `${cleanKey}-${width}.jpg`;
    const jpgFilePath = path.join(outDir, jpgFileName);
    let jpgQuality = 75;
    let jpgBuffer = await sharp(fileBuffer)
      .resize(width, height, { fit: 'cover' })
      .jpeg({ quality: jpgQuality, progressive: true, mozjpeg: true })
      .toBuffer();

    while (jpgBuffer.length > MAX_FILE_SIZE_BYTES && jpgQuality > 30) {
      jpgQuality -= 5;
      jpgBuffer = await sharp(fileBuffer)
        .resize(width, height, { fit: 'cover' })
        .jpeg({ quality: jpgQuality, progressive: true, mozjpeg: true })
        .toBuffer();
    }

    await fs.promises.writeFile(jpgFilePath, jpgBuffer);
    manifestItem.variants.jpg![width] = `/images/optimized/${jpgFileName}`;
    variants.push({
      width,
      format: 'jpg',
      path: jpgFileName,
      sizeBytes: jpgBuffer.length,
      sizeKb: Number((jpgBuffer.length / 1024).toFixed(2)),
    });
  }

  return { manifestItem, variants };
}

export async function optimizeImages(): Promise<{
  totalOriginalBytes: number;
  totalOptimizedBytes: number;
  filesGenerated: number;
  items: ImageManifestItem[];
  allVariants: OptimizedVariant[];
}> {
  if (!fs.existsSync(SRC_DIR)) {
    throw new Error(`Source directory not found: ${SRC_DIR}`);
  }

  await fs.promises.mkdir(PUBLIC_OUT_DIR, { recursive: true });
  await fs.promises.mkdir(ASSETS_TS_DIR, { recursive: true });

  const files = (await fs.promises.readdir(SRC_DIR)).filter((f) => f.endsWith('.jpg'));
  if (files.length === 0) {
    throw new Error('No JPG images found in prototype/src/assets/images');
  }

  let totalOriginalBytes = 0;
  for (const f of files) {
    const stat = await fs.promises.stat(path.join(SRC_DIR, f));
    totalOriginalBytes += stat.size;
  }

  const items: ImageManifestItem[] = [];
  const allVariants: OptimizedVariant[] = [];

  for (const file of files) {
    const { manifestItem, variants } = await processImage(file, PUBLIC_OUT_DIR);
    items.push(manifestItem);
    allVariants.push(...variants);
  }

  // Validate budget on every variant
  const violations = allVariants.filter((v) => v.sizeBytes > MAX_FILE_SIZE_BYTES);
  if (violations.length > 0) {
    const details = violations.map((v) => `${v.path}: ${v.sizeKb} KB > 80 KB`).join(', ');
    throw new Error(`Image budget violated! ${details}`);
  }

  // Write JSON manifest
  const manifestJsonPath = path.join(PUBLIC_OUT_DIR, 'manifest.json');
  await fs.promises.writeFile(manifestJsonPath, JSON.stringify(items, null, 2), 'utf-8');

  // Write TypeScript manifest
  const manifestTsContent = `/**
 * Auto-generated by scripts/optimize-images.ts - DO NOT EDIT MANUALLY.
 */

export interface OptimizedImageInfo {
  key: string;
  blurhash: string;
  aspectRatio: number;
  avifSrcSet: string;
  webpSrcSet: string;
  jpegSrcSet: string;
  fallbackSrc: string;
}

export const OPTIMIZED_IMAGES: Record<string, OptimizedImageInfo> = ${JSON.stringify(
    items.reduce<Record<string, unknown>>((acc, item) => {
      acc[item.key] = {
        key: item.key,
        blurhash: item.blurhash,
        aspectRatio: item.aspectRatio,
        avifSrcSet: TARGET_WIDTHS.map((w) => `${item.variants.avif![w]} ${w}w`).join(', '),
        webpSrcSet: TARGET_WIDTHS.map((w) => `${item.variants.webp![w]} ${w}w`).join(', '),
        jpegSrcSet: TARGET_WIDTHS.map((w) => `${item.variants.jpg![w]} ${w}w`).join(', '),
        fallbackSrc: item.variants.jpg![640],
      };
      return acc;
    }, {}),
    null,
    2,
  )};
`;
  await fs.promises.writeFile(path.join(ASSETS_TS_DIR, 'manifest.ts'), manifestTsContent, 'utf-8');

  const totalOptimizedBytes = allVariants.reduce((sum, v) => sum + v.sizeBytes, 0);

  return {
    totalOriginalBytes,
    totalOptimizedBytes,
    filesGenerated: allVariants.length,
    items,
    allVariants,
  };
}

// Auto-run if directly executed
if (process.argv[1] && process.argv[1].endsWith('optimize-images.ts')) {
  optimizeImages()
    .then((result) => {
      console.log('✅ Image Optimization Completed Successfully:');
      console.log(`- Original prototype size: ${(result.totalOriginalBytes / (1024 * 1024)).toFixed(2)} MB`);
      console.log(`- Total generated files: ${result.filesGenerated} (12 images × 3 widths × 3 formats)`);
      console.log(`- Total all variants size: ${(result.totalOptimizedBytes / (1024 * 1024)).toFixed(2)} MB`);
      console.log(`- Max individual file size: ${Math.max(...result.allVariants.map((v) => v.sizeKb))} KB (Budget: <= 80 KB)`);
    })
    .catch((err) => {
      console.error('❌ Error optimizing images:', err);
      process.exit(1);
    });
}
