import { describe, it, expect } from 'vitest';
import { SW_RUNTIME_CACHING } from '../sw-strategies';

describe('Service Worker Caching Strategies (Architecture §4.6, TASK-048, TASK-048-T)', () => {
  it('defines exactly 8 runtime caching rules matching §4.6 specifications', () => {
    expect(SW_RUNTIME_CACHING).toHaveLength(8);
  });

  it('configures Service Catalog caching as StaleWhileRevalidate with 7-day retention', () => {
    const catalogRule = SW_RUNTIME_CACHING[0]!;
    expect(catalogRule.handler).toBe('StaleWhileRevalidate');
    expect(catalogRule.options?.expiration.maxAgeSeconds).toBe(7 * 24 * 60 * 60);

    const regex = catalogRule.urlPattern as RegExp;
    expect(regex.test('/api/v1/services')).toBe(true);
    expect(regex.test('/api/v1/services?category_id=identity')).toBe(true);
    expect(regex.test('/api/v1/categories')).toBe(true);
  });

  it('configures Offices Network caching as StaleWhileRevalidate with 3-day retention', () => {
    const officesRule = SW_RUNTIME_CACHING[1]!;
    expect(officesRule.handler).toBe('StaleWhileRevalidate');
    expect(officesRule.options?.expiration.maxAgeSeconds).toBe(3 * 24 * 60 * 60);

    const regex = officesRule.urlPattern as RegExp;
    expect(regex.test('/api/v1/offices')).toBe(true);
    expect(regex.test('/api/v1/offices/nearby?lat=35.7&lng=51.4')).toBe(true);
  });

  it('configures Map Tiles caching as CacheFirst capped at 300 tiles and 30-day retention', () => {
    const tilesRule = SW_RUNTIME_CACHING[2]!;
    expect(tilesRule.handler).toBe('CacheFirst');
    expect(tilesRule.options?.expiration.maxEntries).toBe(300);
    expect(tilesRule.options?.expiration.maxAgeSeconds).toBe(30 * 24 * 60 * 60);

    const regex = tilesRule.urlPattern as RegExp;
    expect(regex.test('/tiles/standard-day/14/10543/6542.png')).toBe(true);
    expect(regex.test('/tiles/neshan/12/2635/1635.png')).toBe(true);
    expect(regex.test('/tiles/dreamy/15/21086/13084.png')).toBe(true);
    expect(regex.test('https://api.neshan.org/v4/tiles/raster/standard-day/1/2/3.png')).toBe(true);
  });

  it('configures Service Images caching as CacheFirst capped at 60 files and 30-day retention', () => {
    const imagesRule = SW_RUNTIME_CACHING[3]!;
    expect(imagesRule.handler).toBe('CacheFirst');
    expect(imagesRule.options?.expiration.maxEntries).toBe(60);
    expect(imagesRule.options?.expiration.maxAgeSeconds).toBe(30 * 24 * 60 * 60);

    const regex = imagesRule.urlPattern as RegExp;
    expect(regex.test('/images/optimized/hero-banner-320w.avif')).toBe(true);
    expect(regex.test('/assets/service-icon.webp')).toBe(true);
  });

  it('configures User Cases caching as NetworkFirst (3s timeout) with 24-hour retention', () => {
    const casesRule = SW_RUNTIME_CACHING[4]!;
    expect(casesRule.handler).toBe('NetworkFirst');
    expect(casesRule.options?.networkTimeoutSeconds).toBe(3);
    expect(casesRule.options?.expiration.maxAgeSeconds).toBe(24 * 60 * 60);

    const regex = casesRule.urlPattern as RegExp;
    expect(regex.test('/api/v1/cases')).toBe(true);
    expect(regex.test('/api/v1/cases/PK-1405-9921')).toBe(true);
  });

  it('configures Vault Metadata caching as NetworkFirst with 24-hour retention', () => {
    const vaultRule = SW_RUNTIME_CACHING[5]!;
    expect(vaultRule.handler).toBe('NetworkFirst');
    expect(vaultRule.options?.expiration.maxAgeSeconds).toBe(24 * 60 * 60);

    const regex = vaultRule.urlPattern as RegExp;
    expect(regex.test('/api/v1/documents')).toBe(true);
    expect(regex.test('/api/v1/documents?category=personal')).toBe(true);
  });

  it('ensures Document Contents / Signed URLs are strictly NetworkOnly (never cached)', () => {
    const docContentRule = SW_RUNTIME_CACHING[6]!;
    expect(docContentRule.handler).toBe('NetworkOnly');

    const regex = docContentRule.urlPattern as RegExp;
    expect(regex.test('/api/v1/documents/doc-1/download')).toBe(true);
    expect(regex.test('/api/v1/documents/doc-1/view')).toBe(true);
    expect(regex.test('/api/v1/documents/signed-url')).toBe(true);
  });

  it('ensures Payments and OTP Auth are strictly NetworkOnly', () => {
    const paymentsAuthRule = SW_RUNTIME_CACHING[7]!;
    expect(paymentsAuthRule.handler).toBe('NetworkOnly');

    const regex = paymentsAuthRule.urlPattern as RegExp;
    expect(regex.test('/api/v1/payments/verify')).toBe(true);
    expect(regex.test('/api/v1/auth/otp/request')).toBe(true);
    expect(regex.test('/api/v1/auth/otp/verify')).toBe(true);
  });
});
