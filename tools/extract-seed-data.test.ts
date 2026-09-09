/**
 * Seed Data Verification Test (Architecture §6.9, TASK-039-T).
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';
import { extractSeedData, jalaliToGregorian, parseEstimatedDays, parseJalaliToUtcIso } from './extract-seed-data.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ROOT_DIR = path.resolve(__dirname, '..');
const DATA_DIR = path.resolve(ROOT_DIR, 'apps/api/database/seeders/data');

describe('Seed Data Extraction (TASK-039, TASK-039-T)', () => {
  it('correctly converts Jalali dates to Gregorian UTC ISO-8601', () => {
    const [gy, gm, gd] = jalaliToGregorian(1368, 4, 15);
    expect(gy).toBe(1989);
    expect(gm).toBe(7);
    expect(gd).toBe(6);

    const iso1 = parseJalaliToUtcIso('1368/04/15');
    expect(iso1).toBe('1989-07-06T00:00:00Z');

    const iso2 = parseJalaliToUtcIso('۱۴۰۳/۰۶/۰۲ - ساعت ۱۰:۲۴');
    expect(iso2).toBe('2024-08-23T10:24:00Z');
  });

  it('parses estimated days into integer min/max ranges', () => {
    const range1 = parseEstimatedDays('۳ الی ۵ روز کاری');
    expect(range1).toEqual({ min: 3, max: 5 });

    const range2 = parseEstimatedDays('۲۴ ساعت');
    expect(range2).toEqual({ min: 1, max: 1 });

    const range3 = parseEstimatedDays('۱۰ الی ۲۰ روز');
    expect(range3).toEqual({ min: 10, max: 20 });
  });

  it('verifies all 11 JSON seed files exist and meet required row counts', async () => {
    const summary = await extractSeedData();

    console.log('--- ACTUAL EXTRACTED ROW COUNTS ---');
    console.log(`Service Categories:       ${summary.categoriesCount} (Required: 10)`);
    console.log(`Services:                 ${summary.servicesCount} (Required: >=49)`);
    console.log(`Document Types:           ${summary.documentTypesCount} (Required: ~25)`);
    console.log(`Return Reasons:           ${summary.returnReasonsCount} (Required: 10)`);
    console.log(`Offices:                  ${summary.officesCount} (Required: ~15)`);
    console.log(`Consultation Specialties: ${summary.specialtiesCount} (Required: 6)`);
    console.log(`Advisors:                 ${summary.advisorsCount} (Required: ~8)`);
    console.log(`Subscription Plans:       ${summary.plansCount} (Required: 3)`);
    console.log(`Demo Citizens:            ${summary.citizensCount} (Required: 3)`);
    console.log(`Demo Cases:               ${summary.casesCount} (Required: 4)`);

    expect(summary.categoriesCount).toBe(10);
    expect(summary.servicesCount).toBeGreaterThanOrEqual(49);
    expect(summary.documentTypesCount).toBeGreaterThanOrEqual(20);
    expect(summary.returnReasonsCount).toBe(10);
    expect(summary.officesCount).toBeGreaterThanOrEqual(15);
    expect(summary.specialtiesCount).toBe(6);
    expect(summary.advisorsCount).toBeGreaterThanOrEqual(6);
    expect(summary.plansCount).toBe(3);
    expect(summary.citizensCount).toBe(3);
    expect(summary.casesCount).toBe(4);

    // Provinces file
    const provincesFile = path.join(DATA_DIR, 'provinces.json');
    expect(fs.existsSync(provincesFile)).toBe(true);
    const provinces = JSON.parse(fs.readFileSync(provincesFile, 'utf-8'));
    console.log(`Provinces:                ${provinces.length} (Required: 31)`);
    expect(provinces.length).toBe(31);
  });

  it('ensures NO amounts are float/decimal (all integer Rials)', () => {
    const services: any[] = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'services.json'), 'utf-8'));
    for (const service of services) {
      expect(Number.isInteger(service.fee_rials)).toBe(true);
      expect(service.fee_rials).toBeGreaterThanOrEqual(0);
    }

    const advisors: any[] = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'advisors.json'), 'utf-8'));
    for (const advisor of advisors) {
      expect(Number.isInteger(advisor.fee_per_session_rials)).toBe(true);
    }

    const plans: any[] = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'subscription_plans.json'), 'utf-8'));
    for (const plan of plans) {
      expect(Number.isInteger(plan.price_monthly_rials)).toBe(true);
    }

    const cases: any[] = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'demo_cases.json'), 'utf-8'));
    for (const c of cases) {
      expect(Number.isInteger(c.fee_paid_rials)).toBe(true);
      expect(Number.isInteger(c.office_share_rials)).toBe(true);
      expect(Number.isInteger(c.platform_share_rials)).toBe(true);
    }
  });

  it('ensures NO Persian string dates remain in any JSON file (all valid ISO-8601 UTC)', () => {
    const cases: any[] = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'demo_cases.json'), 'utf-8'));
    const isoRegex = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/;

    for (const c of cases) {
      expect(c.created_at).toMatch(isoRegex);
      expect(c.updated_at).toMatch(isoRegex);
      // Ensure no Persian year characters (۱۴۰۲, ۱۴۰۳, etc.) remain
      expect(c.created_at).not.toContain('۱۴');
      expect(c.created_at).not.toContain('ساعت');
    }

    const citizens: any[] = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'demo_citizens.json'), 'utf-8'));
    for (const citizen of citizens) {
      expect(citizen.birth_date).toMatch(/^\d{4}-\d{2}-\d{2}$/);
      expect(citizen.birth_date).not.toContain('۱۳');
      expect(citizen.birth_date).not.toContain('/');
    }
  });

  it('ensures derived attributes are strictly excluded from JSON schemas', () => {
    const categories: any[] = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'service_categories.json'), 'utf-8'));
    for (const cat of categories) {
      expect(cat.service_count).toBeUndefined();
      expect(cat.serviceCount).toBeUndefined();
    }

    const offices: any[] = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'offices.json'), 'utf-8'));
    for (const office of offices) {
      expect(office.distance_km).toBeUndefined();
      expect(office.distanceKm).toBeUndefined();
      expect(office.coords?.mapX).toBeUndefined();
      expect(office.coords?.mapY).toBeUndefined();
    }
  });
});
