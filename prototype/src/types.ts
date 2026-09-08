export type ServiceTag = 'online' | 'semi-online' | 'in-person';

export interface ServiceCategory {
  id: string;
  title: string;
  shortTitle: string;
  iconName: string;
  color: string;
  badge?: string;
  description: string;
  serviceCount: number;
  image?: string;
}

export interface CitizenService {
  id: string;
  title: string;
  categoryId: string;
  tags: ServiceTag[]; // e.g. ['in-person', 'semi-online'] or ['online']
  description: string;
  requirements: string[];
  estimatedDays: string;
  fee: number; // in Tomans
  icon: string;
  image?: string;
  isPopular?: boolean;
  isNew?: boolean;
  department: string; // e.g. "ثبت احوال کشور", "پلیس راهور", "وزارت بهداشت"
  requiredDocCodes?: string[];
}

export interface OfficeSpecialty {
  title: string;
  icon: string;
}

export interface OfficeReview {
  id: string;
  userName: string;
  rating: number;
  date: string;
  comment: string;
  serviceTitle: string;
}

export type OfficeMembershipStatus = 'registered_online' | 'registered_offline' | 'unregistered';

export interface PishkhanOffice {
  id: string;
  code: string;
  name: string;
  managerName: string;
  membershipStatus?: OfficeMembershipStatus; // 'registered_online' | 'registered_offline' | 'unregistered'
  isOnline: boolean; // Ready to receive requests live (true if registered_online)
  rating: number; // e.g. 4.9
  reviewCount: number;
  medals: string[]; // e.g. ["دفتر برتر استان", "پاسخگویی زیر ۳۰ دقیقه", "رضایت ۹۹٪", "نشان طلایی سرعت"]
  specialties: string[]; // e.g. ["تخصصی ثبت احوال", "خدمات خودرویی VIP", "مالیات و ثبت شرکت"]
  address: string;
  region: string;
  city: string;
  distanceKm: number;
  coords: {
    lat: number;
    lng: number;
    mapX: number; // percentage for interactive visual map
    mapY: number;
  };
  phone: string;
  workingHours: string;
  activeCounters: number;
  currentWaitingQueue: number;
  supportedCategoryIds: string[];
}

export type CaseStatus = 
  | 'searching_office'
  | 'assigned_to_office'
  | 'expert_review'
  | 'government_inquiry'
  | 'action_required' // Returned for document fix
  | 'ready_for_issue'
  | 'completed'
  | 'rejected';

export type StepTurnOwner = 'citizen' | 'office' | 'government' | 'postal' | 'system';

export interface CaseTimelineStep {
  id: string;
  title: string;
  description: string;
  timestamp?: string;
  status: 'done' | 'current' | 'pending' | 'failed' | 'warning';
  icon: string;
  turnOwner: StepTurnOwner;
  turnOwnerLabel: string;
  durationActual?: string;
  durationTypical?: string;
  officeNote?: string;
}

export interface ReturnReasonDefinition {
  code: string;
  title: string;
  defaultMessage: string;
  sampleImg?: string;
}

export interface CaseRequest {
  id: string;
  trackingCode: string;
  serviceId: string;
  serviceTitle: string;
  serviceCategory: string;
  serviceTag: ServiceTag;
  assignedOffice?: PishkhanOffice;
  officeId?: string;
  status: CaseStatus;
  createdAt: string;
  updatedAt: string;
  lastChangeText?: string;
  deadlineCountdown?: string;
  expiresInDays?: number;
  currentStepNumber: number;
  totalSteps: number;
  turnOwner: StepTurnOwner;
  turnOwnerText: string;
  estimatedCompletion: string;
  citizenName: string;
  citizenNationalId: string;
  feePaid: number;
  officeShareFee?: number;
  timeline: CaseTimelineStep[];
  returnReason?: string;
  returnReasonCode?: string;
  requiredFixField?: string;
  isDelegated?: boolean;
  delegatedFromName?: string;
  uploadedDocuments: {
    name: string;
    type: string;
    code?: string;
    url?: string;
    verified: boolean;
    hasBlurWarning?: boolean;
  }[];
}

export interface DocumentItem {
  id: string;
  title: string;
  type: string;
  docNumber: string;
  issueDate: string;
  expiryDate?: string;
  isVerified: boolean;
  category: string;
  filePreviewUrl?: string;
  attributes: { label: string; value: string }[];
}

export interface LegalDelegation {
  id: string;
  principalName: string;
  principalNationalId: string;
  agentName: string;
  agentNationalId: string;
  relation: string;
  validUntil: string;
  allowedServices: string[];
  maxAmountTomans: number;
  status: 'active' | 'pending_otp' | 'revoked';
  documentNumber: string;
}

export interface CitizenProfile {
  fullName: string;
  nationalId: string;
  mobile: string;
  fatherName: string;
  birthDate: string;
  postalCode: string;
  address: string;
  tier: 'bronze' | 'silver' | 'gold';
  tierName: string;
  sanaVerified: boolean;
  digitalSignatureActive: boolean;
  walletBalance: number; // Tomans
  creditScore: number; // 0 - 1000
  documents: DocumentItem[];
  delegations: LegalDelegation[];
}

export interface Appointment {
  id: string;
  officeId: string;
  officeName: string;
  serviceTitle: string;
  date: string;
  timeSlot: string;
  trackingCode: string;
  status: 'active' | 'completed' | 'cancelled';
  reminderEnabled?: boolean;
  reminderType?: 'sms' | 'push' | 'all';
  reminderTime?: string;
  address?: string;
  requiredDocs?: string[];
  queueNumber?: string;
  counterNumber?: string;
}

export interface WalletTransaction {
  id: string;
  title: string;
  amount: number;
  type: 'deposit' | 'service_fee' | 'bill_pay' | 'cashback';
  date: string;
  trackingId: string;
  status: 'success' | 'pending';
}

export interface ChatMessage {
  id: string;
  caseId: string;
  sender: 'citizen' | 'office' | 'system';
  senderName: string;
  text: string;
  time: string;
  attachmentUrl?: string;
}

// ----------------------------------------------------
// CONSULTATION & EXPERT HUB TYPES (مشابه اسنپ دکتر پیشخوان)
// ----------------------------------------------------

export type ConsultationCategory = 
  | 'tax'               // امور مالیاتی و مودیان
  | 'insurance_labor'   // بیمه، بازنشستگی و اداره کار
  | 'legal_registry'    // حقوقی، ثبتی و اسناد
  | 'tenders_permits'   // مناقصات، سامانه ستاد و مجوزها
  | 'municipal'         // شهرداری، ماده ۱۰۰ و املاک
  | 'business_startup'; // شرکت‌ها و کسب‌وکارهای نوپا

export type ConsultationMode = 'text' | 'call' | 'case_review';

export interface ConsultationSpecialtyItem {
  id: string;
  category: ConsultationCategory;
  title: string;
  subtitle: string;
  iconName: string;
  badge?: string;
  popularTopics: string[];
}

export interface AdvisorRatingBreakdown {
  accuracy: number;   // دقت راهکار و اشراف به قوانین (از ۵)
  eloquence: number;  // فن بیان و شفافیت پاسخ
  patience: number;   // صبوری و پاسخگویی کامل
}

export interface ConsultationAdvisor {
  id: string;
  name: string;
  avatar: string;
  title: string;
  category: ConsultationCategory;
  categoryTitle: string;
  credentialsBadge: string;
  licenseNumber?: string;
  experienceYears: number;
  rating: number; // e.g. 4.9
  reviewCount: number;
  ratingBreakdown: AdvisorRatingBreakdown;
  specialties: string[];
  isOnline: boolean;
  isVerified: boolean;
  bio: string;
  consultationCount: number;
  pricing: {
    textChat: number;        // مشاوره متنی و بررسی اولیه (تومان)
    phonePerMinute: number;  // تماس صوتی/تصویری امن اینترنتی دقیقه‌ای (تومان)
    caseDeepReview: number;  // بررسی عمیق پرونده و تنظیم لایحه/استعلام (تومان)
  };
  linkedActionServices: {
    serviceId: string;
    title: string;
    description: string;
  }[];
  recentReviews?: {
    id: string;
    userName: string;
    rating: number;
    date: string;
    comment: string;
    consultationType: string;
  }[];
}

export interface ConsultationSession {
  id: string;
  advisorId: string;
  advisorName: string;
  advisorAvatar: string;
  advisorTitle: string;
  categoryTitle: string;
  mode: ConsultationMode;
  status: 'active' | 'completed' | 'scheduled' | 'cancelled';
  durationSeconds?: number;
  totalFee: number;
  createdAt: string;
  trackingCode: string;
  uploadedDocsCount?: number;
  advisorVerdict?: string;
  linkedServiceToExecute?: {
    serviceId: string;
    title: string;
  };
}

export interface BusinessSubscriptionPlan {
  id: string;
  title: string;
  badge?: string;
  isPopular?: boolean;
  priceMonthly: number;
  periodText: string;
  targetAudience: string;
  features: string[];
  quota: {
    monthlyTaxReview: string;
    laborDisputeDefense: string;
    fastPassSupport: string;
    phoneMinutes: number;
  };
}

export interface AdvisorRegistrationForm {
  fullName: string;
  mobile: string;
  nationalId: string;
  category: ConsultationCategory;
  title: string;
  experienceYears: number;
  licenseType: string;
  licenseNumber: string;
  bio: string;
  selectedSpecialties: string[];
  ratePerMinute: number;
  rateDeepReview: number;
  agreementAccepted: boolean;
}

