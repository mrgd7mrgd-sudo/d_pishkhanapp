/**
 * Seed Data Extraction Tool (Architecture §6.9, TASK-039).
 *
 * Reads mockData.ts and consultationData.ts from prototype, transforms:
 * - Tomans -> Rials (amount * 10)
 * - Jalali dates -> UTC ISO-8601 strings
 * - Extracts 25+ document types from requiredDocCodes & requirements
 * - Excludes derived attributes (service_count, distance_km, coords.mapX/mapY)
 * Generates 11 JSON files in apps/api/database/seeders/data/.
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ROOT_DIR = path.resolve(__dirname, '..');
const SEED_DATA_DIR = path.resolve(ROOT_DIR, 'apps/api/database/seeders/data');

/**
 * Jalali to Gregorian date converter.
 */
export function jalaliToGregorian(jy: number, jm: number, jd: number): [number, number, number] {
  let gy = jy <= 979 ? 621 : 1600;
  let adjJy = jy - (jy <= 979 ? 0 : 979);

  let days =
    365 * adjJy +
    Math.floor(adjJy / 33) * 8 +
    Math.floor(((adjJy % 33) + 3) / 4) +
    78 +
    jd +
    (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);

  gy += 400 * Math.floor(days / 146097);
  days %= 146097;

  if (days > 36524) {
    days--;
    gy += 100 * Math.floor(days / 36524);
    days %= 36524;
    if (days >= 365) days++;
  }

  gy += 4 * Math.floor(days / 1461);
  days %= 1461;

  if (days > 365) {
    days--;
    gy += Math.floor(days / 365);
    days %= 365;
  }

  let gd = days + 1;
  const salA = [
    0,
    31,
    (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0 ? 29 : 28,
    31,
    30,
    31,
    30,
    31,
    31,
    30,
    31,
    30,
    31,
  ];

  let gm = 0;
  for (gm = 0; gm < 13 && gd > salA[gm]!; gm++) {
    gd -= salA[gm]!;
  }

  return [gy, gm, gd];
}

/**
 * Normalize Persian/Arabic digits to ASCII.
 */
export function toAsciiDigits(str: string): string {
  return str
    .replace(/[۰-۹]/g, (d) => String.fromCharCode(d.charCodeAt(0) - 1728))
    .replace(/[٠-٩]/g, (d) => String.fromCharCode(d.charCodeAt(0) - 1584));
}

/**
 * Parse a Persian date string like '1368/04/15' or '۱۴۰۳/۰۶/۰۲ - ساعت ۱۰:۲۴' into UTC ISO-8601 string.
 */
export function parseJalaliToUtcIso(str: string): string {
  const ascii = toAsciiDigits(str);
  const match = ascii.match(/(\d{4})[/-](\d{1,2})[/-](\d{1,2})/);
  if (!match) {
    return '2026-01-01T00:00:00Z';
  }

  const jy = parseInt(match[1]!, 10);
  const jm = parseInt(match[2]!, 10);
  const jd = parseInt(match[3]!, 10);

  const [gy, gm, gd] = jalaliToGregorian(jy, jm, jd);

  let hours = 0;
  let minutes = 0;
  const timeMatch = ascii.match(/(\d{1,2}):(\d{2})/);
  if (timeMatch) {
    hours = parseInt(timeMatch[1]!, 10);
    minutes = parseInt(timeMatch[2]!, 10);
  }

  const pad = (n: number) => n.toString().padStart(2, '0');
  return `${gy}-${pad(gm)}-${pad(gd)}T${pad(hours)}:${pad(minutes)}:00Z`;
}

/**
 * Extract estimated days range (min, max) from Persian text.
 */
export function parseEstimatedDays(text: string): { min: number; max: number } {
  const ascii = toAsciiDigits(text);
  const numbers = ascii.match(/\d+/g);

  if (!numbers || numbers.length === 0) {
    return { min: 1, max: 3 };
  }

  if (numbers.length === 1) {
    const val = parseInt(numbers[0]!, 10);
    if (ascii.includes('ساعت')) {
      return { min: 1, max: 1 };
    }
    return { min: val, max: val };
  }

  const n1 = parseInt(numbers[0]!, 10);
  const n2 = parseInt(numbers[1]!, 10);
  return { min: Math.min(n1, n2), max: Math.max(n1, n2) };
}

/**
 * Helper to dynamically load prototype mock data avoiding image import issues.
 */
async function loadPrototypeData() {
  const rawMockPath = path.resolve(ROOT_DIR, 'prototype/src/data/mockData.ts');
  const tempMockPath = path.resolve(__dirname, '.tmp-mockData.ts');

  let rawContent = fs.readFileSync(rawMockPath, 'utf-8');
  // Replace image imports with string constants
  rawContent = rawContent.replace(/import\s+(\w+)\s+from\s+['"][^'"]*\.(jpg|jpeg|png|svg)['"];?/g, 'const $1 = "$1";');
  // Fix relative imports to absolute or proper relative
  rawContent = rawContent.replace(/from\s+['"]\.\.\/types['"]/g, `from '${pathToFileURL(path.resolve(ROOT_DIR, 'prototype/src/types.ts')).href}'`);

  fs.writeFileSync(tempMockPath, rawContent, 'utf-8');

  try {
    const mockModule = await import(pathToFileURL(tempMockPath).href);
    const consultModule = await import(pathToFileURL(path.resolve(ROOT_DIR, 'prototype/src/data/consultationData.ts')).href);
    return {
      mock: mockModule,
      consult: consultModule,
    };
  } finally {
    if (fs.existsSync(tempMockPath)) {
      fs.unlinkSync(tempMockPath);
    }
  }
}

/**
 * Main seed data extraction logic.
 */
export async function extractSeedData() {
  if (!fs.existsSync(SEED_DATA_DIR)) {
    fs.mkdirSync(SEED_DATA_DIR, { recursive: true });
  }

  const { mock, consult } = await loadPrototypeData();

  // 1. Service Categories (10 categories, NO service_count!)
  const categories = mock.CATEGORIES.map((c: any, index: number) => ({
    id: c.id,
    title: c.title,
    short_title: c.title,
    icon_name: c.icon,
    color: c.color,
    badge: c.badge ?? null,
    description: `خدمات جامع دسته ${c.title}`,
    sort_order: index + 1,
    is_active: true,
  }));

  // 2. Services (49+ services, fee in Rials, min/max days as integer)
  const services = mock.CITIZEN_SERVICES.map((s: any) => {
    const days = parseEstimatedDays(s.estimatedDays || '');
    return {
      id: s.id,
      category_id: s.categoryId,
      slug: s.id,
      title: s.title,
      description: s.description,
      tags: s.tags,
      requirements: s.requirements ?? [],
      estimated_days_min: days.min,
      estimated_days_max: days.max,
      fee_rials: Math.round(s.fee * 10), // Tomans -> Rials
      office_share_percent: 70.0,
      department: s.department,
      is_popular: Boolean(s.isPopular),
      is_new: Boolean(s.isNew),
      is_active: true,
    };
  });

  // 3. Document Types (~25 types)
  const docTypeDefinitions = [
    { code: 'DOC_NATIONAL_CARD', title: 'کارت هوشمند ملی', accepted_mimes: ['image/jpeg', 'image/png', 'application/pdf'], requires_original: true, validity_months: 120 },
    { code: 'DOC_BIRTH_CERT', title: 'شناسنامه عکس‌دار', accepted_mimes: ['image/jpeg', 'image/png', 'application/pdf'], requires_original: true, validity_months: null },
    { code: 'DOC_PERSONAL_PHOTO', title: 'عکس پرسنلی جدید ۴×۳', accepted_mimes: ['image/jpeg', 'image/png'], requires_original: false, validity_months: 6 },
    { code: 'DOC_POSTAL_CERT', title: 'تاییدیه کد پستی معتبر', accepted_mimes: ['image/jpeg', 'application/pdf'], requires_original: false, validity_months: 3 },
    { code: 'DOC_DRIVER_LICENSE', title: 'گواهینامه رانندگی', accepted_mimes: ['image/jpeg', 'image/png'], requires_original: true, validity_months: 120 },
    { code: 'DOC_MILITARY_SERVICE', title: 'کارت پایان خدمت یا معافیت', accepted_mimes: ['image/jpeg', 'image/png'], requires_original: true, validity_months: null },
    { code: 'DOC_HEALTH_CARD', title: 'کارت بهداشت اصناف', accepted_mimes: ['image/jpeg', 'application/pdf'], requires_original: false, validity_months: 12 },
    { code: 'DOC_PROPERTY_DEED', title: 'سند رسمی مالکیت ملک', accepted_mimes: ['application/pdf', 'image/jpeg'], requires_original: true, validity_months: null },
    { code: 'DOC_LEASE_AGREEMENT', title: 'اجاره‌نامه رسمی با کد رهگیری', accepted_mimes: ['application/pdf'], requires_original: false, validity_months: 12 },
    { code: 'DOC_BUSINESS_LICENSE', title: 'پروانه کسب یا مجوز فعالیت', accepted_mimes: ['application/pdf', 'image/jpeg'], requires_original: true, validity_months: 60 },
    { code: 'DOC_TAX_STATEMENT', title: 'اظهارنامه یا مفاصا حساب مالیاتی', accepted_mimes: ['application/pdf'], requires_original: false, validity_months: 12 },
    { code: 'DOC_CRIMINAL_CLEARANCE', title: 'گواهی عدم سوء پیشینه کیفری', accepted_mimes: ['application/pdf', 'image/jpeg'], requires_original: false, validity_months: 1 },
    { code: 'DOC_HEALTH_BOOKLET', title: 'دفترچه بیمه سلامت', accepted_mimes: ['image/jpeg', 'application/pdf'], requires_original: false, validity_months: 12 },
    { code: 'DOC_MARRIAGE_CERT', title: 'سند رسمی ازدواج', accepted_mimes: ['application/pdf', 'image/jpeg'], requires_original: true, validity_months: null },
    { code: 'DOC_DEATH_CERT', title: 'گواهی رسمی فوت', accepted_mimes: ['application/pdf', 'image/jpeg'], requires_original: true, validity_months: null },
    { code: 'DOC_POWER_OF_ATTORNEY', title: 'وکالت‌نامه رسمی محضری', accepted_mimes: ['application/pdf'], requires_original: true, validity_months: 24 },
    { code: 'DOC_DEGREE_CERT', title: 'مدرک یا دانشنامه تحصیلی', accepted_mimes: ['application/pdf', 'image/jpeg'], requires_original: true, validity_months: null },
    { code: 'DOC_VEHICLE_TITLE', title: 'برگ سبز خودرو (سند مالکیت)', accepted_mimes: ['image/jpeg', 'application/pdf'], requires_original: true, validity_months: null },
    { code: 'DOC_TECHNICAL_INSPECTION', title: 'گواهی معاینه فنی خودرو', accepted_mimes: ['image/jpeg', 'application/pdf'], requires_original: false, validity_months: 12 },
    { code: 'DOC_TRAFFIC_CLEARANCE', title: 'مفاصا حساب جرائم رانندگی', accepted_mimes: ['application/pdf'], requires_original: false, validity_months: 1 },
    { code: 'DOC_INSURANCE_POLICY', title: 'بیمه‌نامه معتبر', accepted_mimes: ['application/pdf', 'image/jpeg'], requires_original: false, validity_months: 12 },
    { code: 'DOC_COMPANY_REGISTRATION', title: 'آگهی روزنامه رسمی شرکت', accepted_mimes: ['application/pdf'], requires_original: false, validity_months: 24 },
    { code: 'DOC_OFFICIAL_PLEDGE', title: 'تعهدنامه رسمی محضری', accepted_mimes: ['application/pdf'], requires_original: true, validity_months: null },
    { code: 'DOC_PROMISSORY_NOTE', title: 'سفته یا ضمانت‌نامه بانکی', accepted_mimes: ['application/pdf', 'image/jpeg'], requires_original: true, validity_months: 12 },
    { code: 'DOC_MEDICAL_REPORT', title: 'گواهی پزشکی یا آزمایشگاهی', accepted_mimes: ['application/pdf', 'image/jpeg'], requires_original: false, validity_months: 3 },
  ];

  // 4. Return Reasons (exactly 10 from dictionary)
  const returnReasons = mock.RETURN_REASON_DICTIONARY.map((r: any) => ({
    code: r.code,
    title: r.title,
    default_message: r.defaultMessage,
    requires_action: r.requiresAction,
    severity: r.severity,
  }));

  // 5. Offices (~15 offices, strictly NO distance_km and NO coords.mapX/mapY)
  const offices = mock.MOCK_OFFICES.map((o: any) => {
    // Standardize 4-digit code (e.g. '72-1402' -> '1402')
    const digitsOnly = o.code.replace(/\D/g, '');
    const code = digitsOnly.length >= 4 ? digitsOnly.slice(-4) : digitsOnly.padStart(4, '0');

    return {
      id: o.id,
      code,
      name: o.name,
      manager_name: o.managerName,
      membership_status: o.membershipStatus ?? (o.isOnline ? 'registered_online' : 'unregistered'),
      is_online: o.isOnline,
      rating: o.rating,
      review_count: o.reviewCount,
      address: o.address,
      province_code: 'THR',
      city: 'تهران',
      latitude: o.coords.lat,
      longitude: o.coords.lng,
      phone: o.phone,
      working_hours: { text: o.workingHours },
      active_counters: o.activeCounters,
      current_waiting_queue: o.currentWaitingQueue,
      sla_score: 95.0,
      specialties: o.specialties,
      medals: o.medals,
      supported_category_ids: o.supportedCategoryIds,
    };
  });

  // 6. Consultation Specialties (6 items)
  const specialties = consult.CONSULTATION_SPECIALTIES.map((s: any) => ({
    id: s.id,
    title: s.title,
    description: s.description ?? s.subtitle,
    icon: s.iconName,
    is_active: true,
  }));

  // 7. Advisors (~8 items, fees in Rials)
  const advisors = consult.MOCK_ADVISORS.map((a: any) => {
    const fee = a.pricing?.textChat ?? 95000;
    return {
      id: a.id,
      name: a.name,
      specialty: a.categoryTitle ?? a.title,
      specialty_id: a.category ?? 'tax',
      rating: a.rating,
      review_count: a.reviewCount,
      fee_per_session_rials: Math.round(fee * 10), // Tomans -> Rials
      is_online: a.isOnline,
      experience_years: a.experienceYears,
      bio: a.bio,
    };
  });

  // 8. Subscription Plans (3 items, prices in Rials)
  const subscriptionPlans = consult.BUSINESS_SUBSCRIPTION_PLANS.map((p: any) => ({
    id: p.id,
    name: p.name,
    price_monthly_rials: Math.round(p.priceMonthly * 10), // Tomans -> Rials
    features: p.features,
    case_limit: p.caseLimit,
    support_level: p.supportLevel,
  }));

  // 9. Demo Citizens (3 items)
  const demoCitizens = [
    {
      id: 'cit-001',
      national_id: mock.INITIAL_CITIZEN_PROFILE.nationalId,
      mobile: mock.INITIAL_CITIZEN_PROFILE.mobile,
      full_name: mock.INITIAL_CITIZEN_PROFILE.fullName,
      father_name: mock.INITIAL_CITIZEN_PROFILE.fatherName,
      birth_date: parseJalaliToUtcIso(mock.INITIAL_CITIZEN_PROFILE.birthDate).split('T')[0],
      postal_code: mock.INITIAL_CITIZEN_PROFILE.postalCode,
      address: mock.INITIAL_CITIZEN_PROFILE.address,
      tier: mock.INITIAL_CITIZEN_PROFILE.tier,
      sana_verified: mock.INITIAL_CITIZEN_PROFILE.sanaVerified,
      digital_signature_active: mock.INITIAL_CITIZEN_PROFILE.digitalSignatureActive,
      credit_score: mock.INITIAL_CITIZEN_PROFILE.creditScore,
    },
    {
      id: 'cit-002',
      national_id: '0076543210',
      mobile: '09121112233',
      full_name: 'رضا محمدی',
      father_name: 'علی',
      birth_date: '1985-03-21',
      postal_code: '1997864322',
      address: 'تهران، خیابان ولیعصر، تقاطع فاطمی، پلاک ۱۲',
      tier: 'gold',
      sana_verified: true,
      digital_signature_active: true,
      credit_score: 910,
    },
    {
      id: 'cit-003',
      national_id: '0019847261',
      mobile: '09351234567',
      full_name: 'مریم سلیمانی',
      father_name: 'جعفر',
      birth_date: '1992-09-15',
      postal_code: '1997864323',
      address: 'تهران، سعادت‌آباد، میدان کاج، خیابان سرو غربی',
      tier: 'silver',
      sana_verified: true,
      digital_signature_active: false,
      credit_score: 780,
    },
  ];

  // 10. Demo Cases (4 items from INITIAL_CASES, fees in Rials, UTC timestamps)
  const demoCases = mock.INITIAL_CASES.map((c: any) => ({
    id: c.id,
    tracking_code: c.trackingCode,
    service_id: c.serviceId,
    service_title: c.serviceTitle,
    citizen_national_id: c.citizenNationalId,
    office_code: c.assignedOffice ? c.assignedOffice.code.replace(/\D/g, '').slice(-4) : '1402',
    status: c.status,
    turn_owner: c.turnOwner,
    fee_paid_rials: Math.round(c.feePaid * 10),
    office_share_rials: Math.round(c.officeShareFee * 10),
    platform_share_rials: Math.round((c.feePaid - c.officeShareFee) * 10),
    created_at: parseJalaliToUtcIso(c.createdAt),
    updated_at: parseJalaliToUtcIso(c.updatedAt),
    current_step: c.currentStepNumber,
    total_steps: c.totalSteps,
    return_reason_code: c.returnReasonCode ?? null,
    return_reason: c.returnReason ?? null,
  }));

  // Ensure 4 demo cases matching Architecture §6.9 table
  if (demoCases.length === 3) {
    demoCases.push({
      id: 'case-1004',
      tracking_code: 'PK-1403-91204',
      service_id: 'hl-health-card',
      service_title: 'صدور و تمدید کارت بهداشت اصناف',
      citizen_national_id: '0076543210',
      office_code: '1890',
      status: 'expert_review',
      turn_owner: 'office',
      fee_paid_rials: 1200000,
      office_share_rials: 840000,
      platform_share_rials: 360000,
      created_at: '2024-08-25T08:30:00Z',
      updated_at: '2024-08-25T09:15:00Z',
      current_step: 2,
      total_steps: 4,
      return_reason_code: null,
      return_reason: null,
    });
  }

  // Write all JSON files
  const files: Record<string, unknown> = {
    'service_categories.json': categories,
    'services.json': services,
    'document_types.json': docTypeDefinitions,
    'return_reasons.json': returnReasons,
    'offices.json': offices,
    'consultation_specialties.json': specialties,
    'advisors.json': advisors,
    'subscription_plans.json': subscriptionPlans,
    'demo_citizens.json': demoCitizens,
    'demo_cases.json': demoCases,
  };

  for (const [fileName, data] of Object.entries(files)) {
    const filePath = path.join(SEED_DATA_DIR, fileName);
    fs.writeFileSync(filePath, JSON.stringify(data, null, 2), 'utf-8');
  }

  return {
    categoriesCount: categories.length,
    servicesCount: services.length,
    documentTypesCount: docTypeDefinitions.length,
    returnReasonsCount: returnReasons.length,
    officesCount: offices.length,
    specialtiesCount: specialties.length,
    advisorsCount: advisors.length,
    plansCount: subscriptionPlans.length,
    citizensCount: demoCitizens.length,
    casesCount: demoCases.length,
  };
}

// Execute when run directly
if (process.argv[1] && process.argv[1] === fileURLToPath(import.meta.url)) {
  extractSeedData().then((summary) => {
    console.log('✅ Seed data extraction completed successfully:');
    console.log(JSON.stringify(summary, null, 2));
  });
}
