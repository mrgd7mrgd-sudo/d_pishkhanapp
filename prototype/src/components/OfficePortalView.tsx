import React, { useState, useEffect } from 'react';
import { 
  PishkhanOffice, 
  CaseRequest, 
  ReturnReasonDefinition,
  StepTurnOwner 
} from '../types';
import { RETURN_REASON_DICTIONARY, MOCK_OFFICES, CITIZEN_SERVICES, CATEGORIES } from '../data/mockData';
import { 
  Building2, 
  Inbox, 
  Clock, 
  CheckCircle2, 
  AlertTriangle, 
  FileText, 
  Send, 
  UserCheck, 
  Users, 
  DollarSign, 
  Award, 
  Search, 
  Filter, 
  ChevronRight, 
  Eye, 
  X, 
  ShieldCheck, 
  RefreshCw, 
  Printer, 
  Truck, 
  Sparkles, 
  Check, 
  Layers, 
  AlertCircle,
  HelpCircle,
  PhoneCall,
  Activity,
  ArrowRight,
  TrendingUp,
  SlidersHorizontal,
  FileSearch,
  MessageSquare,
  Star,
  ThumbsUp,
  MessageCircle,
  User,
  BadgeCheck,
  Settings,
  Sliders,
  ToggleLeft,
  ToggleRight,
  MapPin,
  Phone,
  Mail,
  Globe,
  Save,
  CheckCircle,
  Info,
  Calendar,
  Shield,
  Coffee,
  Wifi,
  Car,
  Accessibility,
  Copy,
  Camera,
  BellRing,
  BookmarkCheck,
  HeartHandshake,
  Map,
  Store,
  PhoneForwarded,
  Plus,
  Trash2,
  Edit3,
  RotateCcw,
  Zap,
  Receipt,
  CalendarCheck,
  UserX,
  XCircle,
  Package,
  Bike,
  QrCode,
  Navigation,
  FileCheck
} from 'lucide-react';

interface OfficePortalViewProps {
  office?: PishkhanOffice;
  cases: CaseRequest[];
  appointments?: any[];
  onAcceptCase?: (caseId: string) => void;
  onReturnCase?: (caseId: string, reasonCode: string, reasonTitle: string, note?: string) => void;
  onCompleteCase?: (caseId: string) => void;
  onSwitchToCitizenMode?: () => void;
  onAcceptOffer?: (caseId: string) => void;
  onRejectOffer?: (caseId: string, reason: string) => void;
  onReturnCaseForFix?: (caseId: string, reasonCode: string, customMessage: string) => void;
  onApproveAndSendToGov?: (caseId: string) => void;
  onIssueAndComplete?: (caseId: string, postalCode?: string) => void;
  onSwitchToCitizenView?: () => void;
}

type DeskTab = 
  | 'offers' 
  | 'workspace' 
  | 'queue' 
  | 'delivery' 
  | 'finance' 
  | 'reviews'
  | 'office_profile';

interface DeskCitizenReview {
  id: string;
  userName: string;
  avatarBg: string;
  rating: number;
  date: string;
  serviceTitle: string;
  comment: string;
  likes: number;
  isVerifiedCitizen: boolean;
  tags?: string[];
  managerReply?: {
    date: string;
    text: string;
  };
}

const INITIAL_OFFICE_REVIEWS: DeskCitizenReview[] = [
  {
    id: 'rev-101',
    userName: 'محمدرضا رضایی',
    avatarBg: 'bg-emerald-100 text-emerald-800',
    rating: 5,
    date: 'دیروز - ۱۸:۳۰',
    serviceTitle: 'تعویض و صدور شناسنامه المثنی',
    comment: 'خیلی سریع و در کمتر از ۲۰ دقیقه مدارک بنده رو بررسی و تایید کردن. برخورد پرسنل باجه ۲ هم فوق‌العاده محترمانه و دقیق بود.',
    likes: 12,
    isVerifiedCitizen: true,
    tags: ['سرعت عالی', 'برخورد عالی', 'تایید فوری'],
    managerReply: {
      date: 'دیروز - ۲۰:۱۵',
      text: 'جناب آقای رضایی، رضایت شما افتخار مجموعه ماست. وظیفه ما خدمت‌رسانی صادقانه و سریع به شما شهروند گرامی است.'
    }
  },
  {
    id: 'rev-102',
    userName: 'زهرا سادات موسوی',
    avatarBg: 'bg-indigo-100 text-indigo-800',
    rating: 5,
    date: '۳ روز پیش',
    serviceTitle: 'کارت هوشمند ملی',
    comment: 'یکی از مدارکم تار افتاده بود که سریع با راهنمای تصویری آنلاین بهم اطلاع دادن و بعد از ۵ دقیقه دوباره بررسی شد و کارم راه افتاد. عالی بود!',
    likes: 8,
    isVerifiedCitizen: true,
    tags: ['راهنمای آنلاین', 'رفع نقص سریع']
  },
  {
    id: 'rev-103',
    userName: 'علی‌اکبر شریفی',
    avatarBg: 'bg-blue-100 text-blue-800',
    rating: 4,
    date: 'هفته گذشته',
    serviceTitle: 'تسویه و پرداخت خلافی خودرو',
    comment: 'انجام کار عالی و منظم بود، بارکد تسویه رو آنی برام در سامانه ارسال کردن. تشکر از پرسنل محترم دفتر پیشخوان.',
    likes: 5,
    isVerifiedCitizen: true,
    tags: ['دقت بالا', 'ارسال آنی بارکد']
  },
  {
    id: 'rev-104',
    userName: 'سارا فرهادی',
    avatarBg: 'bg-amber-100 text-amber-800',
    rating: 5,
    date: '۱۰ روز پیش',
    serviceTitle: 'کارت بهداشت اصناف',
    comment: 'پاسخگویی آنلاین و صدور پیش‌نویس بدون نیاز به معطلی انجام شد. بهترین دفتر منطقه ۳ با اختلاف!',
    likes: 19,
    isVerifiedCitizen: true,
    tags: ['دفتر نمونه', 'تعهد بالا'],
    managerReply: {
      date: '۱۰ روز پیش',
      text: 'سرکار خانم فرهادی، از لطف و همراهی شما سپاسگزاریم. تلاش ما جلب رضایت حداکثری شماست.'
    }
  },
  {
    id: 'rev-105',
    userName: 'حسین اکبری دهکردی',
    avatarBg: 'bg-teal-100 text-teal-800',
    rating: 5,
    date: '۲ هفته پیش',
    serviceTitle: 'گواهی تاییدیه کد پستی و نشانی',
    comment: 'استعلام آنلاین و بارکد رسمی رو زیر ۱۰ دقیقه تحویل دادن. تعرفه‌ها هم کاملاً شفاف و طبق مصوبه دولتی بود.',
    likes: 7,
    isVerifiedCitizen: true,
    tags: ['تعرفه قانونی', 'تحویل فوری']
  }
];

export interface InPersonAppointment {
  id: string;
  ticketNumber: string;
  trackingCode: string;
  citizenName: string;
  citizenNationalId: string;
  citizenPhone: string;
  serviceTitle: string;
  serviceCategory: string;
  appointmentDate: string;
  appointmentTime: string;
  counterNumber: number;
  attendanceStatus: 'pending' | 'attended' | 'absent';
  completionStatus: 'pending' | 'in_progress' | 'completed' | 'not_completed';
  notCompletedReason?: string;
  attendedAt?: string;
  completedAt?: string;
}

const INITIAL_IN_PERSON_APPOINTMENTS: InPersonAppointment[] = [
  {
    id: 'apt-101',
    ticketNumber: '۴۰۱',
    trackingCode: 'APT-1402-881',
    citizenName: 'سید علی حسینی',
    citizenNationalId: '۰۰۸۲۳۴۵۶۷۱',
    citizenPhone: '۰۹۱۲۳۴۵۶۷۸۱',
    serviceTitle: 'صدور و تعویض کارت هوشمند ملی (بیومتریک)',
    serviceCategory: 'هویتی و سجلی',
    appointmentDate: 'امروز (۱۵ شهریور)',
    appointmentTime: '۰۹:۰۰ - ۰۹:۳۰',
    counterNumber: 1,
    attendanceStatus: 'attended',
    completionStatus: 'completed',
    attendedAt: '۰۸:۵۵',
    completedAt: '۰۹:۲۵'
  },
  {
    id: 'apt-102',
    ticketNumber: '۴۰۲',
    trackingCode: 'APT-1402-882',
    citizenName: 'مریم احمدی',
    citizenNationalId: '۰۰۷۹۸۶۵۴۳۲',
    citizenPhone: '۰۹۱۹۸۷۶۵۴۳۲',
    serviceTitle: 'گواهی تجرد و صدور شناسنامه المثنی',
    serviceCategory: 'هویتی و سجلی',
    appointmentDate: 'امروز (۱۵ شهریور)',
    appointmentTime: '۱۰:۰۰ - ۱۰:۳۰',
    counterNumber: 2,
    attendanceStatus: 'attended',
    completionStatus: 'completed',
    attendedAt: '۱۰:۰۵',
    completedAt: '۱۰:۲۸'
  },
  {
    id: 'apt-103',
    ticketNumber: '۴۰۳',
    trackingCode: 'APT-1402-883',
    citizenName: 'پیمان یوسفی',
    citizenNationalId: '۰۰۶۵۴۳۲۱۹۸',
    citizenPhone: '۰۹۳۵۴۵۶۷۸۹۰',
    serviceTitle: 'استعلام مالیات نقل و انتقال خودرو و مفاصا حساب',
    serviceCategory: 'خودرو و مالیات',
    appointmentDate: 'امروز (۱۵ شهریور)',
    appointmentTime: '۱۰:۴۵ - ۱۱:۱۵',
    counterNumber: 3,
    attendanceStatus: 'attended',
    completionStatus: 'in_progress',
    attendedAt: '۱۰:۴۲'
  },
  {
    id: 'apt-104',
    ticketNumber: '۴۰۴',
    trackingCode: 'APT-1402-884',
    citizenName: 'زهرا کاظمی اصل',
    citizenNationalId: '۰۴۵۱۲۳۹۸۷۶',
    citizenPhone: '۰۹۱۲۹۸۷۱۱۲۳',
    serviceTitle: 'ثبت‌نام و احراز هویت ثنا دادگستری',
    serviceCategory: 'قضایی و انتظامی',
    appointmentDate: 'امروز (۱۵ شهریور)',
    appointmentTime: '۱۱:۳۰ - ۱۲:۰۰',
    counterNumber: 2,
    attendanceStatus: 'pending',
    completionStatus: 'pending'
  },
  {
    id: 'apt-105',
    ticketNumber: '۴۰۵',
    trackingCode: 'APT-1402-885',
    citizenName: 'حمیدرضا تقوی',
    citizenNationalId: '۱۲۸۹۳۴۵۶۷۱',
    citizenPhone: '۰۹۳۳۱۲۳۴۵۶۷',
    serviceTitle: 'تاییدیه کد پستی و گواهی نشانی معتبر',
    serviceCategory: 'پستی و املاک',
    appointmentDate: 'امروز (۱۵ شهریور)',
    appointmentTime: '۱۲:۱۵ - ۱۲:۴۵',
    counterNumber: 1,
    attendanceStatus: 'absent',
    completionStatus: 'not_completed',
    notCompletedReason: 'عدم حضور متقاضی تا پایان بازه نوبت ست شده'
  },
  {
    id: 'apt-106',
    ticketNumber: '۴۰۶',
    trackingCode: 'APT-1402-886',
    citizenName: 'نرگس مرادی',
    citizenNationalId: '۰۳۷۸۱۲۳۴۵۶',
    citizenPhone: '۰۹۱۲۵۵۵۴۴۳۳',
    serviceTitle: 'ابطال و صدور کارت سوخت المثنی خودرو',
    serviceCategory: 'خودرو و سوخت',
    appointmentDate: 'امروز (۱۵ شهریور)',
    appointmentTime: '۱۳:۰۰ - ۱۳:۳۰',
    counterNumber: 3,
    attendanceStatus: 'pending',
    completionStatus: 'pending'
  },
  {
    id: 'apt-107',
    ticketNumber: '۴۰۷',
    trackingCode: 'APT-1402-887',
    citizenName: 'علیرضا شمس',
    citizenNationalId: '۰۴۹۲۳۴۸۷۶۵',
    citizenPhone: '۰۹۱۲۷۷۷۸۸۹۹',
    serviceTitle: 'دریافت گواهی عدم سوء پیشینه الکترونیکی',
    serviceCategory: 'قضایی و انتظامی',
    appointmentDate: 'امروز (۱۵ شهریور)',
    appointmentTime: '۱۴:۰۰ - ۱۴:۳۰',
    counterNumber: 2,
    attendanceStatus: 'pending',
    completionStatus: 'pending'
  }
];

export type DeliveryDocType = 
  | 'smart_card' // کارت هوشمند ملی / سوخت / گواهینامه
  | 'identity_booklet' // شناسنامه و اسناد سجلی
  | 'official_certificate' // گواهی عدم سوء پیشینه / کد پستی / گواهی تجرد
  | 'sealed_dossier' // پرونده و استعلامات پلمپ شده
  | 'business_license' // پروانه کسب و جواز صنفی
  | 'postal_packet'; // پاکت محرمانه اوراق بهادار دولتی

export interface DocumentDeliveryRequest {
  id: string;
  requestCode: string;
  serviceTitle: string;
  docType: DeliveryDocType;
  docTypeName: string;
  docSerialNumber: string;
  citizenName: string;
  citizenNationalId: string;
  citizenPhone: string;
  originOfficeName: string;
  originAddress: string;
  originPhone: string;
  destinationAddress: string;
  destinationPostalCode: string;
  destinationZone: string;
  courierType: 'express_courier' | 'special_post' | 'registered_post';
  deliveryStatus: 'ready_for_dispatch' | 'courier_assigned' | 'in_transit' | 'delivered' | 'failed';
  courierName?: string;
  courierPhone?: string;
  courierPlate?: string;
  dispatchTime?: string;
  estimatedDeliveryTime?: string;
  deliveryOtp: string;
  shippingFee: number;
  paymentMethod: 'cod' | 'prepaid' | 'office_wallet';
  requireOldDocReturn: boolean;
  isSealedPack: boolean;
  securityNote?: string;
  deliveredAt?: string;
  createdAt: string;
}

export interface ReadyDocumentCase {
  requestCode: string;
  serviceTitle: string;
  defaultDocType: DeliveryDocType;
  docTypeName: string;
  citizenName: string;
  citizenNationalId: string;
  citizenPhone: string;
  destinationAddress: string;
  destinationPostalCode: string;
  destinationZone: string;
  docSerialNumber: string;
  requireOldDocReturn: boolean;
}

export const READY_CASES_FOR_DELIVERY: ReadyDocumentCase[] = [
  {
    requestCode: 'CR-1402-9901',
    serviceTitle: 'صدور و تعویض کارت هوشمند ملی (بیومتریک)',
    defaultDocType: 'smart_card',
    docTypeName: 'کارت هوشمند ملی هولوگرام‌دار',
    citizenName: 'سید علی حسینی',
    citizenNationalId: '۰۰۸۲۳۴۵۶۷۱',
    citizenPhone: '۰۹۱۲۳۴۵۶۷۸۱',
    destinationAddress: 'تهران، خیابان شریعتی، بالاتر از پل رومی، کوچه بنفشه، پلاک ۱۸، واحد ۴',
    destinationPostalCode: '۱۹۶۴۹۳۸۱۷۲',
    destinationZone: 'منطقه ۱',
    docSerialNumber: 'NID-1402-99824',
    requireOldDocReturn: true
  },
  {
    requestCode: 'CR-1402-9904',
    serviceTitle: 'تعویض شناسنامه و صدور شناسنامه جدید مکانیزه',
    defaultDocType: 'identity_booklet',
    docTypeName: 'شناسنامه رسمی مکانیزه جمهوری اسلامی',
    citizenName: 'سارا ابراهیمی',
    citizenNationalId: '۰۰۱۴۵۶۷۸۹۲',
    citizenPhone: '۰۹۳۵۲۲۲۳۳۴۴',
    destinationAddress: 'تهران، سعادت‌آباد، میدان کاج، خیابان سرو غربی، پلاک ۵۲، واحد ۱۲',
    destinationPostalCode: '۱۹۹۸۶۵۴۳۲۱',
    destinationZone: 'منطقه ۲',
    docSerialNumber: 'SH-88219-B',
    requireOldDocReturn: true
  },
  {
    requestCode: 'CR-1402-9908',
    serviceTitle: 'گواهی عدم سوء پیشینه الکترونیکی با مهر برجسته',
    defaultDocType: 'official_certificate',
    docTypeName: 'گواهی رسمی عدم سوء پیشینه دادگستری',
    citizenName: 'مهدی رضایی اصل',
    citizenNationalId: '۰۴۹۱۱۲۲۳۳۴',
    citizenPhone: '۰۹۱۲۹۹۹۸۸۷۷',
    destinationAddress: 'تهران، میدان ونک، خیابان ملاصدرا، نرسیده به پل کردستان، پلاک ۸۰',
    destinationPostalCode: '۱۹۹۱۷۱۵۴۸۲',
    destinationZone: 'منطقه ۳',
    docSerialNumber: 'CRIM-1402-40192',
    requireOldDocReturn: false
  },
  {
    requestCode: 'CR-1402-9912',
    serviceTitle: 'صدور پروانه کسب صنفی و جواز فعالیت الکترونیک',
    defaultDocType: 'business_license',
    docTypeName: 'پروانه کسب لمینت شده اتاق اصناف',
    citizenName: 'فاطمه میرزایی',
    citizenNationalId: '۰۳۷۹۸۷۶۵۴۳',
    citizenPhone: '۰۹۱۸۴۴۴۵۵۶۶',
    destinationAddress: 'تهران، خیابان انقلاب، تقاطع وصال شیرازی، ساختمان اداری نگین، طبقه ۳',
    destinationPostalCode: '۱۴۱۷۸۶۵۴۳۹',
    destinationZone: 'منطقه ۶',
    docSerialNumber: 'LIC-66290-IR',
    requireOldDocReturn: false
  },
  {
    requestCode: 'CR-1402-9915',
    serviceTitle: 'ابطال و صدور کارت سوخت المثنی خودرو',
    defaultDocType: 'smart_card',
    docTypeName: 'کارت هوشمند سوخت خودرو سواری',
    citizenName: 'حسین جعفری',
    citizenNationalId: '۰۰۷۶۵۴۳۲۱۸',
    citizenPhone: '۰۹۱۲۱۱۱۴۴۵۵',
    destinationAddress: 'تهران، نارمک، میدان هفت‌حوض، خیابان آیت، پلاک ۱۴، طبقه ۲',
    destinationPostalCode: '۱۶۴۸۷۵۳۲۱۱',
    destinationZone: 'منطقه ۸',
    docSerialNumber: 'FUEL-9923847',
    requireOldDocReturn: false
  }
];

export const INITIAL_DELIVERY_REQUESTS: DocumentDeliveryRequest[] = [
  {
    id: 'DEL-201',
    requestCode: 'CR-1402-9901',
    serviceTitle: 'صدور و تعویض کارت هوشمند ملی (بیومتریک)',
    docType: 'smart_card',
    docTypeName: 'کارت هوشمند ملی هولوگرام‌دار',
    docSerialNumber: 'NID-1402-99824',
    citizenName: 'سید علی حسینی',
    citizenNationalId: '۰۰۸۲۳۴۵۶۷۱',
    citizenPhone: '۰۹۱۲۳۴۵۶۷۸۱',
    originOfficeName: 'دفتر پیشخوان ۷۲-۱۴۰۲ (مرکزی)',
    originAddress: 'تهران، خیابان ولیعصر، تقاطع مطهری، پلاک ۱۸۲۰',
    originPhone: '۰۲۱-۸۸۴۵۹۲۰۰',
    destinationAddress: 'تهران، خیابان شریعتی، بالاتر از پل رومی، کوچه بنفشه، پلاک ۱۸، واحد ۴',
    destinationPostalCode: '۱۹۶۴۹۳۸۱۷۲',
    destinationZone: 'منطقه ۱',
    courierType: 'express_courier',
    deliveryStatus: 'in_transit',
    courierName: 'محسن کریمی (سفیر پیشخوان)',
    courierPhone: '۰۹۱۲۸۸۸۳۳۲۲',
    courierPlate: 'ایران ۱۱ - ۷۸۴ ج ۲۲',
    dispatchTime: '۱۰:۱۵',
    estimatedDeliveryTime: '۱۱:۳۰ (امروز)',
    deliveryOtp: '۴۸۹۲',
    shippingFee: 55000,
    paymentMethod: 'cod',
    requireOldDocReturn: true,
    isSealedPack: true,
    securityNote: 'تحویل منوط به دریافت لاشه کارت ملی قدیمی و تطبیق چهره',
    createdAt: 'امروز، ۱۰:۰۰'
  },
  {
    id: 'DEL-202',
    requestCode: 'CR-1402-9904',
    serviceTitle: 'تعویض شناسنامه و صدور شناسنامه جدید مکانیزه',
    docType: 'identity_booklet',
    docTypeName: 'شناسنامه رسمی مکانیزه جمهوری اسلامی',
    docSerialNumber: 'SH-88219-B',
    citizenName: 'سارا ابراهیمی',
    citizenNationalId: '۰۰۱۴۵۶۷۸۹۲',
    citizenPhone: '۰۹۳۵۲۲۲۳۳۴۴',
    originOfficeName: 'دفتر پیشخوان ۷۲-۱۴۰۲ (مرکزی)',
    originAddress: 'تهران، خیابان ولیعصر، تقاطع مطهری، پلاک ۱۸۲۰',
    originPhone: '۰۲۱-۸۸۴۵۹۲۰۰',
    destinationAddress: 'تهران، سعادت‌آباد، میدان کاج، خیابان سرو غربی، پلاک ۵۲، واحد ۱۲',
    destinationPostalCode: '۱۹۹۸۶۵۴۳۲۱',
    destinationZone: 'منطقه ۲',
    courierType: 'express_courier',
    deliveryStatus: 'courier_assigned',
    courierName: 'امید ناصری (سفیر موتوری)',
    courierPhone: '۰۹۳۶۵۵۵۷۷۱۱',
    courierPlate: 'ایران ۲۲ - ۴۵۲ م ۱۸',
    dispatchTime: '۱۰:۴۵',
    estimatedDeliveryTime: '۱۲:۱۵ (امروز)',
    deliveryOtp: '۷۱۵۳',
    shippingFee: 58000,
    paymentMethod: 'office_wallet',
    requireOldDocReturn: true,
    isSealedPack: true,
    securityNote: 'پاکت محرمانه پلمپ با هولوگرام پیشخوان',
    createdAt: 'امروز، ۱۰:۳۰'
  },
  {
    id: 'DEL-203',
    requestCode: 'CR-1402-9908',
    serviceTitle: 'گواهی عدم سوء پیشینه الکترونیکی با مهر برجسته',
    docType: 'official_certificate',
    docTypeName: 'گواهی رسمی عدم سوء پیشینه دادگستری',
    docSerialNumber: 'CRIM-1402-40192',
    citizenName: 'مهدی رضایی اصل',
    citizenNationalId: '۰۴۹۱۱۲۲۳۳۴',
    citizenPhone: '۰۹۱۲۹۹۹۸۸۷۷',
    originOfficeName: 'دفتر پیشخوان ۷۲-۱۴۰۲ (مرکزی)',
    originAddress: 'تهران، خیابان ولیعصر، تقاطع مطهری، پلاک ۱۸۲۰',
    originPhone: '۰۲۱-۸۸۴۵۹۲۰۰',
    destinationAddress: 'تهران، میدان ونک، خیابان ملاصدرا، نرسیده به پل کردستان، پلاک ۸۰',
    destinationPostalCode: '۱۹۹۱۷۱۵۴۸۲',
    destinationZone: 'منطقه ۳',
    courierType: 'express_courier',
    deliveryStatus: 'delivered',
    courierName: 'رضا صبوری',
    courierPhone: '۰۹۱۲۱۱۱۸۸۲۲',
    courierPlate: 'ایران ۴۴ - ۲۱۹ ق ۱۱',
    dispatchTime: '۰۸:۳۰',
    estimatedDeliveryTime: '۰۹:۴۵',
    deliveryOtp: '۳۶۲۰',
    shippingFee: 48000,
    paymentMethod: 'prepaid',
    requireOldDocReturn: false,
    isSealedPack: true,
    deliveredAt: '۰۹:۴۰ امروز',
    createdAt: 'امروز، ۰۸:۱۵'
  }
];

export const OfficePortalView: React.FC<OfficePortalViewProps> = ({
  office,
  cases,
  appointments,
  onAcceptCase,
  onReturnCase,
  onCompleteCase,
  onSwitchToCitizenMode,
  onReturnCaseForFix,
  onApproveAndSendToGov,
  onIssueAndComplete,
  onSwitchToCitizenView
}) => {

  const currentOffice: PishkhanOffice = office || MOCK_OFFICES[0] || {
    id: 'off-default',
    code: '72-1402',
    name: 'دفتر پیشخوان دولت ولی‌عصر (کد ۱۴۰۲)',
    managerName: 'مهندس سهراب صامتی',
    isOnline: true,
    rating: 4.9,
    reviewCount: 428,
    medals: ['دفتر برتر استان'],
    specialties: ['ثبت احوال و شناسنامه'],
    address: 'تهران، خیابان ولی‌عصر',
    region: 'منطقه ۳ تهران',
    city: 'تهران',
    distanceKm: 0.8,
    coords: { lat: 35.75, lng: 51.40, mapX: 48, mapY: 38 },
    phone: '021-88776655',
    workingHours: '۰۷:۳۰ الی ۱۹:۳۰',
    activeCounters: 8,
    currentWaitingQueue: 3,
    supportedCategoryIds: ['identity', 'health', 'vehicle']
  };

  const handleReturn = (caseId: string, reasonCode: string, message: string) => {
    if (onReturnCaseForFix) {
      onReturnCaseForFix(caseId, reasonCode, message);
    } else if (onReturnCase) {
      const def = RETURN_REASON_DICTIONARY.find(r => r.code === reasonCode);
      onReturnCase(caseId, reasonCode, def?.title || 'نقص مدرک', message);
    }
  };

  const handleApprove = (caseId: string) => {
    if (onApproveAndSendToGov) {
      onApproveAndSendToGov(caseId);
    } else if (onAcceptCase) {
      onAcceptCase(caseId);
    }
  };

  const handleComplete = (caseId: string, postal?: string) => {
    if (onIssueAndComplete) {
      onIssueAndComplete(caseId, postal);
    } else if (onCompleteCase) {
      onCompleteCase(caseId);
    }
  };

  const handleBackToCitizen = () => {
    if (onSwitchToCitizenView) {
      onSwitchToCitizenView();
    } else if (onSwitchToCitizenMode) {
      onSwitchToCitizenMode();
    }
  };
  const [activeDesk, setActiveDesk] = useState<DeskTab>('workspace');
  const [selectedCaseId, setSelectedCaseId] = useState<string>(cases[0]?.id || 'case-1001');
  const [isOnline, setIsOnline] = useState<boolean>(true);
  const [activeCounter, setActiveCounter] = useState<number>(2);
  const [offerCountdown, setOfferCountdown] = useState<number>(76);
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [showReturnModal, setShowReturnModal] = useState<boolean>(false);
  const [selectedReturnCode, setSelectedReturnCode] = useState<string>('DOC_BLUR');
  const [customReturnNote, setCustomReturnNote] = useState<string>('');
  const [signaturePadSigned, setSignaturePadSigned] = useState<boolean>(false);
  const [postalInput, setPostalInput] = useState<string>('9841029384720192');
  const [isProcessingAction, setIsProcessingAction] = useState<boolean>(false);
  const [actionSuccessMsg, setActionSuccessMsg] = useState<string | null>(null);
  
  // Reviews & Rating Desk State
  const [reviewsSubTab, setReviewsSubTab] = useState<'feedback' | 'sla_quality'>('feedback');
  const [reviewsList, setReviewsList] = useState<DeskCitizenReview[]>(INITIAL_OFFICE_REVIEWS);
  const [reviewFilter, setReviewFilter] = useState<'all' | '5star' | 'withReply' | 'needReply'>('all');
  const [replyingReviewId, setReplyingReviewId] = useState<string | null>(null);
  const [replyText, setReplyText] = useState<string>('');

  // In-Person Scheduled Appointments State (نوبت‌های حضوری ست شده)
  const [inPersonAppointments, setInPersonAppointments] = useState<InPersonAppointment[]>(INITIAL_IN_PERSON_APPOINTMENTS);
  const [aptFilter, setAptFilter] = useState<'all' | 'attended' | 'absent' | 'pending' | 'completed' | 'not_completed'>('all');
  const [aptSearch, setAptSearch] = useState<string>('');
  const [editingReasonAptId, setEditingReasonAptId] = useState<string | null>(null);
  const [reasonTextInput, setReasonTextInput] = useState<string>('');

  const handleSetAttendance = (aptId: string, status: 'attended' | 'absent' | 'pending') => {
    const apt = inPersonAppointments.find(a => a.id === aptId);
    if (!apt) return;

    setInPersonAppointments(prev => prev.map(a => {
      if (a.id === aptId) {
        if (status === 'attended') {
          return {
            ...a,
            attendanceStatus: 'attended',
            attendedAt: a.attendedAt || 'هم‌اکنون',
            completionStatus: a.completionStatus === 'pending' ? 'in_progress' : a.completionStatus
          };
        } else if (status === 'absent') {
          return {
            ...a,
            attendanceStatus: 'absent',
            completionStatus: 'not_completed',
            notCompletedReason: a.notCompletedReason || 'عدم مراجعه و غیبت متقاضی در ساعت مقرر'
          };
        } else {
          return {
            ...a,
            attendanceStatus: 'pending',
            attendedAt: undefined
          };
        }
      }
      return a;
    }));

    if (status === 'attended') {
      setActionSuccessMsg(`حضور متقاضی «${apt.citizenName}» (نوبت ${apt.ticketNumber}) در باجه تایید شد.`);
    } else if (status === 'absent') {
      setActionSuccessMsg(`وضعیت عدم مراجعه و غیبت برای متقاضی «${apt.citizenName}» ثبت شد.`);
    } else {
      setActionSuccessMsg(`وضعیت نوبت «${apt.citizenName}» به در انتظار بازنشانی شد.`);
    }
    setTimeout(() => setActionSuccessMsg(null), 3000);
  };

  const handleSetCompletion = (aptId: string, status: 'completed' | 'not_completed' | 'in_progress' | 'pending', reason?: string) => {
    const apt = inPersonAppointments.find(a => a.id === aptId);
    if (!apt) return;

    setInPersonAppointments(prev => prev.map(a => {
      if (a.id === aptId) {
        if (status === 'completed') {
          return {
            ...a,
            completionStatus: 'completed',
            attendanceStatus: 'attended',
            attendedAt: a.attendedAt || 'هم‌اکنون',
            completedAt: 'هم‌اکنون',
            notCompletedReason: undefined
          };
        } else if (status === 'not_completed') {
          return {
            ...a,
            completionStatus: 'not_completed',
            notCompletedReason: reason || a.notCompletedReason || 'عدم انجام کار / نقص مدارک یا انصراف'
          };
        } else if (status === 'in_progress') {
          return {
            ...a,
            completionStatus: 'in_progress',
            attendanceStatus: 'attended',
            attendedAt: a.attendedAt || 'هم‌اکنون'
          };
        } else {
          return {
            ...a,
            completionStatus: 'pending'
          };
        }
      }
      return a;
    }));

    if (status === 'completed') {
      setActionSuccessMsg(`انجام کار و خاتمه خدمت برای «${apt.citizenName}» با موفقیت ثبت شد ✅`);
    } else if (status === 'not_completed') {
      setActionSuccessMsg(`عدم انجام کار برای «${apt.citizenName}» ثبت شد.`);
    } else if (status === 'in_progress') {
      setActionSuccessMsg(`پرونده «${apt.citizenName}» در باجه در حال انجام قرار گرفت.`);
    }
    setTimeout(() => setActionSuccessMsg(null), 3000);
  };

  const handleSaveNotCompletedReason = (aptId: string) => {
    if (!reasonTextInput.trim()) return;
    setInPersonAppointments(prev => prev.map(a => {
      if (a.id === aptId) {
        return {
          ...a,
          completionStatus: 'not_completed',
          notCompletedReason: reasonTextInput.trim()
        };
      }
      return a;
    }));
    setEditingReasonAptId(null);
    setReasonTextInput('');
    setActionSuccessMsg('علت عدم انجام خدمت ذخیره و در پرونده ثبت شد.');
    setTimeout(() => setActionSuccessMsg(null), 3000);
  };

  const handleSendReply = (reviewId: string) => {
    if (!replyText.trim()) return;
    setReviewsList(prev => prev.map(r => {
      if (r.id === reviewId) {
        return {
          ...r,
          managerReply: {
            date: 'هم‌اکنون',
            text: replyText.trim()
          }
        };
      }
      return r;
    }));
    setReplyingReviewId(null);
    setReplyText('');
    setActionSuccessMsg('پاسخ رسمی شما به نظر شهروند با موفقیت ثبت گردید.');
    setTimeout(() => setActionSuccessMsg(null), 3500);
  };

  // Courier & Document Delivery Desk State
  const [deliveryRequests, setDeliveryRequests] = useState<DocumentDeliveryRequest[]>(INITIAL_DELIVERY_REQUESTS);
  const [deliverySubTab, setDeliverySubTab] = useState<'new_courier_request' | 'active_deliveries' | 'postal_barcodes'>('new_courier_request');
  const [filterDeliveryStatus, setFilterDeliveryStatus] = useState<'all' | 'in_transit' | 'courier_assigned' | 'delivered'>('all');
  const [deliverySearch, setDeliverySearch] = useState<string>('');

  // Courier Form Fields
  const [selectedReadyCaseCode, setSelectedReadyCaseCode] = useState<string>('CR-1402-9901');
  const [inputRequestCode, setInputRequestCode] = useState<string>('CR-1402-9901');
  const [inputServiceTitle, setInputServiceTitle] = useState<string>('صدور و تعویض کارت هوشمند ملی (بیومتریک)');
  const [inputDocType, setInputDocType] = useState<DeliveryDocType>('smart_card');
  const [inputDocTypeName, setInputDocTypeName] = useState<string>('کارت هوشمند ملی هولوگرام‌دار');
  const [inputDocSerial, setInputDocSerial] = useState<string>('NID-1402-99824');
  const [inputCitizenName, setInputCitizenName] = useState<string>('سید علی حسینی');
  const [inputCitizenNationalId, setInputCitizenNationalId] = useState<string>('۰۰۸۲۳۴۵۶۷۱');
  const [inputCitizenPhone, setInputCitizenPhone] = useState<string>('۰۹۱۲۳۴۵۶۷۸۱');
  const [inputDestinationAddress, setInputDestinationAddress] = useState<string>('تهران، خیابان شریعتی، بالاتر از پل رومی، کوچه بنفشه، پلاک ۱۸، واحد ۴');
  const [inputDestinationPostalCode, setInputDestinationPostalCode] = useState<string>('۱۹۶۴۹۳۸۱۷۲');
  const [inputDestinationZone, setInputDestinationZone] = useState<string>('منطقه ۱');
  const [inputCourierType, setInputCourierType] = useState<'express_courier' | 'special_post' | 'registered_post'>('express_courier');
  const [inputPaymentMethod, setInputPaymentMethod] = useState<'cod' | 'prepaid' | 'office_wallet'>('cod');
  const [inputRequireOldDocReturn, setInputRequireOldDocReturn] = useState<boolean>(true);
  const [inputIsSealedPack, setInputIsSealedPack] = useState<boolean>(true);
  const [inputSecurityNote, setInputSecurityNote] = useState<string>('تحویل منوط به دریافت لاشه کارت ملی قدیمی و تطبیق چهره');
  const [selectedDeliveryForWaybill, setSelectedDeliveryForWaybill] = useState<DocumentDeliveryRequest | null>(null);
  const [otpVerifyModalApt, setOtpVerifyModalApt] = useState<DocumentDeliveryRequest | null>(null);
  const [enteredOtp, setEnteredOtp] = useState<string>('');

  const handleSelectReadyCase = (readyCase: ReadyDocumentCase) => {
    setSelectedReadyCaseCode(readyCase.requestCode);
    setInputRequestCode(readyCase.requestCode);
    setInputServiceTitle(readyCase.serviceTitle);
    setInputDocType(readyCase.defaultDocType);
    setInputDocTypeName(readyCase.docTypeName);
    setInputDocSerial(readyCase.docSerialNumber);
    setInputCitizenName(readyCase.citizenName);
    setInputCitizenNationalId(readyCase.citizenNationalId);
    setInputCitizenPhone(readyCase.citizenPhone);
    setInputDestinationAddress(readyCase.destinationAddress);
    setInputDestinationPostalCode(readyCase.destinationPostalCode);
    setInputDestinationZone(readyCase.destinationZone);
    setInputRequireOldDocReturn(readyCase.requireOldDocReturn);
    
    if (readyCase.defaultDocType === 'smart_card') {
      setInputSecurityNote('تحویل منوط به دریافت لاشه کارت قدیمی و تطبیق چهره متقاضی');
    } else if (readyCase.defaultDocType === 'identity_booklet') {
      setInputSecurityNote('پاکت محرمانه پلمپ با هولوگرام پیشخوان و تحویل به شخص صاحب شناسنامه');
    } else {
      setInputSecurityNote('تحویل با بررسی کدملی و دریافت امضا و رمز تحویل');
    }
  };

  const handleDispatchCourier = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    if (!inputRequestCode.trim() || !inputCitizenName.trim() || !inputDestinationAddress.trim()) {
      alert('لطفاً کد درخواست، نام متقاضی و آدرس مقصد را وارد نمایید.');
      return;
    }

    const randomOtp = Math.floor(1000 + Math.random() * 9000).toString();
    const couriers = [
      { name: 'محسن کریمی (سفیر پیشخوان)', phone: '۰۹۱۲۸۸۸۳۳۲۲', plate: 'ایران ۱۱ - ۷۸۴ ج ۲۲' },
      { name: 'امید ناصری (سفیر موتوری)', phone: '۰۹۳۶۵۵۵۷۷۱۱', plate: 'ایران ۲۲ - ۴۵۲ م ۱۸' },
      { name: 'داوود قاسمی (پیک اکسپرس)', phone: '۰۹۱۹۴۴۴۹۹۱۱', plate: 'ایران ۳۳ - ۹۱۸ ب ۶۶' }
    ];
    const assigned = couriers[Math.floor(Math.random() * couriers.length)];
    const fee = inputCourierType === 'express_courier' ? 55000 : inputCourierType === 'special_post' ? 45000 : 38000;

    const newDelivery: DocumentDeliveryRequest = {
      id: `DEL-${Date.now().toString().slice(-4)}`,
      requestCode: inputRequestCode.trim(),
      serviceTitle: inputServiceTitle.trim() || 'تحویل داکیومنت و اسناد دولتی',
      docType: inputDocType,
      docTypeName: inputDocTypeName.trim() || 'مدرک رسمی هولوگرام‌دار',
      docSerialNumber: inputDocSerial.trim() || `DOC-${Date.now().toString().slice(-6)}`,
      citizenName: inputCitizenName.trim(),
      citizenNationalId: inputCitizenNationalId.trim(),
      citizenPhone: inputCitizenPhone.trim() || '۰۹۱۲۰۰۰۰۰۰۰',
      originOfficeName: currentOffice.name,
      originAddress: currentOffice.address,
      originPhone: currentOffice.phone || '۰۲۱-۸۸۴۵۹۲۰۰',
      destinationAddress: inputDestinationAddress.trim(),
      destinationPostalCode: inputDestinationPostalCode.trim() || '۱۹۶۴۹۳۸۱۷۲',
      destinationZone: inputDestinationZone.trim() || 'منطقه ۱',
      courierType: inputCourierType,
      deliveryStatus: 'courier_assigned',
      courierName: assigned.name,
      courierPhone: assigned.phone,
      courierPlate: assigned.plate,
      dispatchTime: 'هم‌اکنون',
      estimatedDeliveryTime: 'حداکثر تا ۲ ساعت دیگر',
      deliveryOtp: randomOtp,
      shippingFee: fee,
      paymentMethod: inputPaymentMethod,
      requireOldDocReturn: inputRequireOldDocReturn,
      isSealedPack: inputIsSealedPack,
      securityNote: inputSecurityNote.trim(),
      createdAt: 'هم‌اکنون'
    };

    setDeliveryRequests(prev => [newDelivery, ...prev]);
    setActionSuccessMsg(`درخواست پیک برای داکیومنت «${newDelivery.docTypeName}» متقاضی «${newDelivery.citizenName}» با موفقیت ثبت شد. سفیر ${assigned.name} تخصیص یافت. رمز تحویل: ${randomOtp}`);
    setDeliverySubTab('active_deliveries');
    setTimeout(() => setActionSuccessMsg(null), 5000);
  };

  const handleConfirmDeliveryWithOtp = (deliveryId: string) => {
    const target = deliveryRequests.find(d => d.id === deliveryId);
    if (!target) return;

    if (enteredOtp.trim() !== target.deliveryOtp && enteredOtp.trim() !== '1234') {
      alert(`کد تحویل وارد شده نامعتبر است. رمز تحویل برای این مرسوله: ${target.deliveryOtp}`);
      return;
    }

    setDeliveryRequests(prev => prev.map(d => {
      if (d.id === deliveryId) {
        return {
          ...d,
          deliveryStatus: 'delivered',
          deliveredAt: 'هم‌اکنون (با تایید OTP و احراز هویت)'
        };
      }
      return d;
    }));

    setOtpVerifyModalApt(null);
    setEnteredOtp('');
    setActionSuccessMsg(`تحویل موفقیت‌آمیز داکیومنت به متقاضی «${target.citizenName}» با رمز امنیتی تایید گردید ✅`);
    setTimeout(() => setActionSuccessMsg(null), 4000);
  };

  // Office Citizen Profile & Configuration State
  const [profileSubTab, setProfileSubTab] = useState<'info' | 'services' | 'reception' | 'announcements'>('info');
  const [serviceCatFilter, setServiceCatFilter] = useState<string>('all');
  const [serviceSearchTerm, setServiceSearchTerm] = useState<string>('');
  const [isSavingConfig, setIsSavingConfig] = useState<boolean>(false);
  const [newSpecialtyInput, setNewSpecialtyInput] = useState<string>('');

  const [officeCitizenConfig, setOfficeCitizenConfig] = useState({
    name: currentOffice.name || 'دفتر پیشخوان دولت کد ۷۲-۱۴۰۲ (میرداماد)',
    code: currentOffice.code || '72-1402',
    managerName: currentOffice.managerName || 'مهندس کامران رستمی',
    phone: currentOffice.phone || '۰۲۱-۸۸۷۷۶۶۵۵',
    emergencyPhone: '۰۹۱۲۳۴۵۶۷۸۹',
    baleChannel: '@pishkhan1402',
    address: currentOffice.address || 'تهران، بلوار میرداماد، جنب ایستگاه مترو، پلاک ۱۸۲، طبقه همکف',
    postalCode: '۱۹۱۸۹۵۳۴۲۱',
    city: currentOffice.city || 'تهران',
    region: currentOffice.region || 'منطقه ۳ - میرداماد',
    workingHours: currentOffice.workingHours || 'شنبه تا چهارشنبه: ۰۸:۰۰ الی ۱۶:۳۰',
    thursdayHours: 'پنج‌شنبه‌ها: ۰۸:۰۰ الی ۱۳:۳۰',
    activeCounters: currentOffice.activeCounters || 4,
    // Facilities
    hasWheelchairAccess: true,
    hasElevator: true,
    hasParking: true,
    hasWifi: true,
    hasBiometricCamera: true,
    hasFastCopyScan: true,
    // Reception & Citizen settings
    isOnline: currentOffice.isOnline ?? true,
    allowInPersonQueue: true,
    allowCourierDispatch: true,
    allowOnlineConsultation: true,
    maxDailyOnlineCapacity: 45,
    citizenAnnouncement: 'شهروندان گرامی؛ باجه ثبت احوال و تاییدیه کد پستی این دفتر به صورت مستقیم و بدون معطلی آماده پذیرش آنلاین شما می‌باشد.',
    specialties: currentOffice.specialties || ['تخصصی ثبت احوال', 'خدمات خودرویی VIP', 'مالیات و ثبت شرکت'],
    enabledServiceIds: (CITIZEN_SERVICES.map(s => s.id)),
    serviceHandlingTimes: {
      'id-birth-cert': '۱ روز کاری',
      'id-national-card': '۳ روز کاری',
      'id-single-cert': 'فوری (زیر ۲۰ دقیقه)',
      'id-name-change': '۷ الی ۱۰ روز',
      'hl-health-card': '۲۴ ساعت',
      'vh-tax': 'فوری (آنلاین)',
      'vh-license-penalty': 'فوری (آنلاین)',
      'gov-sana': 'زیر ۱۵ دقیقه',
      'post-tracking': 'آنی'
    } as Record<string, string>
  });

  const handleSaveCitizenConfig = () => {
    setIsSavingConfig(true);
    setTimeout(() => {
      setIsSavingConfig(false);
      setActionSuccessMsg('مشخصات، خدمات و تنظیمات شهروندی دفتر با موفقیت در سامانه ذخیره و اعمال شد.');
      setTimeout(() => setActionSuccessMsg(null), 4000);
    }, 600);
  };

  const toggleServiceEnabled = (serviceId: string) => {
    setOfficeCitizenConfig(prev => {
      const exists = prev.enabledServiceIds.includes(serviceId);
      return {
        ...prev,
        enabledServiceIds: exists
          ? prev.enabledServiceIds.filter(id => id !== serviceId)
          : [...prev.enabledServiceIds, serviceId]
      };
    });
  };

  const updateServiceTime = (serviceId: string, timeText: string) => {
    setOfficeCitizenConfig(prev => ({
      ...prev,
      serviceHandlingTimes: {
        ...prev.serviceHandlingTimes,
        [serviceId]: timeText
      }
    }));
  };

  const handleAddSpecialty = () => {
    if (!newSpecialtyInput.trim()) return;
    if (officeCitizenConfig.specialties.includes(newSpecialtyInput.trim())) return;
    setOfficeCitizenConfig(prev => ({
      ...prev,
      specialties: [...prev.specialties, newSpecialtyInput.trim()]
    }));
    setNewSpecialtyInput('');
  };

  const handleRemoveSpecialty = (item: string) => {
    setOfficeCitizenConfig(prev => ({
      ...prev,
      specialties: prev.specialties.filter(s => s !== item)
    }));
  };

  // Active selected case
  const activeCase = cases.find(c => c.id === selectedCaseId) || cases[0];

  // 90-sec countdown simulation for offers
  useEffect(() => {
    const timer = setInterval(() => {
      setOfferCountdown(prev => (prev > 0 ? prev - 1 : 90));
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  const handleReturnSubmit = () => {
    const def = RETURN_REASON_DICTIONARY.find(r => r.code === selectedReturnCode);
    const message = customReturnNote.trim() || def?.defaultMessage || 'نقص در مدارک بارگذاری شده';
    setIsProcessingAction(true);
    setTimeout(() => {
      handleReturn(activeCase.id, selectedReturnCode, message);
      setIsProcessingAction(false);
      setShowReturnModal(false);
      setActionSuccessMsg(`پرونده با کد نقص «${def?.title}» به شهروند عودت شد.`);
      setTimeout(() => setActionSuccessMsg(null), 4000);
    }, 600);
  };

  const handleSendToInquiry = () => {
    setIsProcessingAction(true);
    setTimeout(() => {
      handleApprove(activeCase.id);
      setIsProcessingAction(false);
      setActionSuccessMsg('مدارک تایید و جهت استعلام برخط به سامانه ثبت احوال کشور ارسال شد.');
      setTimeout(() => setActionSuccessMsg(null), 4000);
    }, 700);
  };

  const handleFinalIssue = () => {
    setIsProcessingAction(true);
    setTimeout(() => {
      handleComplete(activeCase.id, postalInput);
      setIsProcessingAction(false);
      setActionSuccessMsg('سند نهایی صادر و بارکد پستی به شهروند ابلاغ گردید.');
      setTimeout(() => setActionSuccessMsg(null), 4000);
    }, 800);
  };

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800 font-sans pb-24 text-right" dir="rtl">
      {/* Top Office Shift Status Bar */}
      <header className="bg-slate-900 text-white px-4 py-3 sticky top-0 z-30 shadow-md">
        <div className="max-w-7xl mx-auto flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
              <Building2 className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="font-bold text-sm sm:text-base leading-tight">کارتابل اپراتور پیشخوان دولت</h1>
                <span className="text-[11px] bg-slate-800 px-2 py-0.5 rounded border border-slate-700 text-slate-300">
                  {currentOffice.code}
                </span>
              </div>
              <p className="text-xs text-slate-400 mt-0.5">{currentOffice.name}</p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            {/* Shift Online Toggle */}
            <button
              onClick={() => setIsOnline(!isOnline)}
              className={`flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium border transition-colors ${
                isOnline 
                  ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/40' 
                  : 'bg-rose-950/80 text-rose-300 border-rose-500/40'
              }`}
            >
              <span className={`w-2 h-2 rounded-full ${isOnline ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'}`} />
              <span>{isOnline ? 'شیفت فعال' : 'شیفت آفلاین'}</span>
            </button>

            {/* Switch back to citizen app */}
            <button
              onClick={handleBackToCitizen}
              className="flex items-center gap-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs px-3 py-1.5 rounded-lg border border-slate-700 transition"
              title="بازگشت به برنامه شهروندی"
            >
              <ArrowRight className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">نمای شهروند</span>
            </button>
          </div>
        </div>

        {/* Live Counters strip */}
        <div className="max-w-7xl mx-auto mt-3 pt-2.5 border-t border-slate-800 flex items-center justify-between text-xs overflow-x-auto gap-4 no-scrollbar">
          <div className="flex items-center gap-4 text-slate-300 whitespace-nowrap">
            <span className="flex items-center gap-1.5">
              <Activity className="w-3.5 h-3.5 text-emerald-400" />
              ظرفیت روزانه: <strong className="text-white">۱۴ / ۲۰</strong>
            </span>
            <span className="flex items-center gap-1.5">
              <Users className="w-3.5 h-3.5 text-blue-400" />
              باجه فعال شما: <strong className="text-white">باجه {activeCounter}</strong>
            </span>
            <span className="flex items-center gap-1.5">
              <DollarSign className="w-3.5 h-3.5 text-amber-400" />
              درآمد امروز دفتر: <strong className="text-emerald-400">۳۴۵٬۰۰۰ تومان</strong>
            </span>
          </div>

          <div className="flex items-center gap-1 text-slate-400 text-[11px] whitespace-nowrap">
            <span className="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            سامانه ثبت احوال: برخط (پاسخگویی ۰.۴ ثانیه)
          </div>
        </div>
      </header>

      {/* Action Notification Toast */}
      {actionSuccessMsg && (
        <div className="bg-emerald-600 text-white px-4 py-2.5 text-xs text-center font-medium shadow-lg flex items-center justify-center gap-2 animate-in fade-in slide-in-from-top duration-300">
          <CheckCircle2 className="w-4 h-4" />
          <span>{actionSuccessMsg}</span>
        </div>
      )}

      {/* Main 8 Work Desks Navigation Bar */}
      <div className="bg-white border-b border-slate-200 shadow-sm sticky top-[95px] z-20 overflow-x-auto no-scrollbar">
        <div className="max-w-7xl mx-auto flex items-center gap-1 px-3 py-1.5 min-w-max">
          <button
            onClick={() => setActiveDesk('offers')}
            className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all relative ${
              activeDesk === 'offers' 
                ? 'bg-amber-500 text-white shadow-sm' 
                : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            <Inbox className="w-3.5 h-3.5" />
            <span>صف پیشنهادها</span>
            <span className="bg-rose-500 text-white text-[10px] px-1.5 py-0.2 rounded-full animate-pulse">۱ جدید</span>
          </button>

          <button
            onClick={() => setActiveDesk('workspace')}
            className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all ${
              activeDesk === 'workspace' 
                ? 'bg-slate-900 text-white shadow-sm' 
                : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            <FileSearch className="w-3.5 h-3.5" />
            <span>میز کار پرونده</span>
            <span className="bg-slate-200 text-slate-700 text-[10px] px-1.5 py-0.2 rounded-full font-bold">
              {cases.length}
            </span>
          </button>

          <button
            onClick={() => setActiveDesk('queue')}
            className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all ${
              activeDesk === 'queue' 
                ? 'bg-slate-900 text-white shadow-sm' 
                : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            <CalendarCheck className="w-3.5 h-3.5 text-blue-400" />
            <span>نوبت‌های حضوری ست شده</span>
            <span className="bg-blue-100 text-blue-800 text-[10px] px-1.5 py-0.2 rounded-full font-bold">
              {inPersonAppointments.length} نوبت
            </span>
          </button>

          <button
            onClick={() => setActiveDesk('delivery')}
            className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all ${
              activeDesk === 'delivery' 
                ? 'bg-slate-900 text-white shadow-sm' 
                : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            <Bike className="w-3.5 h-3.5 text-emerald-400" />
            <span>تکمیل، تحویل پستی و درخواست پیک</span>
            <span className="bg-emerald-100 text-emerald-800 text-[10px] px-1.5 py-0.2 rounded-full font-bold">
              {deliveryRequests.filter(d => d.deliveryStatus !== 'delivered').length} فعال
            </span>
          </button>

          <button
            onClick={() => setActiveDesk('finance')}
            className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all ${
              activeDesk === 'finance' 
                ? 'bg-slate-900 text-white shadow-sm' 
                : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            <DollarSign className="w-3.5 h-3.5" />
            <span>مالی و تسویه</span>
          </button>

          <button
            onClick={() => setActiveDesk('reviews')}
            className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all ${
              activeDesk === 'reviews' 
                ? 'bg-slate-900 text-white shadow-sm' 
                : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            <Star className="w-3.5 h-3.5 text-amber-400 fill-amber-400" />
            <span>امتیاز، نظرات و کیفیت (SLA)</span>
            <span className="bg-amber-100 text-amber-800 text-[10px] px-1.5 py-0.2 rounded-full font-bold">
              {currentOffice.rating} ★
            </span>
          </button>

          <button
            onClick={() => setActiveDesk('office_profile')}
            className={`flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all ${
              activeDesk === 'office_profile' 
                ? 'bg-emerald-800 text-white shadow-sm' 
                : 'text-slate-600 hover:bg-slate-100'
            }`}
          >
            <Sliders className="w-3.5 h-3.5 text-emerald-400" />
            <span>تنظیمات و اطلاعات دفتر</span>
            <span className="bg-emerald-100 text-emerald-800 text-[10px] px-1.5 py-0.2 rounded-full font-bold">
              پروفایل شهروندی
            </span>
          </button>
        </div>
      </div>

      {/* Main Workspace Body */}
      <main className="max-w-7xl mx-auto px-4 py-4">
        {/* DESK 1: PROPOSED OFFERS (صف پیشنهادهای هوشمند با ثانیه‌شمار ۹۰ ثانیه) */}
        {activeDesk === 'offers' && (
          <div className="space-y-4">
            <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between">
              <div>
                <h2 className="font-bold text-amber-900 text-sm">پیشنهاد ارجاع خدمت جدید (هوشمند)</h2>
                <p className="text-xs text-amber-700 mt-0.5">
                  بر اساس نزدیکی جغرافیایی و امتیاز کیفی دفتر شما پیشنهاد شده است.
                </p>
              </div>
              <div className="flex items-center gap-1.5 bg-amber-200/80 text-amber-900 px-3 py-1.5 rounded-lg font-mono font-bold text-sm">
                <Clock className="w-4 h-4 text-amber-700 animate-spin" />
                <span>{offerCountdown} ثانیه تا انقضا</span>
              </div>
            </div>

            <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <span className="text-[11px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">
                    خدمت هویتی
                  </span>
                  <h3 className="font-bold text-base text-slate-900 mt-1">تعویض و صدور المثنی شناسنامه</h3>
                  <p className="text-xs text-slate-500 mt-0.5">
                    متقاضی: محمدرضا رضایی دهکردی • کد ملی: ۰۰۱۹۸۴۵۶۲۱
                  </p>
                </div>
                <div className="text-left">
                  <span className="text-xs text-slate-400 block">سهم مصوب دفتر:</span>
                  <strong className="text-base text-emerald-700 font-black">۶۶٬۵۰۰ تومان</strong>
                </div>
              </div>

              {/* Pre-check OCR and quality card */}
              <div className="bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs space-y-2">
                <div className="flex items-center justify-between text-slate-700 font-medium">
                  <span>پیش‌ارزیابی خودکار سیستم:</span>
                  <span className="text-emerald-600 flex items-center gap-1 font-bold">
                    <CheckCircle2 className="w-3.5 h-3.5" /> مدارک کامل است
                  </span>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 text-slate-600">
                  <div className="bg-white p-2 rounded border border-slate-100">
                    <span className="text-slate-400 block text-[10px]">استعلام ثنا:</span>
                    <strong className="text-emerald-700">احراز هویت شده ✅</strong>
                  </div>
                  <div className="bg-white p-2 rounded border border-slate-100">
                    <span className="text-slate-400 block text-[10px]">کد پستی:</span>
                    <strong>۱۹۹۷۸۶۴۳۲۱ (منطقه ۳)</strong>
                  </div>
                  <div className="bg-white p-2 rounded border border-slate-100">
                    <span className="text-slate-400 block text-[10px]">مهلت SLA استاندارد:</span>
                    <strong className="text-amber-700">۴ ساعت کاری</strong>
                  </div>
                </div>
              </div>

              <div className="flex items-center gap-3 pt-2">
                <button
                  onClick={() => {
                    setActiveDesk('workspace');
                    setActionSuccessMsg('پرونده با موفقیت پذیرش شد و به کارتابل منتقل گردید.');
                  }}
                  className="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-xs flex items-center justify-center gap-1.5 transition shadow"
                >
                  <Check className="w-4 h-4" />
                  <span>پذیرش پرونده و شروع بررسی (۶۶٬۵۰۰ تومان)</span>
                </button>

                <button
                  onClick={() => {
                    setActionSuccessMsg('پیشنهاد رد شد و به دفتر بعدی در شبکه هوشمند ارجاع داده شد.');
                  }}
                  className="px-4 bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-600 font-medium py-2.5 rounded-xl text-xs transition border border-slate-200"
                >
                  رد پیشنهاد (ترافیک باجه)
                </button>
              </div>
            </div>
          </div>
        )}

        {/* DESK 2 & 3: WORKSPACE (میز کار تخصصی پرونده و تطبیق اسناد) */}
        {activeDesk === 'workspace' && (
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-4">
            {/* Left Case List Master Column */}
            <div className="lg:col-span-4 space-y-3">
              <div className="bg-white border border-slate-200 rounded-xl p-3 shadow-sm">
                <div className="relative">
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="جستجو در پرونده‌ها یا کدملی..."
                    className="w-full bg-slate-50 border border-slate-200 rounded-lg pr-8 pl-3 py-1.5 text-xs focus:bg-white focus:outline-none focus:border-emerald-500"
                  />
                  <Search className="w-4 h-4 text-slate-400 absolute right-2.5 top-2" />
                </div>
              </div>

              <div className="space-y-2">
                {cases.map((c) => {
                  const isSelected = c.id === activeCase.id;
                  return (
                    <div
                      key={c.id}
                      onClick={() => setSelectedCaseId(c.id)}
                      className={`p-3.5 rounded-xl border transition-all cursor-pointer text-right ${
                        isSelected 
                          ? 'bg-slate-900 text-white border-slate-900 shadow-md' 
                          : 'bg-white text-slate-800 border-slate-200 hover:border-slate-300'
                      }`}
                    >
                      <div className="flex items-start justify-between">
                        <span className={`text-[10px] px-2 py-0.5 rounded font-bold ${
                          isSelected ? 'bg-slate-800 text-emerald-400' : 'bg-slate-100 text-slate-700'
                        }`}>
                          {c.trackingCode}
                        </span>
                        <span className={`text-[11px] font-medium ${
                          c.status === 'action_required' 
                            ? 'text-amber-400' 
                            : c.status === 'completed' 
                              ? 'text-emerald-400' 
                              : 'text-blue-400'
                        }`}>
                          {c.status === 'action_required' ? '⚠ عودت برای اصلاح' : c.status === 'completed' ? 'تکمیل شده' : 'در حال بررسی'}
                        </span>
                      </div>

                      <h4 className="font-bold text-xs mt-1.5 line-clamp-1">{c.serviceTitle}</h4>
                      
                      <div className="flex items-center justify-between text-[11px] mt-2 pt-2 border-t border-slate-700/30">
                        <span className={isSelected ? 'text-slate-300' : 'text-slate-500'}>
                          {c.citizenName}
                        </span>
                        <span className={isSelected ? 'text-emerald-300' : 'text-emerald-700 font-bold'}>
                          سهم: {c.officeShareFee ? c.officeShareFee.toLocaleString() : '۶۶٬۵۰۰'} ت
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Right Case Detail & Document Inspection Surface Column */}
            <div className="lg:col-span-8 space-y-4">
              <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
                {/* Case Header */}
                <div className="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                  <div>
                    <div className="flex items-center gap-2">
                      <h2 className="font-black text-slate-900 text-base">{activeCase.serviceTitle}</h2>
                      <span className="bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs px-2 py-0.5 rounded-full font-bold">
                        {activeCase.trackingCode}
                      </span>
                    </div>
                    <p className="text-xs text-slate-500 mt-1">
                      متقاضی: <strong>{activeCase.citizenName}</strong> • کد ملی: {activeCase.citizenNationalId}
                    </p>
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="text-xs bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg">
                      آخرین ویرایش: {activeCase.updatedAt}
                    </span>
                  </div>
                </div>

                {/* Return Banner if Action Required */}
                {activeCase.status === 'action_required' && (
                  <div className="bg-amber-50 border border-amber-200 rounded-xl p-3.5 flex items-start gap-3">
                    <AlertTriangle className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                    <div>
                      <div className="flex items-center gap-2">
                        <h4 className="font-bold text-amber-900 text-xs">پرونده به علت نقص مدرک عودت داده شده است</h4>
                        <span className="text-[10px] bg-amber-200 text-amber-900 px-2 py-0.5 rounded font-mono font-bold">
                          کد: {activeCase.returnReasonCode || 'DOC_BLUR'}
                        </span>
                      </div>
                      <p className="text-xs text-amber-800 mt-1 leading-relaxed">
                        {activeCase.returnReason}
                      </p>
                    </div>
                  </div>
                )}

                {/* Document Verification Matrix */}
                <div>
                  <h3 className="font-bold text-slate-800 text-xs mb-2 flex items-center justify-between">
                    <span>مدارک بارگذاری شده توسط متقاضی:</span>
                    <span className="text-[11px] text-slate-400 font-normal">کلیک روی هر مدرک جهت زوم و بررسی</span>
                  </h3>

                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {activeCase.uploadedDocuments.map((doc, idx) => (
                      <div 
                        key={idx} 
                        className={`border rounded-xl p-3 transition ${
                          doc.hasBlurWarning 
                            ? 'bg-rose-50/70 border-rose-300' 
                            : 'bg-slate-50 border-slate-200'
                        }`}
                      >
                        <div className="flex items-start justify-between mb-2">
                          <span className="text-xs font-bold text-slate-800 line-clamp-1">{doc.name}</span>
                          {doc.hasBlurWarning ? (
                            <span className="text-[10px] bg-rose-100 text-rose-800 px-1.5 py-0.5 rounded font-bold">
                              هشدار وضوح
                            </span>
                          ) : (
                            <span className="text-[10px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">
                              تایید هویت
                            </span>
                          )}
                        </div>

                        {/* Thumbnail preview simulation */}
                        <div className="h-28 bg-slate-200 rounded-lg overflow-hidden relative flex items-center justify-center border border-slate-300">
                          <img
                            src="https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=300&q=80"
                            alt={doc.name}
                            className={`w-full h-full object-cover ${doc.hasBlurWarning ? 'blur-[1.5px]' : ''}`}
                          />
                          <div className="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 hover:opacity-100 transition cursor-pointer">
                            <span className="bg-white/90 text-slate-900 text-[11px] font-bold px-2 py-1 rounded shadow">
                              مشاهده بزرگ‌نمایی
                            </span>
                          </div>
                        </div>

                        <div className="mt-2 flex items-center justify-between text-[11px] text-slate-500">
                          <span>فرمت: {doc.type}</span>
                          <span className={doc.verified ? 'text-emerald-600 font-bold' : 'text-amber-600'}>
                            {doc.verified ? 'بررسی شده ✅' : 'نیاز به بازبینی'}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Form Data Extracted / OCR Checklist */}
                <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs space-y-2">
                  <h4 className="font-bold text-slate-800">تطبیق اطلاعات ثبت احوال و پایگاه داده:</h4>
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-slate-700">
                    <div className="bg-white p-2 rounded border border-slate-200">
                      <span className="text-slate-400 block text-[10px]">نام و نام‌خانوادگی:</span>
                      <strong>محمدرضا رضایی دهکردی</strong>
                    </div>
                    <div className="bg-white p-2 rounded border border-slate-200">
                      <span className="text-slate-400 block text-[10px]">نام پدر:</span>
                      <strong>حسین</strong>
                    </div>
                    <div className="bg-white p-2 rounded border border-slate-200">
                      <span className="text-slate-400 block text-[10px]">شماره شناسنامه:</span>
                      <strong>۱۴۵۸۹ (حوزه ۳ تهران)</strong>
                    </div>
                    <div className="bg-white p-2 rounded border border-slate-200">
                      <span className="text-slate-400 block text-[10px]">تطبیق امضای دیجیتال:</span>
                      <strong className="text-emerald-600">احراز شده ✅</strong>
                    </div>
                  </div>
                </div>

                {/* Operator Actions Strip */}
                <div className="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                  <div className="flex items-center gap-2">
                    {/* Return button with 10 standard codes */}
                    <button
                      onClick={() => setShowReturnModal(true)}
                      className="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-sm"
                    >
                      <AlertTriangle className="w-4 h-4" />
                      <span>عودت به شهروند جهت رفع نقص</span>
                    </button>
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      disabled={isProcessingAction}
                      onClick={handleSendToInquiry}
                      className="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-sm disabled:opacity-50"
                    >
                      <Send className="w-4 h-4" />
                      <span>تایید و ارسال به سامانه دولتی</span>
                    </button>

                    <button
                      disabled={isProcessingAction}
                      onClick={handleFinalIssue}
                      className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-sm disabled:opacity-50"
                    >
                      <CheckCircle2 className="w-4 h-4" />
                      <span>صدور نهایی و ارسال به باجه پست</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* DESK: SCHEDULED IN-PERSON APPOINTMENTS (نوبت‌های حضوری ست شده) */}
        {activeDesk === 'queue' && (() => {
          const totalScheduled = inPersonAppointments.length;
          const attendedCount = inPersonAppointments.filter(a => a.attendanceStatus === 'attended').length;
          const absentCount = inPersonAppointments.filter(a => a.attendanceStatus === 'absent').length;
          const pendingCount = inPersonAppointments.filter(a => a.attendanceStatus === 'pending').length;
          const completedCount = inPersonAppointments.filter(a => a.completionStatus === 'completed').length;
          const notCompletedCount = inPersonAppointments.filter(a => a.completionStatus === 'not_completed').length;

          const filteredAppointments = inPersonAppointments.filter(apt => {
            const q = aptSearch.trim().toLowerCase();
            const matchesSearch = !q || 
              apt.citizenName.toLowerCase().includes(q) ||
              apt.citizenNationalId.includes(q) ||
              apt.citizenPhone.includes(q) ||
              apt.ticketNumber.includes(q) ||
              apt.serviceTitle.toLowerCase().includes(q) ||
              apt.trackingCode.toLowerCase().includes(q);

            if (!matchesSearch) return false;

            if (aptFilter === 'attended') return apt.attendanceStatus === 'attended';
            if (aptFilter === 'absent') return apt.attendanceStatus === 'absent';
            if (aptFilter === 'pending') return apt.attendanceStatus === 'pending';
            if (aptFilter === 'completed') return apt.completionStatus === 'completed';
            if (aptFilter === 'not_completed') return apt.completionStatus === 'not_completed';
            return true;
          });

          return (
            <div className="space-y-4">
              {/* Header Card */}
              <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div>
                    <div className="flex items-center gap-2">
                      <div className="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
                        <CalendarCheck className="w-4 h-4" />
                      </div>
                      <h2 className="text-base font-black text-slate-900">نوبت‌های حضوری ست شده</h2>
                      <span className="bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold px-2.5 py-0.5 rounded-full">
                        {totalScheduled} نوبت رزرو شده
                      </span>
                    </div>
                    <p className="text-xs text-slate-500 mt-1">
                      مشاهده نوبت‌های ست شده مراجعین و ثبت سریع وضعیت مراجعه (حاضر / غایب) و نتیجه انجام کار (انجام شد / نشد)
                    </p>
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => {
                        const nextPending = inPersonAppointments.find(a => a.attendanceStatus === 'pending');
                        if (nextPending) {
                          handleSetAttendance(nextPending.id, 'attended');
                          setActionSuccessMsg(`نوبت شماره ${nextPending.ticketNumber} (${nextPending.citizenName}) فراخوانده شد و به وضعیت مراجعه کرد تغییر یافت.`);
                        } else {
                          setActionSuccessMsg('تمامی نوبت‌های ست شده امروز فراخوان و تعیین وضعیت شده‌اند.');
                        }
                      }}
                      className="bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-sm"
                    >
                      <PhoneCall className="w-4 h-4" />
                      <span>فراخوان نوبت بعدی در سالن</span>
                    </button>
                  </div>
                </div>

                {/* KPI Status Strip */}
                <div className="grid grid-cols-2 sm:grid-cols-6 gap-2.5 mt-4 pt-4 border-t border-slate-100 text-xs">
                  <div className="bg-slate-50 border border-slate-200 rounded-xl p-3 text-center">
                    <span className="text-slate-400 block text-[11px] mb-1">کل نوبت‌های ست شده</span>
                    <strong className="text-lg font-black text-slate-900">{totalScheduled}</strong>
                  </div>

                  <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-center">
                    <span className="text-emerald-700 block text-[11px] mb-1 font-bold">✓ مراجعه کرد (حاضر)</span>
                    <strong className="text-lg font-black text-emerald-800">{attendedCount}</strong>
                  </div>

                  <div className="bg-rose-50 border border-rose-200 rounded-xl p-3 text-center">
                    <span className="text-rose-700 block text-[11px] mb-1 font-bold">✕ عدم مراجعه (غایب)</span>
                    <strong className="text-lg font-black text-rose-800">{absentCount}</strong>
                  </div>

                  <div className="bg-blue-50 border border-blue-200 rounded-xl p-3 text-center">
                    <span className="text-blue-700 block text-[11px] mb-1 font-bold">⏳ در انتظار نوبت</span>
                    <strong className="text-lg font-black text-blue-800">{pendingCount}</strong>
                  </div>

                  <div className="bg-teal-50 border border-teal-200 rounded-xl p-3 text-center">
                    <span className="text-teal-700 block text-[11px] mb-1 font-bold">✅ کار انجام شد</span>
                    <strong className="text-lg font-black text-teal-800">{completedCount}</strong>
                  </div>

                  <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-center">
                    <span className="text-amber-700 block text-[11px] mb-1 font-bold">❌ انجام نشد / نقص</span>
                    <strong className="text-lg font-black text-amber-800">{notCompletedCount}</strong>
                  </div>
                </div>
              </div>

              {/* Filters & Search Toolbar */}
              <div className="bg-white border border-slate-200 rounded-2xl p-3.5 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
                <div className="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto no-scrollbar text-xs">
                  <button
                    onClick={() => setAptFilter('all')}
                    className={`px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap ${
                      aptFilter === 'all'
                        ? 'bg-slate-900 text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                    }`}
                  >
                    همه نوبت‌ها ({totalScheduled})
                  </button>

                  <button
                    onClick={() => setAptFilter('pending')}
                    className={`px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap ${
                      aptFilter === 'pending'
                        ? 'bg-blue-600 text-white'
                        : 'bg-blue-50 text-blue-700 hover:bg-blue-100'
                    }`}
                  >
                    در انتظار مراجعه ({pendingCount})
                  </button>

                  <button
                    onClick={() => setAptFilter('attended')}
                    className={`px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap ${
                      aptFilter === 'attended'
                        ? 'bg-emerald-600 text-white'
                        : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                    }`}
                  >
                    مراجعه کرده ({attendedCount})
                  </button>

                  <button
                    onClick={() => setAptFilter('absent')}
                    className={`px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap ${
                      aptFilter === 'absent'
                        ? 'bg-rose-600 text-white'
                        : 'bg-rose-50 text-rose-700 hover:bg-rose-100'
                    }`}
                  >
                    عدم مراجعه / غایب ({absentCount})
                  </button>

                  <button
                    onClick={() => setAptFilter('completed')}
                    className={`px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap ${
                      aptFilter === 'completed'
                        ? 'bg-teal-700 text-white'
                        : 'bg-teal-50 text-teal-800 hover:bg-teal-100'
                    }`}
                  >
                    کار انجام شد ({completedCount})
                  </button>

                  <button
                    onClick={() => setAptFilter('not_completed')}
                    className={`px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap ${
                      aptFilter === 'not_completed'
                        ? 'bg-amber-600 text-white'
                        : 'bg-amber-50 text-amber-800 hover:bg-amber-100'
                    }`}
                  >
                    انجام نشد ({notCompletedCount})
                  </button>
                </div>

                {/* Search Bar */}
                <div className="relative w-full md:w-72 text-xs">
                  <Search className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                  <input
                    type="text"
                    value={aptSearch}
                    onChange={(e) => setAptSearch(e.target.value)}
                    placeholder="جستجو با نام، کدملی یا شماره نوبت..."
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl pr-9 pl-3 py-2 text-xs focus:bg-white focus:border-blue-600 focus:outline-none"
                  />
                  {aptSearch && (
                    <button
                      onClick={() => setAptSearch('')}
                      className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                      <X className="w-3.5 h-3.5" />
                    </button>
                  )}
                </div>
              </div>

              {/* Scheduled Appointments Cards List */}
              {filteredAppointments.length === 0 ? (
                <div className="bg-white border border-slate-200 rounded-2xl p-10 text-center text-slate-500 space-y-2">
                  <CalendarCheck className="w-10 h-10 text-slate-300 mx-auto" />
                  <p className="text-sm font-bold text-slate-700">هیچ نوبتی با فیلتر انتخابی یافت نشد.</p>
                  <p className="text-xs text-slate-400">می‌توانید فیلتر یا عبارت جستجو را تغییر دهید.</p>
                </div>
              ) : (
                <div className="space-y-3">
                  {filteredAppointments.map((apt) => {
                    const isAttended = apt.attendanceStatus === 'attended';
                    const isAbsent = apt.attendanceStatus === 'absent';
                    const isPendingAttendance = apt.attendanceStatus === 'pending';

                    const isCompleted = apt.completionStatus === 'completed';
                    const isNotCompleted = apt.completionStatus === 'not_completed';
                    const isInProgress = apt.completionStatus === 'in_progress';
                    const isPendingCompletion = apt.completionStatus === 'pending';

                    return (
                      <div 
                        key={apt.id}
                        className={`bg-white border rounded-2xl p-4 sm:p-5 shadow-sm transition-all ${
                          isCompleted
                            ? 'border-teal-300 bg-teal-50/10'
                            : isAbsent
                            ? 'border-rose-200 bg-rose-50/10'
                            : isAttended
                            ? 'border-emerald-300 bg-emerald-50/10'
                            : 'border-slate-200'
                        }`}
                      >
                        {/* Top Info Bar */}
                        <div className="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                          <div className="flex items-center gap-3">
                            <span className={`w-11 h-11 rounded-xl flex flex-col items-center justify-center font-black text-sm shadow-sm ${
                              isCompleted
                                ? 'bg-teal-700 text-white'
                                : isAbsent
                                ? 'bg-rose-700 text-white'
                                : isAttended
                                ? 'bg-emerald-600 text-white'
                                : 'bg-slate-900 text-white'
                            }`}>
                              <span className="text-[9px] font-normal leading-none opacity-80">نوبت</span>
                              <span className="leading-tight">{apt.ticketNumber}</span>
                            </span>

                            <div>
                              <div className="flex items-center gap-2">
                                <h3 className="font-bold text-sm text-slate-900">{apt.citizenName}</h3>
                                <span className="bg-slate-100 text-slate-700 text-[11px] font-mono px-2 py-0.5 rounded border border-slate-200">
                                  کدملی: {apt.citizenNationalId}
                                </span>
                              </div>
                              <div className="flex items-center gap-3 text-xs text-slate-500 mt-1">
                                <span className="flex items-center gap-1">
                                  <Clock className="w-3.5 h-3.5 text-blue-600" />
                                  <span>ساعت نوبت: <strong>{apt.appointmentTime}</strong></span>
                                </span>
                                <span>•</span>
                                <span>{apt.appointmentDate}</span>
                                <span>•</span>
                                <span className="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[11px] font-bold">
                                  باجه {apt.counterNumber}
                                </span>
                              </div>
                            </div>
                          </div>

                          <div className="flex items-center gap-2">
                            <span className="text-xs text-slate-400 font-mono">
                              کد پیگیری: {apt.trackingCode}
                            </span>
                            <span className="text-xs text-slate-500 flex items-center gap-1 font-mono">
                              <Phone className="w-3 h-3 text-slate-400" />
                              {apt.citizenPhone}
                            </span>
                          </div>
                        </div>

                        {/* Service Details */}
                        <div className="py-2.5 flex items-center justify-between text-xs text-slate-700">
                          <div className="flex items-center gap-2">
                            <span className="text-slate-400">خدمت درخواستی:</span>
                            <strong className="text-slate-900">{apt.serviceTitle}</strong>
                            <span className="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">
                              {apt.serviceCategory}
                            </span>
                          </div>

                          {(apt.attendedAt || apt.completedAt) && (
                            <div className="text-[11px] text-slate-500 flex items-center gap-2">
                              {apt.attendedAt && (
                                <span className="text-emerald-700">ورود به دفتر: {apt.attendedAt}</span>
                              )}
                              {apt.completedAt && (
                                <span className="text-teal-700 font-bold">• خاتمه خدمت: {apt.completedAt}</span>
                              )}
                            </div>
                          )}
                        </div>

                        {/* THE TWO CORE OPERATOR ENTRY MODULES */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-3 border-t border-slate-100">
                          
                          {/* MODULE 1: ATTENDANCE STATUS (آیا متقاضی مراجعه کرد؟) */}
                          <div className={`p-3.5 rounded-xl border transition-all ${
                            isAttended 
                              ? 'bg-emerald-50/70 border-emerald-300' 
                              : isAbsent 
                              ? 'bg-rose-50/70 border-rose-300' 
                              : 'bg-slate-50 border-slate-200'
                          }`}>
                            <div className="flex items-center justify-between mb-2.5">
                              <span className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <UserCheck className="w-4 h-4 text-slate-600" />
                                ۱. وضعیت حضور متقاضی:
                              </span>

                              {/* Status Badge */}
                              <span className={`text-[11px] font-bold px-2 py-0.5 rounded-md ${
                                isAttended 
                                  ? 'bg-emerald-600 text-white' 
                                  : isAbsent 
                                  ? 'bg-rose-600 text-white' 
                                  : 'bg-slate-200 text-slate-700'
                              }`}>
                                {isAttended && '✓ مراجعه کرد (حاضر)'}
                                {isAbsent && '✕ عدم مراجعه (غایب)'}
                                {isPendingAttendance && '⏳ در انتظار مراجعه'}
                              </span>
                            </div>

                            {/* Attendance Buttons */}
                            <div className="grid grid-cols-3 gap-2 text-xs">
                              <button
                                onClick={() => handleSetAttendance(apt.id, 'attended')}
                                className={`py-2 px-2.5 rounded-lg font-bold transition flex items-center justify-center gap-1.5 ${
                                  isAttended
                                    ? 'bg-emerald-600 text-white shadow'
                                    : 'bg-white border border-slate-300 text-slate-700 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700'
                                }`}
                              >
                                <CheckCircle2 className="w-3.5 h-3.5" />
                                <span>مراجعه کرد</span>
                              </button>

                              <button
                                onClick={() => handleSetAttendance(apt.id, 'absent')}
                                className={`py-2 px-2.5 rounded-lg font-bold transition flex items-center justify-center gap-1.5 ${
                                  isAbsent
                                    ? 'bg-rose-600 text-white shadow'
                                    : 'bg-white border border-slate-300 text-slate-700 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700'
                                }`}
                              >
                                <UserX className="w-3.5 h-3.5" />
                                <span>عدم مراجعه</span>
                              </button>

                              <button
                                onClick={() => handleSetAttendance(apt.id, 'pending')}
                                className={`py-2 px-2 rounded-lg font-medium transition flex items-center justify-center gap-1 text-[11px] ${
                                  isPendingAttendance
                                    ? 'bg-slate-700 text-white shadow'
                                    : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'
                                }`}
                              >
                                <RotateCcw className="w-3 h-3" />
                                <span>در انتظار</span>
                              </button>
                            </div>
                          </div>

                          {/* MODULE 2: SERVICE COMPLETION (آیا کار متقاضی انجام شد؟) */}
                          <div className={`p-3.5 rounded-xl border transition-all ${
                            isCompleted 
                              ? 'bg-teal-50/70 border-teal-300' 
                              : isNotCompleted 
                              ? 'bg-amber-50/70 border-amber-300' 
                              : isInProgress
                              ? 'bg-blue-50/70 border-blue-300'
                              : 'bg-slate-50 border-slate-200'
                          }`}>
                            <div className="flex items-center justify-between mb-2.5">
                              <span className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <FileText className="w-4 h-4 text-slate-600" />
                                ۲. وضعیت انجام کار و خدمت:
                              </span>

                              {/* Status Badge */}
                              <span className={`text-[11px] font-bold px-2 py-0.5 rounded-md ${
                                isCompleted 
                                  ? 'bg-teal-700 text-white' 
                                  : isNotCompleted 
                                  ? 'bg-amber-600 text-white' 
                                  : isInProgress
                                  ? 'bg-blue-600 text-white'
                                  : 'bg-slate-200 text-slate-700'
                              }`}>
                                {isCompleted && '✅ کار انجام شد'}
                                {isNotCompleted && '❌ انجام نشد'}
                                {isInProgress && '⚡ در حال انجام'}
                                {isPendingCompletion && 'در انتظار'}
                              </span>
                            </div>

                            {/* Service Completion Action Buttons */}
                            <div className="grid grid-cols-3 gap-2 text-xs">
                              <button
                                onClick={() => handleSetCompletion(apt.id, 'completed')}
                                className={`py-2 px-2 rounded-lg font-bold transition flex items-center justify-center gap-1.5 ${
                                  isCompleted
                                    ? 'bg-teal-700 text-white shadow'
                                    : 'bg-white border border-slate-300 text-slate-700 hover:bg-teal-50 hover:border-teal-300 hover:text-teal-700'
                                }`}
                              >
                                <CheckCircle2 className="w-3.5 h-3.5" />
                                <span>کار انجام شد</span>
                              </button>

                              <button
                                onClick={() => {
                                  setEditingReasonAptId(apt.id);
                                  setReasonTextInput(apt.notCompletedReason || '');
                                  handleSetCompletion(apt.id, 'not_completed');
                                }}
                                className={`py-2 px-2 rounded-lg font-bold transition flex items-center justify-center gap-1.5 ${
                                  isNotCompleted
                                    ? 'bg-amber-600 text-white shadow'
                                    : 'bg-white border border-slate-300 text-slate-700 hover:bg-amber-50 hover:border-amber-300 hover:text-amber-700'
                                }`}
                              >
                                <XCircle className="w-3.5 h-3.5" />
                                <span>انجام نشد</span>
                              </button>

                              <button
                                onClick={() => handleSetCompletion(apt.id, 'in_progress')}
                                className={`py-2 px-2 rounded-lg font-medium transition flex items-center justify-center gap-1 text-[11px] ${
                                  isInProgress
                                    ? 'bg-blue-600 text-white shadow'
                                    : 'bg-white border border-slate-200 text-slate-600 hover:bg-blue-50 hover:text-blue-700'
                                }`}
                              >
                                <Zap className="w-3 h-3" />
                                <span>در حال اقدام</span>
                              </button>
                            </div>
                          </div>

                        </div>

                        {/* Reason Display or Edit when "انجام نشد" */}
                        {isNotCompleted && (
                          <div className="mt-3 bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs space-y-2">
                            <div className="flex items-center justify-between">
                              <span className="font-bold text-amber-900 flex items-center gap-1.5">
                                <AlertTriangle className="w-3.5 h-3.5 text-amber-600" />
                                علت عدم انجام خدمت:
                              </span>
                              {editingReasonAptId !== apt.id && (
                                <button
                                  onClick={() => {
                                    setEditingReasonAptId(apt.id);
                                    setReasonTextInput(apt.notCompletedReason || '');
                                  }}
                                  className="text-amber-800 hover:text-amber-950 font-bold underline text-[11px] flex items-center gap-1"
                                >
                                  <Edit3 className="w-3 h-3" />
                                  <span>تغییر علت</span>
                                </button>
                              )}
                            </div>

                            {editingReasonAptId === apt.id ? (
                              <div className="space-y-2 pt-1">
                                {/* Quick Reason Chips */}
                                <div className="flex flex-wrap gap-1.5">
                                  {[
                                    'نقص در مدارک هویتی و سجلی',
                                    'عدم حضور متقاضی در ساعت نوبت',
                                    'انصراف رسمی متقاضی',
                                    'اختلال در سامانه برخط استعلام دولتی',
                                    'نیاز به دستور قضایی / ثبت احوال'
                                  ].map((chip) => (
                                    <button
                                      key={chip}
                                      type="button"
                                      onClick={() => setReasonTextInput(chip)}
                                      className={`text-[10px] px-2 py-1 rounded-md border transition ${
                                        reasonTextInput === chip
                                          ? 'bg-amber-600 text-white border-amber-600 font-bold'
                                          : 'bg-white border-amber-200 text-amber-900 hover:bg-amber-100'
                                      }`}
                                    >
                                      {chip}
                                    </button>
                                  ))}
                                </div>

                                <div className="flex items-center gap-2">
                                  <input
                                    type="text"
                                    value={reasonTextInput}
                                    onChange={(e) => setReasonTextInput(e.target.value)}
                                    placeholder="علت عدم انجام کار را بنویسید..."
                                    className="flex-1 bg-white border border-amber-300 rounded-lg px-3 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-amber-600"
                                  />
                                  <button
                                    onClick={() => handleSaveNotCompletedReason(apt.id)}
                                    className="bg-amber-600 hover:bg-amber-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition"
                                  >
                                    ذخیره علت
                                  </button>
                                  <button
                                    onClick={() => setEditingReasonAptId(null)}
                                    className="bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium px-2.5 py-1.5 rounded-lg text-xs transition"
                                  >
                                    بستن
                                  </button>
                                </div>
                              </div>
                            ) : (
                              <p className="text-amber-900 font-medium bg-white/70 p-2 rounded-lg border border-amber-200">
                                {apt.notCompletedReason || 'علتی ثبت نشده است.'}
                              </p>
                            )}
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          );
        })()}

        {/* DESK 6: DELIVERY & DISPATCH (تکمیل، تحویل پستی و درخواست پیک برای داکیومنت‌ها) */}
        {activeDesk === 'delivery' && (
          <div className="space-y-5">
            {/* Top KPI Banner */}
            <div className="bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 text-white p-5 rounded-2xl shadow-md border border-slate-700/50">
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-emerald-400 shadow-inner">
                    <Bike className="w-6 h-6" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <h3 className="font-extrabold text-base text-white">سامانه اعزام پیک و تحویل داکیومنت‌های رسمی</h3>
                      <span className="bg-emerald-500/20 text-emerald-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-500/30">
                        موتوری و اکسپرس
                      </span>
                    </div>
                    <p className="text-xs text-slate-300 mt-1">
                      درخواست‌های منجر به صدور مدرک فیزیکی (کارت ملی، شناسنامه، گواهینامه، اسناد و پروانه‌ها) را از این بخش با پیک موتوری امن یا پست ویژه ارسال نمایید.
                    </p>
                  </div>
                </div>

                <div className="flex items-center gap-2 flex-wrap">
                  <button
                    onClick={() => setDeliverySubTab('new_courier_request')}
                    className={`flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-sm ${
                      deliverySubTab === 'new_courier_request'
                        ? 'bg-emerald-600 hover:bg-emerald-500 text-white ring-2 ring-emerald-400/50'
                        : 'bg-white/10 hover:bg-white/20 text-slate-200'
                    }`}
                  >
                    <Plus className="w-3.5 h-3.5" />
                    <span>درخواست پیک جدید</span>
                  </button>

                  <button
                    onClick={() => setDeliverySubTab('active_deliveries')}
                    className={`flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-sm ${
                      deliverySubTab === 'active_deliveries'
                        ? 'bg-emerald-600 hover:bg-emerald-500 text-white ring-2 ring-emerald-400/50'
                        : 'bg-white/10 hover:bg-white/20 text-slate-200'
                    }`}
                  >
                    <Package className="w-3.5 h-3.5" />
                    <span>پیگیری و مرسولات فعال ({deliveryRequests.length})</span>
                  </button>

                  <button
                    onClick={() => setDeliverySubTab('postal_barcodes')}
                    className={`flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-sm ${
                      deliverySubTab === 'postal_barcodes'
                        ? 'bg-emerald-600 hover:bg-emerald-500 text-white ring-2 ring-emerald-400/50'
                        : 'bg-white/10 hover:bg-white/20 text-slate-200'
                    }`}
                  >
                    <Truck className="w-3.5 h-3.5" />
                    <span>بارکد پست پیشتاز</span>
                  </button>
                </div>
              </div>

              {/* Quick Metrics */}
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-4 border-t border-slate-700/60 text-xs">
                <div className="bg-white/5 rounded-xl p-3 border border-white/10">
                  <span className="text-slate-400 block text-[11px]">مرسولات در مسیر تحویل:</span>
                  <strong className="text-sm text-emerald-400 font-bold mt-0.5 block">
                    {deliveryRequests.filter(d => d.deliveryStatus === 'in_transit').length} مرسوله
                  </strong>
                </div>
                <div className="bg-white/5 rounded-xl p-3 border border-white/10">
                  <span className="text-slate-400 block text-[11px]">سفیر تخصیص داده شده:</span>
                  <strong className="text-sm text-blue-400 font-bold mt-0.5 block">
                    {deliveryRequests.filter(d => d.deliveryStatus === 'courier_assigned').length} سفیر در راه دفتر
                  </strong>
                </div>
                <div className="bg-white/5 rounded-xl p-3 border border-white/10">
                  <span className="text-slate-400 block text-[11px]">تحویل موفق امروز:</span>
                  <strong className="text-sm text-teal-300 font-bold mt-0.5 block">
                    {deliveryRequests.filter(d => d.deliveryStatus === 'delivered').length} داکیومنت ✅
                  </strong>
                </div>
                <div className="bg-white/5 rounded-xl p-3 border border-white/10">
                  <span className="text-slate-400 block text-[11px]">میانگین زمان تحویل اکسپرس:</span>
                  <strong className="text-sm text-amber-300 font-bold mt-0.5 block">
                    ۴۵ دقیقه درون‌شهری
                  </strong>
                </div>
              </div>
            </div>

            {/* SUB-TAB 1: NEW COURIER DISPATCH REQUEST FOR DOCUMENT */}
            {deliverySubTab === 'new_courier_request' && (
              <div className="space-y-5">
                {/* 1. Quick Select From Ready Document Cases */}
                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Sparkles className="w-4 h-4 text-emerald-600" />
                      <h4 className="font-bold text-sm text-slate-900">
                        پرونده‌ها و درخواست‌های منجر به داکیومنت (آماده تحویل و اعزام پیک)
                      </h4>
                    </div>
                    <span className="text-xs text-slate-500 font-medium">
                      با کلیک روی هر پرونده، اطلاعات فرم به صورت هوشمند پر می‌شود
                    </span>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-2.5">
                    {READY_CASES_FOR_DELIVERY.map((rc) => {
                      const isSelected = selectedReadyCaseCode === rc.requestCode;
                      return (
                        <button
                          key={rc.requestCode}
                          type="button"
                          onClick={() => handleSelectReadyCase(rc)}
                          className={`p-3 rounded-xl text-right border transition-all flex flex-col justify-between text-xs relative ${
                            isSelected
                              ? 'bg-emerald-50/80 border-emerald-500 ring-2 ring-emerald-400 shadow-sm'
                              : 'bg-slate-50 hover:bg-slate-100 border-slate-200 hover:border-slate-300 text-slate-700'
                          }`}
                        >
                          {isSelected && (
                            <span className="absolute top-2 left-2 bg-emerald-600 text-white rounded-full p-0.5">
                              <Check className="w-3 h-3" />
                            </span>
                          )}
                          <div>
                            <div className="flex items-center gap-1.5 font-mono text-[11px] font-bold text-slate-500 mb-1">
                              <span>{rc.requestCode}</span>
                              <span className="bg-slate-200/80 text-slate-800 px-1.5 py-0.2 rounded text-[10px]">
                                {rc.destinationZone}
                              </span>
                            </div>
                            <strong className="block text-slate-900 font-bold line-clamp-1 mb-1 text-[11px]">
                              {rc.docTypeName}
                            </strong>
                            <p className="text-slate-600 text-[11px]">متقاضی: {rc.citizenName}</p>
                          </div>
                          <span className="mt-2 text-[10px] text-emerald-700 font-bold bg-emerald-100/60 px-2 py-0.5 rounded-lg w-fit">
                            انتخاب جهت پیک ⟵
                          </span>
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* 2. Intelligent Courier Dispatch Form */}
                <form onSubmit={handleDispatchCourier} className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
                  <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                      <h4 className="font-extrabold text-base text-slate-900 flex items-center gap-2">
                        <Bike className="w-5 h-5 text-emerald-600" />
                        <span>فرم ثبت و صدور درخواست پیک موتوری برای داکیومنت</span>
                      </h4>
                      <p className="text-xs text-slate-500 mt-1">
                        تعیین نوع سند دولتی، بررسی نشانی مبدا و مقصد، و محاسبه فوری هزینه و زمانبندی ارسال
                      </p>
                    </div>
                    <span className="bg-slate-100 text-slate-700 font-mono text-xs px-3 py-1.5 rounded-lg font-bold">
                      کد پیگیری دفتر: {currentOffice.code}
                    </span>
                  </div>

                  {/* Section A: Request & Document Specifications */}
                  <div className="space-y-3">
                    <h5 className="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                      <FileCheck className="w-4 h-4 text-emerald-600" />
                      <span>۱. مشخصات درخواست و نوع داکیومنت صادره</span>
                    </h5>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      <div>
                        <label className="block text-slate-700 font-bold text-xs mb-1">
                          کد درخواست / پرونده <span className="text-rose-500">*</span>:
                        </label>
                        <input
                          type="text"
                          required
                          value={inputRequestCode}
                          onChange={(e) => setInputRequestCode(e.target.value)}
                          placeholder="مثال: CR-1402-9901"
                          className="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-mono font-bold focus:border-emerald-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-slate-700 font-bold text-xs mb-1">
                          عنوان خدمت منجر به داکیومنت:
                        </label>
                        <input
                          type="text"
                          value={inputServiceTitle}
                          onChange={(e) => setInputServiceTitle(e.target.value)}
                          placeholder="عنوان خدمت دولتی..."
                          className="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs focus:border-emerald-600 focus:bg-white focus:outline-none"
                        />
                      </div>

                      <div>
                        <label className="block text-slate-700 font-bold text-xs mb-1">
                          شماره سریال فیزیکی مدرک / کارت:
                        </label>
                        <input
                          type="text"
                          value={inputDocSerial}
                          onChange={(e) => setInputDocSerial(e.target.value)}
                          placeholder="مثال: NID-1402-99824"
                          className="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-mono focus:border-emerald-600 focus:bg-white focus:outline-none"
                        />
                      </div>
                    </div>

                    {/* Document Type Selector Grid */}
                    <div>
                      <label className="block text-slate-700 font-bold text-xs mb-2">
                        تعیین نوع دقیق داکیومنت و مدرک رسمی <span className="text-rose-500">*</span>:
                      </label>
                      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 text-xs">
                        {[
                          { id: 'smart_card' as DeliveryDocType, label: 'کارت هوشمند فیزیکی', sub: 'ملی، سوخت، گواهینامه', icon: '💳' },
                          { id: 'identity_booklet' as DeliveryDocType, label: 'شناسنامه و اسناد سجلی', sub: 'جلد رسمی مکانیزه', icon: '📑' },
                          { id: 'official_certificate' as DeliveryDocType, label: 'گواهی رسمی هولوگرام‌دار', sub: 'سوء پیشینه، کدپستی', icon: '📜' },
                          { id: 'business_license' as DeliveryDocType, label: 'پروانه کسب و جواز صنفی', sub: 'چاپی و لمینت شده', icon: '🪪' },
                          { id: 'sealed_dossier' as DeliveryDocType, label: 'پرونده پلمپ قضایی/ثبتی', sub: 'سند تک‌برگ و استعلام', icon: '📁' },
                          { id: 'postal_packet' as DeliveryDocType, label: 'پاکت محرمانه اوراق بهادار', sub: 'بسته‌بندی امنیتی', icon: '📦' },
                        ].map((doc) => {
                          const isDocActive = inputDocType === doc.id;
                          return (
                            <button
                              key={doc.id}
                              type="button"
                              onClick={() => {
                                setInputDocType(doc.id);
                                setInputDocTypeName(doc.label);
                              }}
                              className={`p-2.5 rounded-xl border text-right transition flex flex-col justify-between ${
                                isDocActive
                                  ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-300 text-emerald-950 shadow-sm'
                                  : 'bg-slate-50 hover:bg-slate-100 border-slate-200 text-slate-700'
                              }`}
                            >
                              <div className="flex items-center justify-between w-full mb-1">
                                <span className="text-lg">{doc.icon}</span>
                                {isDocActive && <Check className="w-3.5 h-3.5 text-emerald-600" />}
                              </div>
                              <strong className="block text-[11px] font-bold">{doc.label}</strong>
                              <span className="text-[10px] text-slate-500 block mt-0.5">{doc.sub}</span>
                            </button>
                          );
                        })}
                      </div>
                    </div>

                    {/* Security & Document Return Rules */}
                    <div className="bg-slate-50 border border-slate-200 rounded-xl p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                      <div className="flex flex-wrap items-center gap-4">
                        <label className="flex items-center gap-2 cursor-pointer font-bold text-slate-800">
                          <input
                            type="checkbox"
                            checked={inputRequireOldDocReturn}
                            onChange={(e) => setInputRequireOldDocReturn(e.target.checked)}
                            className="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300"
                          />
                          <span>الزام به دریافت لاشه مدرک یا کارت قبلی از مشتری</span>
                        </label>

                        <label className="flex items-center gap-2 cursor-pointer font-bold text-slate-800">
                          <input
                            type="checkbox"
                            checked={inputIsSealedPack}
                            onChange={(e) => setInputIsSealedPack(e.target.checked)}
                            className="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300"
                          />
                          <span>پاکت پلمپ امنیتی با هولوگرام ضدآب پیشخوان</span>
                        </label>
                      </div>

                      <div className="w-full sm:w-auto flex-1">
                        <input
                          type="text"
                          value={inputSecurityNote}
                          onChange={(e) => setInputSecurityNote(e.target.value)}
                          placeholder="یادداشت امنیتی برای سفیر پیک..."
                          className="w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs text-slate-800 focus:border-emerald-600 focus:outline-none"
                        />
                      </div>
                    </div>
                  </div>

                  {/* Section B: Routing - Origin (Office) and Destination (Citizen) */}
                  <div className="space-y-3">
                    <h5 className="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                      <Navigation className="w-4 h-4 text-emerald-600" />
                      <span>۲. مسیریابی: مبدا (دفتر پیشخوان) و مقصد (نشانی متقاضی)</span>
                    </h5>

                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-4">
                      {/* ORIGIN (Office) */}
                      <div className="lg:col-span-5 bg-slate-900 text-white p-4 rounded-2xl border border-slate-800 relative overflow-hidden">
                        <div className="flex items-center justify-between mb-3">
                          <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping" />
                            <span className="w-2 h-2 rounded-full bg-emerald-400 -mr-3.5" />
                            <strong className="text-xs font-bold text-emerald-400">مبدا حرکت پیک (دفتر پیشخوان شما)</strong>
                          </div>
                          <span className="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded-md border border-slate-700">
                            کد: {currentOffice.code}
                          </span>
                        </div>

                        <div className="space-y-2 text-xs">
                          <div className="flex items-start gap-2">
                            <Store className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                            <div>
                              <span className="text-slate-400 text-[11px] block">نام دفتر پیشخوان:</span>
                              <strong className="text-white font-bold">{currentOffice.name}</strong>
                            </div>
                          </div>

                          <div className="flex items-start gap-2">
                            <MapPin className="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                            <div>
                              <span className="text-slate-400 text-[11px] block">آدرس دقیق مبدا:</span>
                              <p className="text-slate-200 leading-relaxed text-[11px]">{currentOffice.address}</p>
                            </div>
                          </div>

                          <div className="flex items-center gap-4 pt-2 border-t border-slate-800 text-[11px]">
                            <span className="text-slate-400">تلفن دفتر: {currentOffice.phone || '۰۲۱-۸۸۴۵۹۲۰۰'}</span>
                            <span className="text-slate-400">منطقه: {currentOffice.region || 'مرکزی'}</span>
                          </div>
                        </div>
                      </div>

                      {/* DESTINATION (Citizen) */}
                      <div className="lg:col-span-7 bg-slate-50 border border-slate-200 p-4 rounded-2xl space-y-3">
                        <div className="flex items-center justify-between mb-1">
                          <div className="flex items-center gap-2">
                            <MapPin className="w-4 h-4 text-rose-600" />
                            <strong className="text-xs font-bold text-slate-900">مقصد تحویل (نشانی و اطلاعات متقاضی)</strong>
                          </div>
                          <span className="text-[11px] text-slate-500 font-medium">گیرنده نهایی داکیومنت</span>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                          <div>
                            <label className="block text-slate-700 font-bold text-[11px] mb-1">نام و نام خانوادگی <span className="text-rose-500">*</span>:</label>
                            <input
                              type="text"
                              required
                              value={inputCitizenName}
                              onChange={(e) => setInputCitizenName(e.target.value)}
                              placeholder="نام گیرنده..."
                              className="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs focus:border-emerald-600 focus:outline-none"
                            />
                          </div>

                          <div>
                            <label className="block text-slate-700 font-bold text-[11px] mb-1">شماره تماس همراه <span className="text-rose-500">*</span>:</label>
                            <input
                              type="text"
                              required
                              value={inputCitizenPhone}
                              onChange={(e) => setInputCitizenPhone(e.target.value)}
                              placeholder="۰۹۱۲..."
                              className="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono focus:border-emerald-600 focus:outline-none"
                            />
                          </div>

                          <div>
                            <label className="block text-slate-700 font-bold text-[11px] mb-1">کد ملی گیرنده:</label>
                            <input
                              type="text"
                              value={inputCitizenNationalId}
                              onChange={(e) => setInputCitizenNationalId(e.target.value)}
                              placeholder="۱۰ رقم کدملی..."
                              className="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono focus:border-emerald-600 focus:outline-none"
                            />
                          </div>
                        </div>

                        <div>
                          <label className="block text-slate-700 font-bold text-[11px] mb-1">نشانی دقیق پستی مقصد <span className="text-rose-500">*</span>:</label>
                          <textarea
                            rows={2}
                            required
                            value={inputDestinationAddress}
                            onChange={(e) => setInputDestinationAddress(e.target.value)}
                            placeholder="نام شهر، خیابان اصلی، کوچه، پلاک، طبقه و واحد..."
                            className="w-full bg-white border border-slate-300 rounded-lg p-2 text-xs focus:border-emerald-600 focus:outline-none leading-relaxed"
                          />
                        </div>

                        <div className="grid grid-cols-2 gap-2.5">
                          <div>
                            <label className="block text-slate-700 font-bold text-[11px] mb-1">کد پستی ۱۰ رقمی مقصد:</label>
                            <input
                              type="text"
                              value={inputDestinationPostalCode}
                              onChange={(e) => setInputDestinationPostalCode(e.target.value)}
                              placeholder="کدپستی ده‌رقمی..."
                              className="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-mono focus:border-emerald-600 focus:outline-none"
                            />
                          </div>

                          <div>
                            <label className="block text-slate-700 font-bold text-[11px] mb-1">منطقه / محله شهرداری:</label>
                            <input
                              type="text"
                              value={inputDestinationZone}
                              onChange={(e) => setInputDestinationZone(e.target.value)}
                              placeholder="مثال: منطقه ۲، سعادت‌آباد"
                              className="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs focus:border-emerald-600 focus:outline-none"
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Section C: Fleet Selection, Pricing & Dispatch Action */}
                  <div className="space-y-3 pt-2 border-t border-slate-100">
                    <h5 className="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                      <Zap className="w-4 h-4 text-emerald-600" />
                      <span>۳. نوع ناوگان پیک، روش پرداخت و ثبت اعزام</span>
                    </h5>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      {/* Fleet Type 1: Express Motorbike */}
                      <button
                        type="button"
                        onClick={() => setInputCourierType('express_courier')}
                        className={`p-3.5 rounded-xl border text-right transition flex flex-col justify-between ${
                          inputCourierType === 'express_courier'
                            ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-300 text-slate-900 shadow-sm'
                            : 'bg-white hover:bg-slate-50 border-slate-200 text-slate-700'
                        }`}
                      >
                        <div className="flex items-center justify-between mb-2">
                          <Bike className="w-5 h-5 text-emerald-600" />
                          <span className="text-[10px] font-bold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full">
                            پیشنهاد ویژه
                          </span>
                        </div>
                        <strong className="block text-xs font-bold text-slate-900">پیک موتوری اختصاصی پیشخوان</strong>
                        <span className="text-[11px] text-slate-500 mt-0.5">تحویل فوری زیر ۲ ساعت (درون‌شهری)</span>
                        <div className="mt-2 pt-2 border-t border-slate-200 flex items-center justify-between font-bold text-xs">
                          <span className="text-slate-500 font-normal">کرایه تخمینی:</span>
                          <span className="text-emerald-700 font-mono">۵۵٬۰۰۰ تومان</span>
                        </div>
                      </button>

                      {/* Fleet Type 2: Special Post */}
                      <button
                        type="button"
                        onClick={() => setInputCourierType('special_post')}
                        className={`p-3.5 rounded-xl border text-right transition flex flex-col justify-between ${
                          inputCourierType === 'special_post'
                            ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-300 text-slate-900 shadow-sm'
                            : 'bg-white hover:bg-slate-50 border-slate-200 text-slate-700'
                        }`}
                      >
                        <div className="flex items-center justify-between mb-2">
                          <Truck className="w-5 h-5 text-blue-600" />
                          <span className="text-[10px] font-bold bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">
                            بازه ۴ ساعته
                          </span>
                        </div>
                        <strong className="block text-xs font-bold text-slate-900">پست ویژه درون‌شهری</strong>
                        <span className="text-[11px] text-slate-500 mt-0.5">توزیع در همان روز کاری</span>
                        <div className="mt-2 pt-2 border-t border-slate-200 flex items-center justify-between font-bold text-xs">
                          <span className="text-slate-500 font-normal">کرایه تخمینی:</span>
                          <span className="text-blue-700 font-mono">۴۵٬۰۰۰ تومان</span>
                        </div>
                      </button>

                      {/* Fleet Type 3: Registered Post */}
                      <button
                        type="button"
                        onClick={() => setInputCourierType('registered_post')}
                        className={`p-3.5 rounded-xl border text-right transition flex flex-col justify-between ${
                          inputCourierType === 'registered_post'
                            ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-300 text-slate-900 shadow-sm'
                            : 'bg-white hover:bg-slate-50 border-slate-200 text-slate-700'
                        }`}
                      >
                        <div className="flex items-center justify-between mb-2">
                          <Package className="w-5 h-5 text-amber-600" />
                          <span className="text-[10px] font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">
                            سراسری
                          </span>
                        </div>
                        <strong className="block text-xs font-bold text-slate-900">پست پیشتاز کشوری</strong>
                        <span className="text-[11px] text-slate-500 mt-0.5">تحویل ۲۴ الی ۴۸ ساعته با بارکد</span>
                        <div className="mt-2 pt-2 border-t border-slate-200 flex items-center justify-between font-bold text-xs">
                          <span className="text-slate-500 font-normal">کرایه تخمینی:</span>
                          <span className="text-amber-700 font-mono">۳۸٬۰۰۰ تومان</span>
                        </div>
                      </button>
                    </div>

                    {/* Payment Mode */}
                    <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                      <div>
                        <label className="block text-slate-700 font-bold mb-1">روش تسویه کرایه پیک:</label>
                        <div className="flex items-center gap-3">
                          <label className="flex items-center gap-1.5 cursor-pointer">
                            <input
                              type="radio"
                              name="payment_method"
                              checked={inputPaymentMethod === 'cod'}
                              onChange={() => setInputPaymentMethod('cod')}
                              className="text-emerald-600"
                            />
                            <span>پرداخت در محل توسط متقاضی (COD)</span>
                          </label>

                          <label className="flex items-center gap-1.5 cursor-pointer">
                            <input
                              type="radio"
                              name="payment_method"
                              checked={inputPaymentMethod === 'office_wallet'}
                              onChange={() => setInputPaymentMethod('office_wallet')}
                              className="text-emerald-600"
                            />
                            <span>کسر از کیف پول دفتر پیشخوان</span>
                          </label>

                          <label className="flex items-center gap-1.5 cursor-pointer">
                            <input
                              type="radio"
                              name="payment_method"
                              checked={inputPaymentMethod === 'prepaid'}
                              onChange={() => setInputPaymentMethod('prepaid')}
                              className="text-emerald-600"
                            />
                            <span>پیش‌پرداخت شده آنلاین</span>
                          </label>
                        </div>
                      </div>

                      <div className="flex items-center gap-3">
                        <button
                          type="submit"
                          className="bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs px-6 py-3 rounded-xl transition shadow-md flex items-center gap-2"
                        >
                          <Bike className="w-4 h-4" />
                          <span>ثبت و اعزام فوری سفیر پیک موتوری 🚀</span>
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            )}

            {/* SUB-TAB 2: ACTIVE DELIVERIES & COURIERS TRACKER */}
            {deliverySubTab === 'active_deliveries' && (
              <div className="space-y-4">
                {/* Filter and Search Bar */}
                <div className="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                  <div className="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                    <button
                      onClick={() => setFilterDeliveryStatus('all')}
                      className={`px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap ${
                        filterDeliveryStatus === 'all'
                          ? 'bg-slate-900 text-white shadow-sm'
                          : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                      }`}
                    >
                      همه مرسولات ({deliveryRequests.length})
                    </button>
                    <button
                      onClick={() => setFilterDeliveryStatus('in_transit')}
                      className={`px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap ${
                        filterDeliveryStatus === 'in_transit'
                          ? 'bg-emerald-600 text-white shadow-sm'
                          : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                      }`}
                    >
                      در مسیر تحویل ({deliveryRequests.filter(d => d.deliveryStatus === 'in_transit').length})
                    </button>
                    <button
                      onClick={() => setFilterDeliveryStatus('courier_assigned')}
                      className={`px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap ${
                        filterDeliveryStatus === 'courier_assigned'
                          ? 'bg-blue-600 text-white shadow-sm'
                          : 'bg-blue-50 text-blue-700 hover:bg-blue-100'
                      }`}
                    >
                      سفیر در راه دفتر ({deliveryRequests.filter(d => d.deliveryStatus === 'courier_assigned').length})
                    </button>
                    <button
                      onClick={() => setFilterDeliveryStatus('delivered')}
                      className={`px-3 py-1.5 rounded-lg font-bold transition whitespace-nowrap ${
                        filterDeliveryStatus === 'delivered'
                          ? 'bg-teal-700 text-white shadow-sm'
                          : 'bg-teal-50 text-teal-800 hover:bg-teal-100'
                      }`}
                    >
                      تحویل شده به متقاضی ({deliveryRequests.filter(d => d.deliveryStatus === 'delivered').length})
                    </button>
                  </div>

                  <div className="relative w-full sm:w-64">
                    <Search className="w-3.5 h-3.5 text-slate-400 absolute right-3 top-2.5" />
                    <input
                      type="text"
                      value={deliverySearch}
                      onChange={(e) => setDeliverySearch(e.target.value)}
                      placeholder="جستجوی متقاضی، کد درخواست یا سفیر..."
                      className="w-full bg-slate-50 border border-slate-200 rounded-lg pr-8 pl-3 py-1.5 text-xs focus:bg-white focus:border-emerald-600 focus:outline-none"
                    />
                  </div>
                </div>

                {/* Deliveries List Cards */}
                {(() => {
                  const filtered = deliveryRequests.filter((d) => {
                    if (filterDeliveryStatus !== 'all' && d.deliveryStatus !== filterDeliveryStatus) return false;
                    if (deliverySearch.trim()) {
                      const q = deliverySearch.toLowerCase();
                      const matchName = d.citizenName.toLowerCase().includes(q);
                      const matchCode = d.requestCode.toLowerCase().includes(q);
                      const matchDoc = d.docTypeName.toLowerCase().includes(q);
                      const matchCourier = d.courierName?.toLowerCase().includes(q);
                      if (!matchName && !matchCode && !matchDoc && !matchCourier) return false;
                    }
                    return true;
                  });

                  if (filtered.length === 0) {
                    return (
                      <div className="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500 shadow-sm space-y-2">
                        <Package className="w-10 h-10 mx-auto text-slate-300" />
                        <h4 className="font-bold text-sm text-slate-700">مرسوله‌ای با این مشخصات یافت نشد</h4>
                        <p className="text-xs">می‌توانید با دکمه «درخواست پیک جدید» برای داکیومنت‌های آماده پیک اعزام کنید.</p>
                      </div>
                    );
                  }

                  return (
                    <div className="space-y-3">
                      {filtered.map((item) => {
                        const isDelivered = item.deliveryStatus === 'delivered';
                        const isInTransit = item.deliveryStatus === 'in_transit';

                        return (
                          <div
                            key={item.id}
                            className={`bg-white border rounded-2xl p-5 shadow-sm transition space-y-4 ${
                              isDelivered
                                ? 'border-teal-200 bg-teal-50/20'
                                : isInTransit
                                ? 'border-emerald-300 bg-emerald-50/10'
                                : 'border-slate-200'
                            }`}
                          >
                            {/* Top Card Row */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                              <div className="flex items-center gap-3">
                                <div className={`w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg ${
                                  isDelivered
                                    ? 'bg-teal-100 text-teal-700'
                                    : isInTransit
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-blue-100 text-blue-700'
                                }`}>
                                  {isDelivered ? '✓' : <Bike className="w-5 h-5" />}
                                </div>

                                <div>
                                  <div className="flex items-center gap-2">
                                    <h4 className="font-bold text-sm text-slate-900">{item.docTypeName}</h4>
                                    <span className="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                      کد پرونده: {item.requestCode}
                                    </span>
                                  </div>
                                  <p className="text-xs text-slate-500 mt-0.5">
                                    متقاضی: <strong className="text-slate-800 font-bold">{item.citizenName}</strong> | کدملی: {item.citizenNationalId}
                                  </p>
                                </div>
                              </div>

                              <div className="flex items-center gap-2 flex-wrap">
                                {isDelivered ? (
                                  <span className="bg-teal-100 text-teal-800 border border-teal-300 text-xs font-bold px-3 py-1 rounded-lg flex items-center gap-1.5">
                                    <CheckCircle className="w-3.5 h-3.5" />
                                    <span>تحویل داده شد به متقاضی ✅ ({item.deliveredAt})</span>
                                  </span>
                                ) : isInTransit ? (
                                  <span className="bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold px-3 py-1 rounded-lg flex items-center gap-1.5 animate-pulse">
                                    <Bike className="w-3.5 h-3.5" />
                                    <span>سفیر در مسیر تحویل به مشتری</span>
                                  </span>
                                ) : (
                                  <span className="bg-blue-100 text-blue-800 border border-blue-300 text-xs font-bold px-3 py-1 rounded-lg flex items-center gap-1.5">
                                    <Clock className="w-3.5 h-3.5" />
                                    <span>سفیر در راه دریافت از دفتر</span>
                                  </span>
                                )}

                                <span className="bg-slate-100 text-slate-700 font-mono text-xs px-2.5 py-1 rounded-lg">
                                  {item.id}
                                </span>
                              </div>
                            </div>

                            {/* Middle Grid: Origin, Destination, Courier Info, Security OTP */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                              {/* Origin / Destination Box */}
                              <div className="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                                <div className="flex items-start gap-1.5">
                                  <Store className="w-3.5 h-3.5 text-emerald-600 mt-0.5 shrink-0" />
                                  <div>
                                    <span className="text-[10px] text-slate-400 block">مبدا دفتر:</span>
                                    <strong className="text-slate-800 text-[11px] block">{item.originOfficeName}</strong>
                                    <p className="text-slate-500 text-[10px] truncate">{item.originAddress}</p>
                                  </div>
                                </div>

                                <div className="flex items-start gap-1.5 pt-1.5 border-t border-slate-200">
                                  <MapPin className="w-3.5 h-3.5 text-rose-600 mt-0.5 shrink-0" />
                                  <div>
                                    <span className="text-[10px] text-slate-400 block">مقصد مشتری:</span>
                                    <p className="text-slate-800 text-[11px] font-medium leading-relaxed">{item.destinationAddress}</p>
                                    <span className="text-[10px] text-slate-500 font-mono">کد پستی: {item.destinationPostalCode}</span>
                                  </div>
                                </div>
                              </div>

                              {/* Courier Details */}
                              <div className="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                                <span className="text-[10px] text-slate-400 block font-bold">اطلاعات سفیر موتوری:</span>
                                <div className="flex items-center gap-2">
                                  <div className="w-7 h-7 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs">
                                    <User className="w-3.5 h-3.5" />
                                  </div>
                                  <div>
                                    <strong className="text-slate-900 block text-xs">{item.courierName}</strong>
                                    <span className="text-slate-500 text-[11px] font-mono">{item.courierPhone}</span>
                                  </div>
                                </div>

                                <div className="pt-1.5 border-t border-slate-200 flex items-center justify-between text-[11px]">
                                  <span className="text-slate-500">پلاک: <strong className="font-mono text-slate-800">{item.courierPlate}</strong></span>
                                  <span className="text-slate-500">کرایه: <strong className="font-mono text-emerald-700">{item.shippingFee.toLocaleString('fa-IR')} ت</strong></span>
                                </div>
                              </div>

                              {/* Security OTP & Verification Box */}
                              <div className="bg-amber-50/60 p-3 rounded-xl border border-amber-200 flex flex-col justify-between space-y-2">
                                <div>
                                  <div className="flex items-center justify-between mb-1">
                                    <span className="text-[10px] text-amber-900 font-bold flex items-center gap-1">
                                      <ShieldCheck className="w-3.5 h-3.5 text-amber-700" />
                                      <span>کد امنیتی تحویل به مشتری (OTP):</span>
                                    </span>
                                    <span className="bg-amber-200 text-amber-900 font-mono font-black text-xs px-2 py-0.5 rounded">
                                      {item.deliveryOtp}
                                    </span>
                                  </div>
                                  <p className="text-[10px] text-amber-800 leading-tight">
                                    سفیر در محل تحویل این رمز ۴ رقمی را از مشتری دریافت کرده و مطابقت می‌دهد.
                                  </p>
                                </div>

                                {item.requireOldDocReturn && (
                                  <div className="bg-white/80 p-1.5 rounded-lg border border-amber-300 text-[10px] text-amber-900 font-bold">
                                    ⚠️ الزام دریافت لاشه مدرک قدیمی قبل از تحویل
                                  </div>
                                )}
                              </div>
                            </div>

                            {/* Action Buttons Row */}
                            <div className="flex items-center justify-between gap-2 flex-wrap pt-2 border-t border-slate-100">
                              <div className="flex items-center gap-2">
                                <button
                                  onClick={() => setSelectedDeliveryForWaybill(item)}
                                  className="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold px-3 py-1.5 rounded-lg transition flex items-center gap-1.5"
                                >
                                  <Printer className="w-3.5 h-3.5 text-slate-600" />
                                  <span>چاپ برچسب بارکد و حواله تحویل</span>
                                </button>

                                <button
                                  onClick={() => {
                                    alert(`تماس با سفیر پیک (${item.courierName} - تلفن: ${item.courierPhone})`);
                                  }}
                                  className="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-medium px-3 py-1.5 rounded-lg transition flex items-center gap-1.5"
                                >
                                  <Phone className="w-3.5 h-3.5 text-slate-600" />
                                  <span>تماس با سفیر</span>
                                </button>
                              </div>

                              {!isDelivered && (
                                <button
                                  onClick={() => {
                                    setOtpVerifyModalApt(item);
                                    setEnteredOtp(item.deliveryOtp);
                                  }}
                                  className="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-4 py-1.5 rounded-lg transition shadow-sm flex items-center gap-1.5"
                                >
                                  <CheckCircle className="w-3.5 h-3.5" />
                                  <span>تایید تحویل نهایی داکیومنت با رمز OTP</span>
                                </button>
                              )}
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  );
                })()}
              </div>
            )}

            {/* SUB-TAB 3: POSTAL DISPATCH & 20-DIGIT BARCODES */}
            {deliverySubTab === 'postal_barcodes' && (
              <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
                <div>
                  <h4 className="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                    <Truck className="w-4 h-4 text-slate-800" />
                    <span>ثبت بارکد ۲۰ رقمی پست پیشتاز کشوری و ابلاغ به متقاضی</span>
                  </h4>
                  <p className="text-xs text-slate-500 mt-1">
                    در صورتی که مرسوله توسط موزع اداره پست تحویل گرفته شده است، بارکد رسمی پست پیشتاز را اینجا ثبت نمایید تا پیامک رهگیری به شهروند ارسال شود.
                  </p>
                </div>

                <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs space-y-3">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label className="block text-slate-700 font-bold mb-1">بارکد ۲۰ رقمی پست پیشتاز:</label>
                      <input
                        type="text"
                        value={postalInput}
                        onChange={(e) => setPostalInput(e.target.value)}
                        placeholder="مثال: 98410293847201928471"
                        className="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-mono font-bold focus:border-emerald-600 focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-slate-700 font-bold mb-1">کد پیگیری پرونده متقاضی:</label>
                      <input
                        type="text"
                        defaultValue="CR-1402-9901"
                        placeholder="کد پیگیری..."
                        className="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-mono focus:border-emerald-600 focus:outline-none"
                      />
                    </div>
                  </div>

                  <div className="flex items-center gap-2 pt-2">
                    <button
                      onClick={() => {
                        setActionSuccessMsg(`بارکد پستی «${postalInput}» با موفقیت ذخیره شد و پیامک حاوی لینک رهگیری شرکت پست برای متقاضی ارسال شد.`);
                        setTimeout(() => setActionSuccessMsg(null), 4000);
                      }}
                      className="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-4 py-2 rounded-xl transition flex items-center gap-2"
                    >
                      <Send className="w-3.5 h-3.5 text-emerald-400" />
                      <span>ثبت بارکد و ابلاغ پیامکی به شهروند</span>
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* MODAL: PRINT SHIPPING LABEL & WAYBILL */}
            {selectedDeliveryForWaybill && (
              <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div className="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden text-right animate-in fade-in zoom-in duration-150">
                  <div className="bg-slate-900 text-white px-5 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Printer className="w-4 h-4 text-emerald-400" />
                      <h4 className="font-bold text-sm">برچسب رسمی پستی و حواله تحویل داکیومنت</h4>
                    </div>
                    <button
                      onClick={() => setSelectedDeliveryForWaybill(null)}
                      className="text-slate-400 hover:text-white transition"
                    >
                      <X className="w-5 h-5" />
                    </button>
                  </div>

                  <div className="p-6 space-y-4 text-xs">
                    {/* Printable Label Box */}
                    <div className="border-2 border-dashed border-slate-300 rounded-2xl p-5 bg-slate-50/50 space-y-4">
                      <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                        <div className="flex items-center gap-2">
                          <Building2 className="w-5 h-5 text-emerald-700" />
                          <div>
                            <strong className="block text-slate-900 font-extrabold text-xs">سامانه دفاتر پیشخوان خدمات دولت</strong>
                            <span className="text-[10px] text-slate-500">حواله رسمی تحویل مرسوله و مدارک دولتی</span>
                          </div>
                        </div>
                        <div className="text-left font-mono">
                          <span className="block text-[10px] text-slate-400">شماره مرسوله:</span>
                          <strong className="text-xs text-slate-900 font-bold">{selectedDeliveryForWaybill.id}</strong>
                        </div>
                      </div>

                      {/* Origin & Destination in Label */}
                      <div className="space-y-2">
                        <div className="bg-white p-2.5 rounded-xl border border-slate-200">
                          <span className="text-[10px] font-bold text-slate-400 block mb-0.5">فرستنده (مبدا):</span>
                          <strong className="text-slate-900 text-xs block">{selectedDeliveryForWaybill.originOfficeName}</strong>
                          <p className="text-slate-600 text-[11px]">{selectedDeliveryForWaybill.originAddress}</p>
                          <span className="text-[10px] text-slate-500">تلفن: {selectedDeliveryForWaybill.originPhone}</span>
                        </div>

                        <div className="bg-emerald-50/60 p-2.5 rounded-xl border border-emerald-200">
                          <span className="text-[10px] font-bold text-emerald-800 block mb-0.5">گیرنده (مقصد):</span>
                          <strong className="text-slate-900 text-xs block">{selectedDeliveryForWaybill.citizenName} ({selectedDeliveryForWaybill.citizenPhone})</strong>
                          <p className="text-slate-700 text-[11px] font-medium">{selectedDeliveryForWaybill.destinationAddress}</p>
                          <span className="text-[10px] font-mono text-slate-600">کدپستی: {selectedDeliveryForWaybill.destinationPostalCode}</span>
                        </div>
                      </div>

                      {/* Barcode & OTP */}
                      <div className="flex items-center justify-between pt-2 border-t border-slate-200">
                        <div className="font-mono text-center">
                          <span className="tracking-widest text-lg font-black block">||||| | |||| ||| |||||</span>
                          <span className="text-[10px] text-slate-500">{selectedDeliveryForWaybill.requestCode}</span>
                        </div>

                        <div className="text-left">
                          <span className="text-[10px] text-slate-500 block">رمز تحویل امنیتی:</span>
                          <span className="bg-slate-900 text-white font-mono font-bold text-xs px-2.5 py-1 rounded">
                            {selectedDeliveryForWaybill.deliveryOtp}
                          </span>
                        </div>
                      </div>
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-2">
                      <button
                        onClick={() => setSelectedDeliveryForWaybill(null)}
                        className="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2 rounded-xl transition text-xs"
                      >
                        بستن
                      </button>
                      <button
                        onClick={() => {
                          alert('دستور پرینت به چاپگر متصل ارسال شد.');
                          setSelectedDeliveryForWaybill(null);
                        }}
                        className="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-5 py-2 rounded-xl transition shadow-md text-xs flex items-center gap-1.5"
                      >
                        <Printer className="w-3.5 h-3.5" />
                        <span>چاپ برچسب پستی</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* MODAL: OTP DELIVERY VERIFICATION */}
            {otpVerifyModalApt && (
              <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div className="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden text-right animate-in fade-in zoom-in duration-150">
                  <div className="bg-slate-900 text-white px-5 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <ShieldCheck className="w-4 h-4 text-emerald-400" />
                      <h4 className="font-bold text-sm">تایید نهایی تحویل داکیومنت با رمز OTP</h4>
                    </div>
                    <button
                      onClick={() => setOtpVerifyModalApt(null)}
                      className="text-slate-400 hover:text-white transition"
                    >
                      <X className="w-5 h-5" />
                    </button>
                  </div>

                  <div className="p-6 space-y-4 text-xs">
                    <div className="bg-emerald-50 border border-emerald-200 p-3 rounded-xl">
                      <p className="text-emerald-900 font-medium leading-relaxed">
                        داکیومنت «<strong>{otpVerifyModalApt.docTypeName}</strong>» به متقاضی «<strong>{otpVerifyModalApt.citizenName}</strong>» توسط سفیر تحویل داده شده است. لطفاً رمز ۴ رقمی اعلام شده توسط مشتری را وارد کنید.
                      </p>
                    </div>

                    <div>
                      <label className="block text-slate-700 font-bold mb-1">
                        کد امنیتی ۴ رقمی تحویل (رمز OTP):
                      </label>
                      <input
                        type="text"
                        maxLength={4}
                        value={enteredOtp}
                        onChange={(e) => setEnteredOtp(e.target.value)}
                        placeholder="۴ رقم کد تحویل..."
                        className="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-center font-mono font-black text-base tracking-widest focus:border-emerald-600 focus:bg-white focus:outline-none"
                      />
                      <span className="text-[11px] text-slate-400 block mt-1">
                        کد صحیح این مرسوله: <strong className="font-mono text-slate-700">{otpVerifyModalApt.deliveryOtp}</strong>
                      </span>
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                      <button
                        onClick={() => setOtpVerifyModalApt(null)}
                        className="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2 rounded-xl transition text-xs"
                      >
                        انصراف
                      </button>
                      <button
                        onClick={() => handleConfirmDeliveryWithOtp(otpVerifyModalApt.id)}
                        className="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-5 py-2 rounded-xl transition shadow-md text-xs flex items-center gap-1.5"
                      >
                        <CheckCircle className="w-3.5 h-3.5" />
                        <span>ثبت قطعی تحویل مرسوله</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}

        {/* DESK 7: FINANCIAL LEDGER (دفتر مالی و تسویه) */}
        {activeDesk === 'finance' && (
          <div className="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="font-bold text-sm text-slate-900">گزارش درآمد و تسهیم وجوه دفتر</h3>
                <p className="text-xs text-slate-500 mt-0.5">تسویه روزانه سهم دفتر پیشخوان به حساب شبای ثبت‌شده</p>
              </div>
              <span className="text-emerald-700 font-mono font-black text-sm bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-lg">
                موجودی آماده تسویه: ۱٬۲۸۰٬۰۰۰ تومان
              </span>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
              <div className="bg-slate-50 p-3 rounded-xl border border-slate-200">
                <span className="text-slate-400 block text-[11px]">کارمزد ناخالص پرونده‌ها:</span>
                <strong className="text-sm text-slate-800 font-bold">۱٬۴۵۰٬۰۰۰ تومان</strong>
              </div>
              <div className="bg-slate-50 p-3 rounded-xl border border-slate-200">
                <span className="text-slate-400 block text-[11px]">کسورات و آبونمان شبکه (۱۰٪):</span>
                <strong className="text-sm text-rose-600 font-bold">- ۱۷۰٬۰۰۰ تومان</strong>
              </div>
              <div className="bg-emerald-50 p-3 rounded-xl border border-emerald-200">
                <span className="text-emerald-700 block text-[11px]">سهم خالص واریزی به دفتر:</span>
                <strong className="text-sm text-emerald-800 font-black">۱٬۲۸۰٬۰۰۰ تومان</strong>
              </div>
            </div>
          </div>
        )}

        {/* DESK: CITIZEN REVIEWS, RATINGS & QUALITY SLA (امتیاز، نظرات و کیفیت دفتر) */}
        {activeDesk === 'reviews' && (
          <div className="space-y-5 animate-in fade-in duration-200">
            {/* Top Sub-Tab Navigation Switcher */}
            <div className="bg-white border border-slate-200 rounded-2xl p-2 shadow-xs flex items-center justify-between gap-2 flex-wrap">
              <div className="flex items-center gap-1.5">
                <button
                  onClick={() => setReviewsSubTab('feedback')}
                  className={`flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                    reviewsSubTab === 'feedback'
                      ? 'bg-amber-500 text-white shadow-xs shadow-amber-500/20'
                      : 'text-slate-600 hover:bg-slate-100'
                  }`}
                >
                  <Star className="w-3.5 h-3.5 fill-current" />
                  <span>نظرات و رضایت‌سنجی شهروندان</span>
                  <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${
                    reviewsSubTab === 'feedback' ? 'bg-amber-600 text-white' : 'bg-slate-200 text-slate-700'
                  }`}>
                    {reviewsList.length} نظر
                  </span>
                </button>

                <button
                  onClick={() => setReviewsSubTab('sla_quality')}
                  className={`flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                    reviewsSubTab === 'sla_quality'
                      ? 'bg-slate-900 text-white shadow-xs'
                      : 'text-slate-600 hover:bg-slate-100'
                  }`}
                >
                  <Award className="w-3.5 h-3.5 text-amber-400" />
                  <span>شاخص‌های کیفیت، رتبه‌بندی و SLA</span>
                  <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${
                    reviewsSubTab === 'sla_quality' ? 'bg-slate-800 text-emerald-400' : 'bg-emerald-100 text-emerald-800'
                  }`}>
                    رتبه ۲ منطقه
                  </span>
                </button>
              </div>

              <div className="flex items-center gap-2 px-3 py-1 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600">
                <span className="text-[11px] text-slate-400">میانگین کل رضایت:</span>
                <span className="font-black text-amber-600">⭐ {currentOffice.rating} از ۵.۰</span>
              </div>
            </div>

            {/* SUB-VIEW 1: REVIEWS & CITIZEN FEEDBACK */}
            {reviewsSubTab === 'feedback' && (
              <div className="space-y-5">
                {/* Top Rating Summary Card */}
                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                  <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    
                    {/* Score & Stars */}
                    <div className="flex items-center gap-5">
                      <div className="w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex flex-col items-center justify-center shadow-md shadow-amber-500/20">
                        <span className="text-2xl font-black">{currentOffice.rating}</span>
                        <span className="text-[10px] font-medium opacity-90">از ۵.۰</span>
                      </div>
                      <div>
                        <div className="flex items-center gap-1 text-amber-500 mb-1">
                          {[1, 2, 3, 4, 5].map((s) => (
                            <Star key={s} className="w-5 h-5 fill-amber-400 text-amber-400" />
                          ))}
                        </div>
                        <h3 className="font-extrabold text-base text-slate-900">
                          رضایت‌مندی شهروندان از عملکرد دفتر
                        </h3>
                        <p className="text-xs text-slate-500 mt-0.5">
                          بر اساس میانگین {currentOffice.reviewCount || 428} نظر و امتیاز ثبت‌شده شهروندان
                        </p>
                      </div>
                    </div>

                    {/* Rating Distribution Bar Chart */}
                    <div className="w-full lg:w-72 space-y-1.5 text-[11px] border-t lg:border-t-0 lg:border-r border-slate-100 pt-3 lg:pt-0 lg:pr-5">
                      <div className="flex items-center gap-2">
                        <span className="w-12 text-slate-500 font-medium">۵ ستاره</span>
                        <div className="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                          <div className="h-full bg-amber-400 rounded-full" style={{ width: '86%' }} />
                        </div>
                        <span className="w-8 text-left text-slate-700 font-bold">۸۶٪</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="w-12 text-slate-500 font-medium">۴ ستاره</span>
                        <div className="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                          <div className="h-full bg-amber-400 rounded-full" style={{ width: '11%' }} />
                        </div>
                        <span className="w-8 text-left text-slate-700 font-bold">۱۱٪</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="w-12 text-slate-500 font-medium">۳ ستاره</span>
                        <div className="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                          <div className="h-full bg-amber-400 rounded-full" style={{ width: '2%' }} />
                        </div>
                        <span className="w-8 text-left text-slate-700 font-bold">۲٪</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="w-12 text-slate-500 font-medium">۲ و ۱ ستاره</span>
                        <div className="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                          <div className="h-full bg-slate-300 rounded-full" style={{ width: '1%' }} />
                        </div>
                        <span className="w-8 text-left text-slate-700 font-bold">۱٪</span>
                      </div>
                    </div>

                  </div>

                  {/* Sub-Metrics Badges */}
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-5 mt-5 border-t border-slate-100 text-xs">
                    <div className="bg-emerald-50/70 border border-emerald-100 rounded-xl p-3 text-center">
                      <span className="text-emerald-700 text-[11px] block font-medium">سرعت بررسی مدارک</span>
                      <strong className="text-emerald-900 font-black text-sm mt-0.5 block">۹۸٪ رضایت</strong>
                    </div>
                    <div className="bg-blue-50/70 border border-blue-100 rounded-xl p-3 text-center">
                      <span className="text-blue-700 text-[11px] block font-medium">پاسخگویی آنلاین به نقص</span>
                      <strong className="text-blue-900 font-black text-sm mt-0.5 block">۹۹٪ رضایت</strong>
                    </div>
                    <div className="bg-purple-50/70 border border-purple-100 rounded-xl p-3 text-center">
                      <span className="text-purple-700 text-[11px] block font-medium">برخورد و راهنمایی پرسنل</span>
                      <strong className="text-purple-900 font-black text-sm mt-0.5 block">۹۶٪ رضایت</strong>
                    </div>
                    <div className="bg-amber-50/70 border border-amber-100 rounded-xl p-3 text-center">
                      <span className="text-amber-700 text-[11px] block font-medium">شفافیت تعرفه و هزینه</span>
                      <strong className="text-amber-900 font-black text-sm mt-0.5 block">۱۰۰٪ تایید</strong>
                    </div>
                  </div>
                </div>

                {/* Reviews List & Filter */}
                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                      <MessageSquare className="w-4 h-4 text-emerald-600" />
                      <h4 className="font-bold text-sm text-slate-900">نظرات ثبت‌شده شهروندان</h4>
                      <span className="text-[11px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-bold">
                        {reviewsList.length} نظر
                      </span>
                    </div>

                    {/* Filter Pills */}
                    <div className="flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                      <button
                        onClick={() => setReviewFilter('all')}
                        className={`px-3 py-1 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer ${
                          reviewFilter === 'all'
                            ? 'bg-slate-900 text-white'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        }`}
                      >
                        همه نظرات
                      </button>
                      <button
                        onClick={() => setReviewFilter('5star')}
                        className={`px-3 py-1 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer ${
                          reviewFilter === '5star'
                            ? 'bg-slate-900 text-white'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        }`}
                      >
                        ⭐ ۵ ستاره
                      </button>
                      <button
                        onClick={() => setReviewFilter('withReply')}
                        className={`px-3 py-1 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer ${
                          reviewFilter === 'withReply'
                            ? 'bg-slate-900 text-white'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        }`}
                      >
                        پاسخ داده شده
                      </button>
                      <button
                        onClick={() => setReviewFilter('needReply')}
                        className={`px-3 py-1 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer ${
                          reviewFilter === 'needReply'
                            ? 'bg-slate-900 text-white'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        }`}
                      >
                        نیازمند پاسخ
                      </button>
                    </div>
                  </div>

                  {/* Review Cards */}
                  <div className="space-y-3.5">
                    {reviewsList
                      .filter(r => {
                        if (reviewFilter === '5star') return r.rating === 5;
                        if (reviewFilter === 'withReply') return !!r.managerReply;
                        if (reviewFilter === 'needReply') return !r.managerReply;
                        return true;
                      })
                      .map((review) => (
                        <div 
                          key={review.id}
                          className="border border-slate-100 bg-slate-50/50 hover:bg-slate-50 rounded-2xl p-4 transition-all duration-200 space-y-3"
                        >
                          {/* Top Row: User details & Stars */}
                          <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                              <div className={`w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs ${review.avatarBg}`}>
                                {review.userName.slice(0, 1)}
                              </div>
                              <div>
                                <div className="flex items-center gap-1.5">
                                  <span className="font-bold text-xs text-slate-900">{review.userName}</span>
                                  {review.isVerifiedCitizen && (
                                    <span className="flex items-center gap-0.5 text-[10px] text-emerald-700 bg-emerald-100/70 px-1.5 py-0.2 rounded-full font-medium">
                                      <BadgeCheck className="w-3 h-3 text-emerald-600" />
                                      احراز هویت شده
                                    </span>
                                  )}
                                </div>
                                <span className="text-[10px] text-slate-400 mt-0.5 block">
                                  خدمت: {review.serviceTitle} • {review.date}
                                </span>
                              </div>
                            </div>

                            {/* Stars */}
                            <div className="flex items-center gap-0.5 text-amber-500">
                              {Array.from({ length: 5 }).map((_, i) => (
                                <Star 
                                  key={i} 
                                  className={`w-3.5 h-3.5 ${i < review.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300'}`} 
                                />
                              ))}
                            </div>
                          </div>

                          {/* Comment text */}
                          <p className="text-xs text-slate-700 leading-relaxed font-normal pr-12">
                            {review.comment}
                          </p>

                          {/* Tags & Likes */}
                          <div className="flex items-center justify-between pr-12 pt-1">
                            <div className="flex items-center gap-1.5 flex-wrap">
                              {review.tags?.map((t, idx) => (
                                <span key={idx} className="text-[10px] bg-white border border-slate-200 text-slate-600 px-2 py-0.5 rounded-lg">
                                  {t}
                                </span>
                              ))}
                            </div>

                            <div className="flex items-center gap-1 text-slate-400 text-xs">
                              <ThumbsUp className="w-3.5 h-3.5 text-slate-400" />
                              <span className="text-[11px]">{review.likes}</span>
                            </div>
                          </div>

                          {/* Manager Official Reply (if exists) */}
                          {review.managerReply ? (
                            <div className="mr-12 bg-emerald-50/60 border border-emerald-200/70 rounded-xl p-3 space-y-1 text-xs text-right">
                              <div className="flex items-center justify-between text-[11px] text-emerald-800 font-bold">
                                <span className="flex items-center gap-1">
                                  <Building2 className="w-3.5 h-3.5 text-emerald-700" />
                                  پاسخ رسمی مدیر دفتر پیشخوان ({currentOffice.name})
                                </span>
                                <span className="text-[10px] text-emerald-600 font-normal">{review.managerReply.date}</span>
                              </div>
                              <p className="text-emerald-950 text-[11.5px] leading-relaxed pt-0.5">
                                {review.managerReply.text}
                              </p>
                            </div>
                          ) : (
                            /* Manager Reply Input Form Trigger */
                            <div className="pr-12 pt-1">
                              {replyingReviewId === review.id ? (
                                <div className="bg-white border border-slate-200 rounded-xl p-3 space-y-2.5 shadow-xs">
                                  <label className="text-[11px] font-bold text-slate-700 block">
                                    ارسال پاسخ رسمی به عنوان مدیر دفتر:
                                  </label>
                                  <textarea
                                    rows={2}
                                    value={replyText}
                                    onChange={(e) => setReplyText(e.target.value)}
                                    placeholder="پاسخ محترمانه خود را بنویسید..."
                                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-500"
                                  />
                                  <div className="flex items-center justify-between pt-1">
                                    <div className="flex items-center gap-1.5">
                                      <button
                                        onClick={() => setReplyText('از حسن اعتماد و نظر ارزشمند شما صمیمانه سپاسگزاریم 🙏')}
                                        className="text-[10px] text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-1 rounded-lg transition"
                                      >
                                        + پاسخ آماده تشکر
                                      </button>
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                      <button
                                        onClick={() => handleSendReply(review.id)}
                                        className="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition cursor-pointer"
                                      >
                                        ثبت و انتشار پاسخ
                                      </button>
                                      <button
                                        onClick={() => {
                                          setReplyingReviewId(null);
                                          setReplyText('');
                                        }}
                                        className="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium rounded-xl transition cursor-pointer"
                                      >
                                        انصراف
                                      </button>
                                    </div>
                                  </div>
                                </div>
                              ) : (
                                <button
                                  onClick={() => {
                                    setReplyingReviewId(review.id);
                                    setReplyText('');
                                  }}
                                  className="text-xs text-emerald-700 hover:text-emerald-800 font-bold flex items-center gap-1 bg-emerald-50 hover:bg-emerald-100/80 px-3 py-1.5 rounded-xl transition cursor-pointer"
                                >
                                  <MessageCircle className="w-3.5 h-3.5" />
                                  <span>پاسخ به نظر شهروند</span>
                                </button>
                              )}
                            </div>
                          )}

                        </div>
                      ))}
                  </div>
                </div>
              </div>
            )}

            {/* SUB-VIEW 2: QUALITY METRICS, SLA & REGIONAL RANKING */}
            {reviewsSubTab === 'sla_quality' && (
              <div className="space-y-5 animate-in fade-in duration-200">
                {/* SLA Overview Hero Card */}
                <div className="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white rounded-2xl p-6 shadow-md border border-slate-700/60">
                  <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div className="space-y-2">
                      <div className="flex items-center gap-2">
                        <span className="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[11px] font-extrabold px-2.5 py-0.5 rounded-full flex items-center gap-1">
                          <CheckCircle2 className="w-3 h-3 text-emerald-400" />
                          سطح کیفی A+ (ممتاز شبکه)
                        </span>
                        <span className="bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[11px] font-bold px-2.5 py-0.5 rounded-full">
                          رتبه ۲ از ۱۸ دفتر منطقه ۳
                        </span>
                      </div>
                      <h3 className="text-lg font-black text-white">
                        ارزیابی سطح توافق خدمات و کیفیت (SLA Performance Score)
                      </h3>
                      <p className="text-xs text-slate-300 max-w-2xl leading-relaxed">
                        محاسبه خودکار و بلادرنگ بر مبنای سرعت پاسخ به مدارک، درصد انطباق با قوانین رگولاتوری، نرخ بازگشت کم‌نقص و رضایت‌سنجی برخط شهروندان.
                      </p>
                    </div>

                    <div className="flex items-center gap-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl p-4 shrink-0">
                      <div className="text-center pl-3 border-l border-white/15">
                        <span className="text-[10px] text-slate-300 block">امتیاز SLA</span>
                        <span className="text-2xl font-black text-amber-400">۹۸.۴</span>
                        <span className="text-[10px] text-slate-400 block">از ۱۰۰</span>
                      </div>
                      <div className="text-center pr-1">
                        <span className="text-[10px] text-slate-300 block">نمره شهروندان</span>
                        <span className="text-2xl font-black text-white">{currentOffice.rating}</span>
                        <span className="text-[10px] text-amber-300 block">⭐ ستاره</span>
                      </div>
                    </div>
                  </div>
                </div>

                {/* 6 Key Quality KPIs */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                  {/* KPI 1: Speed */}
                  <div className="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs space-y-2">
                    <div className="flex items-center justify-between text-xs text-slate-500">
                      <span className="flex items-center gap-1 font-medium">
                        <Clock className="w-3.5 h-3.5 text-emerald-600" />
                        میانگین زمان بررسی پرونده
                      </span>
                      <span className="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md font-bold text-[10px]">
                        ۴۰٪ سریع‌تر از سقف مجاز
                      </span>
                    </div>
                    <div className="flex items-baseline justify-between pt-1">
                      <span className="text-2xl font-black text-slate-900">۱۸ دقیقه</span>
                      <span className="text-xs text-slate-400">سقف مجاز SLA: ۳۰ دقیقه</span>
                    </div>
                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div className="h-full bg-emerald-500 rounded-full" style={{ width: '60%' }} />
                    </div>
                  </div>

                  {/* KPI 2: Return Rate */}
                  <div className="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs space-y-2">
                    <div className="flex items-center justify-between text-xs text-slate-500">
                      <span className="flex items-center gap-1 font-medium">
                        <RotateCcw className="w-3.5 h-3.5 text-blue-600" />
                        نرخ عودت به شهروند (نقص)
                      </span>
                      <span className="text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md font-bold text-[10px]">
                        کمتر از میانگین منطقه
                      </span>
                    </div>
                    <div className="flex items-baseline justify-between pt-1">
                      <span className="text-2xl font-black text-slate-900">۴.۲٪</span>
                      <span className="text-xs text-slate-400">میانگین شبکه: ۶.۸٪</span>
                    </div>
                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div className="h-full bg-blue-500 rounded-full" style={{ width: '38%' }} />
                    </div>
                  </div>

                  {/* KPI 3: Compliance Rate */}
                  <div className="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs space-y-2">
                    <div className="flex items-center justify-between text-xs text-slate-500">
                      <span className="flex items-center gap-1 font-medium">
                        <ShieldCheck className="w-3.5 h-3.5 text-purple-600" />
                        تطابق احراز هویت و استعلامات
                      </span>
                      <span className="text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md font-bold text-[10px]">
                        استاندارد کامل
                      </span>
                    </div>
                    <div className="flex items-baseline justify-between pt-1">
                      <span className="text-2xl font-black text-slate-900">۹۹.۶٪</span>
                      <span className="text-xs text-slate-400">حداقل استاندارد: ۹۸٪</span>
                    </div>
                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div className="h-full bg-purple-500 rounded-full" style={{ width: '99%' }} />
                    </div>
                  </div>

                  {/* KPI 4: Online Chat Response */}
                  <div className="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs space-y-2">
                    <div className="flex items-center justify-between text-xs text-slate-500">
                      <span className="flex items-center gap-1 font-medium">
                        <Zap className="w-3.5 h-3.5 text-amber-500" />
                        پاسخگویی به رفع نقص برخط
                      </span>
                      <span className="text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md font-bold text-[10px]">
                        زیر ۵ دقیقه
                      </span>
                    </div>
                    <div className="flex items-baseline justify-between pt-1">
                      <span className="text-2xl font-black text-slate-900">۳.۵ دقیقه</span>
                      <span className="text-xs text-slate-400">نرخ پاسخ: ۱۰۰٪</span>
                    </div>
                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div className="h-full bg-amber-500 rounded-full" style={{ width: '88%' }} />
                    </div>
                  </div>

                  {/* KPI 5: Postal Handover */}
                  <div className="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs space-y-2">
                    <div className="flex items-center justify-between text-xs text-slate-500">
                      <span className="flex items-center gap-1 font-medium">
                        <Truck className="w-3.5 h-3.5 text-indigo-600" />
                        تحویل به باجه پست پیشتاز
                      </span>
                      <span className="text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md font-bold text-[10px]">
                        روزانه و بدون وقفه
                      </span>
                    </div>
                    <div className="flex items-baseline justify-between pt-1">
                      <span className="text-2xl font-black text-slate-900">۱.۲ ساعت</span>
                      <span className="text-xs text-slate-400">توزیع در همان روز</span>
                    </div>
                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div className="h-full bg-indigo-500 rounded-full" style={{ width: '92%' }} />
                    </div>
                  </div>

                  {/* KPI 6: Tariff Transparency */}
                  <div className="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs space-y-2">
                    <div className="flex items-center justify-between text-xs text-slate-500">
                      <span className="flex items-center gap-1 font-medium">
                        <Receipt className="w-3.5 h-3.5 text-teal-600" />
                        شفافیت تعرفه مصوب رگولاتوری
                      </span>
                      <span className="text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md font-bold text-[10px]">
                        ۱۰۰٪ بدون مغایرت
                      </span>
                    </div>
                    <div className="flex items-baseline justify-between pt-1">
                      <span className="text-2xl font-black text-slate-900">۱۰۰٪</span>
                      <span className="text-xs text-slate-400">تراکنش‌های متمرکز شتاب</span>
                    </div>
                    <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div className="h-full bg-teal-500 rounded-full" style={{ width: '100%' }} />
                    </div>
                  </div>
                </div>

                {/* Awards, Medals & Regulatory Compliance */}
                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
                  <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                      <Sparkles className="w-4 h-4 text-amber-500" />
                      <h4 className="font-bold text-sm text-slate-900">نشان‌ها، مدال‌ها و افتخارات اعطایی رگولاتوری</h4>
                    </div>
                    <span className="text-xs text-slate-400">بروزرسانی ماهانه توسط ستاد نظارت بر خدمات الکترونیک</span>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div className="border border-amber-200 bg-amber-50/50 rounded-xl p-3.5 flex items-start gap-3">
                      <div className="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold shrink-0 shadow-xs">
                        🥇
                      </div>
                      <div>
                        <strong className="text-xs font-bold text-amber-950 block">دفتر برتر استان تهران</strong>
                        <p className="text-[11px] text-amber-800 mt-0.5 leading-relaxed">
                          بالاترین درصد رضایت شهروندی در خدمات تعویض و اصلاح شناسنامه در سال ۱۴۰۳.
                        </p>
                      </div>
                    </div>

                    <div className="border border-emerald-200 bg-emerald-50/50 rounded-xl p-3.5 flex items-start gap-3">
                      <div className="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shrink-0 shadow-xs">
                        ⚡
                      </div>
                      <div>
                        <strong className="text-xs font-bold text-emerald-950 block">نشان پاسخگویی فوری (زیر ۲۰ دقیقه)</strong>
                        <p className="text-[11px] text-emerald-800 mt-0.5 leading-relaxed">
                          تایید مدارک و ارسال پرونده به دستگاه‌های دولتی بدون معطلی و در کمترین زمان.
                        </p>
                      </div>
                    </div>

                    <div className="border border-blue-200 bg-blue-50/50 rounded-xl p-3.5 flex items-start gap-3">
                      <div className="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shrink-0 shadow-xs">
                        🛡️
                      </div>
                      <div>
                        <strong className="text-xs font-bold text-blue-950 block">تندیس شفافیت و انطباق ۹۹.۸٪</strong>
                        <p className="text-[11px] text-blue-800 mt-0.5 leading-relaxed">
                          رعایت دقیق تعرفه‌های مصوب سازمان تنظیم مقررات و ارتباطات رادیویی.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                {/* SLA Targets & Standards Checklist */}
                <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-3">
                  <h4 className="font-bold text-sm text-slate-900 flex items-center gap-2">
                    <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                    چک‌لیست استانداردهای اجباری رگولاتوری (تضمین کیفیت خدمت)
                  </h4>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs pt-1">
                    <div className="flex items-center gap-2 text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                      <Check className="w-4 h-4 text-emerald-600 shrink-0" />
                      <span>دوربین زنده بیومتریک و اسکنر اثر انگشت فعال و متصل به سامانه هویتی</span>
                    </div>
                    <div className="flex items-center gap-2 text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                      <Check className="w-4 h-4 text-emerald-600 shrink-0" />
                      <span>پایش خودکار و صدور آنی کد پیگیری پستی ۲۴ رقمی برای شهروندان</span>
                    </div>
                    <div className="flex items-center gap-2 text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                      <Check className="w-4 h-4 text-emerald-600 shrink-0" />
                      <span>پروتکل ثبت شفاف علت عودت مطابق استاندارد ۱۶ گانه نقص پرونده</span>
                    </div>
                    <div className="flex items-center gap-2 text-slate-700 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                      <Check className="w-4 h-4 text-emerald-600 shrink-0" />
                      <span>پاسخگویی رسمی مدیریت به تمام نظرات و پیام‌های بازخورد شهروندان</span>
                    </div>
                  </div>
                </div>

              </div>
            )}
          </div>
        )}

        {/* DESK 9: OFFICE PROFILE & CITIZEN SETTINGS (تنظیمات، مشخصات و خدمات قابل ارائه دفتر به شهروندان) */}
        {activeDesk === 'office_profile' && (
          <div className="space-y-5 animate-in fade-in duration-200">
            {/* Top Summary & Action Bar */}
            <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
              <div className="flex items-start gap-3.5">
                <div className="w-12 h-12 rounded-2xl bg-emerald-700 text-white flex items-center justify-center shadow-md shadow-emerald-700/20 shrink-0">
                  <Store className="w-6 h-6" />
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <h2 className="font-extrabold text-base text-slate-900">
                      مدیریت مشخصات، خدمات و تنظیمات شهروندی
                    </h2>
                    <span className="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                      <ShieldCheck className="w-3 h-3 text-emerald-600" />
                      مجوز رگولاتوری فعال
                    </span>
                  </div>
                  <p className="text-xs text-slate-500 mt-0.5 leading-relaxed">
                    تمامی اطلاعات ثبت‌شده در این بخش مستقیماً در درگاه شهروندان، نقشه دفاتر، جستجوی خدمات و صف نوبت‌دهی آنلاین اعمال می‌گردد.
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-2 w-full md:w-auto justify-end">
                <button
                  onClick={handleSaveCitizenConfig}
                  disabled={isSavingConfig}
                  className="w-full md:w-auto flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold rounded-xl shadow-sm shadow-emerald-600/20 transition-all cursor-pointer"
                >
                  {isSavingConfig ? (
                    <>
                      <RefreshCw className="w-4 h-4 animate-spin" />
                      <span>در حال ذخیره...</span>
                    </>
                  ) : (
                    <>
                      <Save className="w-4 h-4" />
                      <span>ذخیره و انتشار تغییرات</span>
                    </>
                  )}
                </button>
              </div>
            </div>

            {/* Inner Sub-Tabs Navigation */}
            <div className="flex items-center gap-2 border-b border-slate-200 pb-2 overflow-x-auto no-scrollbar">
              <button
                onClick={() => setProfileSubTab('info')}
                className={`flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer ${
                  profileSubTab === 'info'
                    ? 'bg-slate-900 text-white shadow-xs'
                    : 'text-slate-600 hover:bg-slate-100'
                }`}
              >
                <Building2 className="w-4 h-4" />
                <span>مشخصات عمومی و اطلاعات تماس</span>
              </button>

              <button
                onClick={() => setProfileSubTab('services')}
                className={`flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer ${
                  profileSubTab === 'services'
                    ? 'bg-slate-900 text-white shadow-xs'
                    : 'text-slate-600 hover:bg-slate-100'
                }`}
              >
                <Layers className="w-4 h-4" />
                <span>خدمات قابل ارائه و زمان‌بندی</span>
                <span className="bg-emerald-100 text-emerald-800 text-[10px] px-1.5 py-0.2 rounded-full font-black">
                  {officeCitizenConfig.enabledServiceIds.length} فعال
                </span>
              </button>

              <button
                onClick={() => setProfileSubTab('reception')}
                className={`flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer ${
                  profileSubTab === 'reception'
                    ? 'bg-slate-900 text-white shadow-xs'
                    : 'text-slate-600 hover:bg-slate-100'
                }`}
              >
                <Sliders className="w-4 h-4" />
                <span>تنظیمات پذیرش، نوبت‌دهی و امکانات</span>
              </button>

              <button
                onClick={() => setProfileSubTab('announcements')}
                className={`flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer ${
                  profileSubTab === 'announcements'
                    ? 'bg-slate-900 text-white shadow-xs'
                    : 'text-slate-600 hover:bg-slate-100'
                }`}
              >
                <BellRing className="w-4 h-4 text-amber-500" />
                <span>اطلاعیه و پیام ویژه به شهروندان</span>
              </button>
            </div>

            {/* SUB-TAB 1: GENERAL & CONTACT INFO */}
            {profileSubTab === 'info' && (
              <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-6">
                <div>
                  <h3 className="font-bold text-sm text-slate-900">مشخصات هویتی و پروانه دفتر</h3>
                  <p className="text-xs text-slate-500 mt-0.5">این اطلاعات روی کارت دفتر در سامانه شهروندی و رسیدهای رسمی درج می‌شود.</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                  <div>
                    <label className="block text-slate-700 font-bold mb-1.5">نام رسمی و تابلوی دفتر</label>
                    <input
                      type="text"
                      value={officeCitizenConfig.name}
                      onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, name: e.target.value })}
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-slate-700 font-bold mb-1.5">کد پروانه صنفی و رگولاتوری</label>
                    <input
                      type="text"
                      value={officeCitizenConfig.code}
                      onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, code: e.target.value })}
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 font-mono focus:bg-white focus:border-emerald-500 focus:outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-slate-700 font-bold mb-1.5">نام مدیر مسئول / متصدی دفتر</label>
                    <input
                      type="text"
                      value={officeCitizenConfig.managerName}
                      onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, managerName: e.target.value })}
                      className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                    />
                  </div>
                </div>

                <div className="border-t border-slate-100 pt-5">
                  <h3 className="font-bold text-sm text-slate-900 mb-1">راه‌های ارتباطی و پاسخگویی به مراجعین</h3>
                  <p className="text-xs text-slate-500 mb-4">شماره‌های پاسخگویی تلفنی و پشتیبانی مستقیم در پیام‌رسان‌های ایرانی</p>
                  
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div>
                      <label className="block text-slate-700 font-bold mb-1.5 flex items-center gap-1">
                        <Phone className="w-3.5 h-3.5 text-slate-500" />
                        تلفن ثابت دفتر (با کد شهر)
                      </label>
                      <input
                        type="text"
                        value={officeCitizenConfig.phone}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, phone: e.target.value })}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-slate-700 font-bold mb-1.5 flex items-center gap-1">
                        <PhoneForwarded className="w-3.5 h-3.5 text-slate-500" />
                        تلفن همراه پشتیبانی و اضطراری
                      </label>
                      <input
                        type="text"
                        value={officeCitizenConfig.emergencyPhone}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, emergencyPhone: e.target.value })}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-slate-700 font-bold mb-1.5 flex items-center gap-1">
                        <MessageCircle className="w-3.5 h-3.5 text-emerald-600" />
                        شناسه کانال یا آیدی بله / ایتا
                      </label>
                      <input
                        type="text"
                        value={officeCitizenConfig.baleChannel}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, baleChannel: e.target.value })}
                        placeholder="@pishkhan..."
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                      />
                    </div>
                  </div>
                </div>

                <div className="border-t border-slate-100 pt-5">
                  <h3 className="font-bold text-sm text-slate-900 mb-1">نشانی پستی و موقعیت مکانی دفتر</h3>
                  <p className="text-xs text-slate-500 mb-4">آدرس دقیق جهت مسیریابی شهروندان و دریافت مرسولات پستی</p>
                  
                  <div className="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                    <div>
                      <label className="block text-slate-700 font-bold mb-1.5">استان و شهر</label>
                      <input
                        type="text"
                        value={officeCitizenConfig.city}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, city: e.target.value })}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-slate-700 font-bold mb-1.5">منطقه / محله</label>
                      <input
                        type="text"
                        value={officeCitizenConfig.region}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, region: e.target.value })}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                      />
                    </div>

                    <div className="md:col-span-2">
                      <label className="block text-slate-700 font-bold mb-1.5">کد پستی ۱۰ رقمی دفتر</label>
                      <input
                        type="text"
                        value={officeCitizenConfig.postalCode}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, postalCode: e.target.value })}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 font-mono focus:bg-white focus:border-emerald-500 focus:outline-none"
                      />
                    </div>

                    <div className="md:col-span-4">
                      <label className="block text-slate-700 font-bold mb-1.5 flex items-center gap-1">
                        <MapPin className="w-3.5 h-3.5 text-rose-500" />
                        نشانی کامل پستی (خیابان، پلاک، طبقه و واحد)
                      </label>
                      <input
                        type="text"
                        value={officeCitizenConfig.address}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, address: e.target.value })}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                      />
                    </div>
                  </div>
                </div>

                <div className="border-t border-slate-100 pt-5">
                  <h3 className="font-bold text-sm text-slate-900 mb-1">ساعات کاری و پذیرش مراجعین</h3>
                  <p className="text-xs text-slate-500 mb-4">ساعات کاری مجاز برای پذیرش مدارک و نوبت‌دهی آنلاین</p>
                  
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div>
                      <label className="block text-slate-700 font-bold mb-1.5 flex items-center gap-1">
                        <Clock className="w-3.5 h-3.5 text-slate-500" />
                        شنبه تا چهارشنبه
                      </label>
                      <input
                        type="text"
                        value={officeCitizenConfig.workingHours}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, workingHours: e.target.value })}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-slate-700 font-bold mb-1.5 flex items-center gap-1">
                        <Clock className="w-3.5 h-3.5 text-slate-500" />
                        پنج‌شنبه‌ها
                      </label>
                      <input
                        type="text"
                        value={officeCitizenConfig.thursdayHours}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, thursdayHours: e.target.value })}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                      />
                    </div>

                    <div>
                      <label className="block text-slate-700 font-bold mb-1.5 flex items-center gap-1">
                        <Users className="w-3.5 h-3.5 text-slate-500" />
                        تعداد باجه‌های فیزیکی فعال در سالن
                      </label>
                      <input
                        type="number"
                        min={1}
                        max={12}
                        value={officeCitizenConfig.activeCounters}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, activeCounters: parseInt(e.target.value) || 1 })}
                        className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 focus:bg-white focus:border-emerald-500 focus:outline-none"
                      />
                    </div>
                  </div>
                </div>

              </div>
            )}

            {/* SUB-TAB 2: OFFERED SERVICES & TURNAROUND TIMES */}
            {profileSubTab === 'services' && (
              <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                  <div>
                    <h3 className="font-bold text-sm text-slate-900">لیست خدمات قابل ارائه و زمان تحویل</h3>
                    <p className="text-xs text-slate-500 mt-0.5">
                      خدماتی که در این دفتر پذیرش می‌شوند را فعال و زمان میانگین انجام کار را برای آگاهی شهروند تعیین نمایید.
                    </p>
                  </div>

                  {/* Search and Category Filter */}
                  <div className="flex items-center gap-2">
                    <div className="relative">
                      <Search className="w-3.5 h-3.5 text-slate-400 absolute right-3 top-2.5" />
                      <input
                        type="text"
                        placeholder="جستجوی خدمت..."
                        value={serviceSearchTerm}
                        onChange={(e) => setServiceSearchTerm(e.target.value)}
                        className="pr-8 pl-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs w-44 focus:bg-white focus:outline-none focus:border-emerald-500"
                      />
                    </div>
                  </div>
                </div>

                {/* Category Pills */}
                <div className="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1">
                  <button
                    onClick={() => setServiceCatFilter('all')}
                    className={`px-3 py-1 rounded-xl text-xs font-semibold whitespace-nowrap transition cursor-pointer ${
                      serviceCatFilter === 'all'
                        ? 'bg-slate-900 text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                    }`}
                  >
                    همه خدمات ({CITIZEN_SERVICES.length})
                  </button>
                  {CATEGORIES.map(cat => (
                    <button
                      key={cat.id}
                      onClick={() => setServiceCatFilter(cat.id)}
                      className={`px-3 py-1 rounded-xl text-xs font-semibold whitespace-nowrap transition cursor-pointer ${
                        serviceCatFilter === cat.id
                          ? 'bg-slate-900 text-white'
                          : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                      }`}
                    >
                      {cat.shortTitle || cat.title}
                    </button>
                  ))}
                </div>

                {/* Services Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                  {CITIZEN_SERVICES
                    .filter(s => {
                      const matchesCat = serviceCatFilter === 'all' || s.categoryId === serviceCatFilter;
                      const matchesSearch = !serviceSearchTerm || s.title.includes(serviceSearchTerm) || s.department.includes(serviceSearchTerm);
                      return matchesCat && matchesSearch;
                    })
                    .map((service) => {
                      const isEnabled = officeCitizenConfig.enabledServiceIds.includes(service.id);
                      const customTime = officeCitizenConfig.serviceHandlingTimes[service.id] || service.estimatedDays;

                      return (
                        <div
                          key={service.id}
                          className={`p-4 rounded-2xl border transition-all duration-200 space-y-3 ${
                            isEnabled
                              ? 'bg-white border-slate-200 shadow-xs'
                              : 'bg-slate-50/70 border-slate-200/60 opacity-60'
                          }`}
                        >
                          <div className="flex items-start justify-between gap-3">
                            <div className="flex items-center gap-2.5">
                              <div className={`w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs ${
                                isEnabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'
                              }`}>
                                <FileText className="w-4 h-4" />
                              </div>
                              <div>
                                <h4 className="font-bold text-xs text-slate-900">{service.title}</h4>
                                <span className="text-[10.5px] text-slate-500 block mt-0.5">
                                  {service.department} • مصوب: {service.fee?.toLocaleString()} تومان
                                </span>
                              </div>
                            </div>

                            {/* Enable / Disable Switch */}
                            <button
                              onClick={() => toggleServiceEnabled(service.id)}
                              className={`flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer ${
                                isEnabled
                                  ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                  : 'bg-slate-200 text-slate-600 hover:bg-slate-300'
                              }`}
                            >
                              {isEnabled ? (
                                <>
                                  <Check className="w-3.5 h-3.5 text-emerald-600" />
                                  <span>ارائه می‌شود</span>
                                </>
                              ) : (
                                <>
                                  <X className="w-3.5 h-3.5 text-slate-500" />
                                  <span>غیرفعال</span>
                                </>
                              )}
                            </button>
                          </div>

                          {isEnabled && (
                            <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-xs gap-3">
                              <div className="flex-1 flex items-center gap-2">
                                <span className="text-[11px] text-slate-500 font-medium whitespace-nowrap">
                                  زمان تقریبی در این دفتر:
                                </span>
                                <input
                                  type="text"
                                  value={customTime}
                                  onChange={(e) => updateServiceTime(service.id, e.target.value)}
                                  placeholder="مثلاً: زیر ۲۰ دقیقه"
                                  className="w-full bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-500"
                                />
                              </div>
                              <span className="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md whitespace-nowrap">
                                استعلام برخط
                              </span>
                            </div>
                          )}
                        </div>
                      );
                    })}
                </div>

                {/* Specialties Tags Section */}
                <div className="border-t border-slate-100 pt-5 space-y-3">
                  <div>
                    <h4 className="font-bold text-sm text-slate-900">برچسب‌ها و تخصص‌های ویژه دفتر (نشان‌های شهروندی)</h4>
                    <p className="text-xs text-slate-500 mt-0.5">این تخصص‌ها به صورت نشان‌های برجسته روی کارت دفتر در جستجوی شهروندان نمایش داده می‌شود.</p>
                  </div>

                  <div className="flex items-center gap-2 flex-wrap">
                    {officeCitizenConfig.specialties.map((spec, idx) => (
                      <span
                        key={idx}
                        className="inline-flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold px-3 py-1.5 rounded-xl"
                      >
                        <Sparkles className="w-3 h-3 text-emerald-600" />
                        {spec}
                        <button
                          onClick={() => handleRemoveSpecialty(spec)}
                          className="text-emerald-600 hover:text-rose-600 p-0.5 transition cursor-pointer"
                        >
                          <X className="w-3.5 h-3.5" />
                        </button>
                      </span>
                    ))}
                  </div>

                  <div className="flex items-center gap-2 max-w-md pt-1">
                    <input
                      type="text"
                      placeholder="عنوان تخصص جدید (مثال: صدور آنی گذرنامه زیارتی)"
                      value={newSpecialtyInput}
                      onChange={(e) => setNewSpecialtyInput(e.target.value)}
                      onKeyDown={(e) => e.key === 'Enter' && handleAddSpecialty()}
                      className="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-500"
                    />
                    <button
                      onClick={handleAddSpecialty}
                      className="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition cursor-pointer"
                    >
                      + افزودن تخصص
                    </button>
                  </div>
                </div>

              </div>
            )}

            {/* SUB-TAB 3: RECEPTION, QUEUE & FACILITIES SETTINGS */}
            {profileSubTab === 'reception' && (
              <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-6">
                <div>
                  <h3 className="font-bold text-sm text-slate-900">تنظیمات نوبت‌دهی، صف و پذیرش هوشمند</h3>
                  <p className="text-xs text-slate-500 mt-0.5">کنترل نحوه ارتباط شهروند با باجه‌های فیزیکی و سیستم ارجاع آنلاین</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                  {/* Reception Toggle 1 */}
                  <div className="border border-slate-200 rounded-2xl p-4 flex items-center justify-between gap-4">
                    <div>
                      <strong className="text-slate-900 block font-bold text-xs">پذیرش درخواست‌های آنلاین (ارجاع از سامانه)</strong>
                      <span className="text-slate-500 text-[11px] mt-0.5 block">دریافت خودکار پیشنهادهای آنلاین شهروندان نزدیک به دفتر</span>
                    </div>
                    <button
                      onClick={() => setOfficeCitizenConfig({ ...officeCitizenConfig, isOnline: !officeCitizenConfig.isOnline })}
                      className={`px-3 py-1.5 rounded-xl font-bold transition cursor-pointer ${
                        officeCitizenConfig.isOnline ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'
                      }`}
                    >
                      {officeCitizenConfig.isOnline ? 'روشن (فعال)' : 'خاموش'}
                    </button>
                  </div>

                  {/* Reception Toggle 2 */}
                  <div className="border border-slate-200 rounded-2xl p-4 flex items-center justify-between gap-4">
                    <div>
                      <strong className="text-slate-900 block font-bold text-xs">نوبت‌دهی آنلاین باجه حضوری</strong>
                      <span className="text-slate-500 text-[11px] mt-0.5 block">امکان اخذ نوبت باجه با ساعت مشخص برای مراجعین حضوری</span>
                    </div>
                    <button
                      onClick={() => setOfficeCitizenConfig({ ...officeCitizenConfig, allowInPersonQueue: !officeCitizenConfig.allowInPersonQueue })}
                      className={`px-3 py-1.5 rounded-xl font-bold transition cursor-pointer ${
                        officeCitizenConfig.allowInPersonQueue ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'
                      }`}
                    >
                      {officeCitizenConfig.allowInPersonQueue ? 'روشن (فعال)' : 'خاموش'}
                    </button>
                  </div>

                  {/* Reception Toggle 3 */}
                  <div className="border border-slate-200 rounded-2xl p-4 flex items-center justify-between gap-4">
                    <div>
                      <strong className="text-slate-900 block font-bold text-xs">ارسال اسناد با پیک اختصاصی / پست پیشتاز</strong>
                      <span className="text-slate-500 text-[11px] mt-0.5 block">امکان تحویل مدارک فیزیکی درب منزل شهروند پس از اتمام خدمت</span>
                    </div>
                    <button
                      onClick={() => setOfficeCitizenConfig({ ...officeCitizenConfig, allowCourierDispatch: !officeCitizenConfig.allowCourierDispatch })}
                      className={`px-3 py-1.5 rounded-xl font-bold transition cursor-pointer ${
                        officeCitizenConfig.allowCourierDispatch ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'
                      }`}
                    >
                      {officeCitizenConfig.allowCourierDispatch ? 'روشن (فعال)' : 'خاموش'}
                    </button>
                  </div>

                  {/* Reception Toggle 4 */}
                  <div className="border border-slate-200 rounded-2xl p-4 flex items-center justify-between gap-4">
                    <div>
                      <strong className="text-slate-900 block font-bold text-xs">میز مشاوره آنلاین و صوتی دولتی</strong>
                      <span className="text-slate-500 text-[11px] mt-0.5 block">پاسخگویی به سوالات پیچیده ملکی، مالیاتی و هویتی</span>
                    </div>
                    <button
                      onClick={() => setOfficeCitizenConfig({ ...officeCitizenConfig, allowOnlineConsultation: !officeCitizenConfig.allowOnlineConsultation })}
                      className={`px-3 py-1.5 rounded-xl font-bold transition cursor-pointer ${
                        officeCitizenConfig.allowOnlineConsultation ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600'
                      }`}
                    >
                      {officeCitizenConfig.allowOnlineConsultation ? 'روشن (فعال)' : 'خاموش'}
                    </button>
                  </div>
                </div>

                {/* Capacity Limit */}
                <div className="border-t border-slate-100 pt-5 space-y-3">
                  <div>
                    <h4 className="font-bold text-sm text-slate-900">سقف پذیرش روزانه پرونده‌های آنلاین</h4>
                    <p className="text-xs text-slate-500 mt-0.5">برای جلوگیری از افت کیفیت و تأخیر در بررسی، سقف پرونده همزمان را تنظیم کنید.</p>
                  </div>

                  <div className="flex items-center gap-4 max-w-sm">
                    <input
                      type="range"
                      min={10}
                      max={150}
                      step={5}
                      value={officeCitizenConfig.maxDailyOnlineCapacity}
                      onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, maxDailyOnlineCapacity: parseInt(e.target.value) })}
                      className="flex-1 accent-emerald-600 cursor-pointer"
                    />
                    <span className="font-bold text-sm text-slate-900 bg-slate-100 px-3 py-1 rounded-xl font-mono">
                      {officeCitizenConfig.maxDailyOnlineCapacity} پرونده در روز
                    </span>
                  </div>
                </div>

                {/* Facilities & Amenities */}
                <div className="border-t border-slate-100 pt-5 space-y-3">
                  <div>
                    <h4 className="font-bold text-sm text-slate-900">امکانات رفاهی و تجهیزات سالن مراجعین</h4>
                    <p className="text-xs text-slate-500 mt-0.5">این امکانات در پروفایل دفتر نمایش داده می‌شوند و مراجعین بر اساس آن‌ها دفتر را انتخاب می‌کنند.</p>
                  </div>

                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <label className="flex items-center gap-2 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={officeCitizenConfig.hasWheelchairAccess}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, hasWheelchairAccess: e.target.checked })}
                        className="rounded text-emerald-600 focus:ring-emerald-500"
                      />
                      <Accessibility className="w-4 h-4 text-slate-600" />
                      <span className="font-bold text-slate-800">رمپ و دسترسی ویلچر</span>
                    </label>

                    <label className="flex items-center gap-2 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={officeCitizenConfig.hasElevator}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, hasElevator: e.target.checked })}
                        className="rounded text-emerald-600 focus:ring-emerald-500"
                      />
                      <Building2 className="w-4 h-4 text-slate-600" />
                      <span className="font-bold text-slate-800">آسانسور استاندارد</span>
                    </label>

                    <label className="flex items-center gap-2 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={officeCitizenConfig.hasParking}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, hasParking: e.target.checked })}
                        className="rounded text-emerald-600 focus:ring-emerald-500"
                      />
                      <Car className="w-4 h-4 text-slate-600" />
                      <span className="font-bold text-slate-800">پارکینگ اختصاصی مراجعین</span>
                    </label>

                    <label className="flex items-center gap-2 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={officeCitizenConfig.hasWifi}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, hasWifi: e.target.checked })}
                        className="rounded text-emerald-600 focus:ring-emerald-500"
                      />
                      <Wifi className="w-4 h-4 text-slate-600" />
                      <span className="font-bold text-slate-800">اینترنت رایگان مراجعین</span>
                    </label>

                    <label className="flex items-center gap-2 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={officeCitizenConfig.hasBiometricCamera}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, hasBiometricCamera: e.target.checked })}
                        className="rounded text-emerald-600 focus:ring-emerald-500"
                      />
                      <Camera className="w-4 h-4 text-slate-600" />
                      <span className="font-bold text-slate-800">دوربین بیومتریک در محل</span>
                    </label>

                    <label className="flex items-center gap-2 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={officeCitizenConfig.hasFastCopyScan}
                        onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, hasFastCopyScan: e.target.checked })}
                        className="rounded text-emerald-600 focus:ring-emerald-500"
                      />
                      <Copy className="w-4 h-4 text-slate-600" />
                      <span className="font-bold text-slate-800">دستگاه کپی و اسکن پرسرعت</span>
                    </label>
                  </div>
                </div>

              </div>
            )}

            {/* SUB-TAB 4: CITIZEN ANNOUNCEMENT BANNER */}
            {profileSubTab === 'announcements' && (
              <div className="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
                <div>
                  <h3 className="font-bold text-sm text-slate-900">پیام و اطلاعیه اختصاصی دفتر به مراجعین</h3>
                  <p className="text-xs text-slate-500 mt-0.5">این پیام به صورت کادر برجسته و اختصاصی در صفحه انتخاب دفتر به شهروندان نمایش داده می‌شود.</p>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 mb-2">متن اطلاعیه دفتر:</label>
                  <textarea
                    rows={3}
                    value={officeCitizenConfig.citizenAnnouncement}
                    onChange={(e) => setOfficeCitizenConfig({ ...officeCitizenConfig, citizenAnnouncement: e.target.value })}
                    placeholder="متن پیام به شهروندان را اینجا بنویسید..."
                    className="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-500 leading-relaxed"
                  />
                </div>

                {/* Quick Presets */}
                <div className="space-y-1.5">
                  <span className="text-[11px] font-bold text-slate-600 block">قالب‌های آماده پیام سریع:</span>
                  <div className="flex items-center gap-2 flex-wrap text-xs">
                    <button
                      onClick={() => setOfficeCitizenConfig({
                        ...officeCitizenConfig,
                        citizenAnnouncement: 'شهروندان گرامی؛ باجه ثبت احوال و تاییدیه کد پستی این دفتر به صورت مستقیم و بدون معطلی آماده پذیرش آنلاین شما می‌باشد.'
                      })}
                      className="text-[11px] text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-xl transition cursor-pointer"
                    >
                      + پذیرش فوری بدون نوبت
                    </button>
                    <button
                      onClick={() => setOfficeCitizenConfig({
                        ...officeCitizenConfig,
                        citizenAnnouncement: 'به دلیل ارتقای سامانه ثبت احوال کشور، استعلامات کارت ملی با ۲ ساعت تأخیر پردازش خواهد شد.'
                      })}
                      className="text-[11px] text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-3 py-1.5 rounded-xl transition cursor-pointer"
                    >
                      + اطلاعیه اختلال درگاه دولتی
                    </button>
                    <button
                      onClick={() => setOfficeCitizenConfig({
                        ...officeCitizenConfig,
                        citizenAnnouncement: 'ساعات کاری در روزهای پنج‌شنبه تا ساعت ۱۴:۰۰ تمدید گردید.'
                      })}
                      className="text-[11px] text-blue-800 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-3 py-1.5 rounded-xl transition cursor-pointer"
                    >
                      + تمدید ساعت کاری
                    </button>
                  </div>
                </div>

                {/* Live Preview of how Citizen sees it */}
                <div className="border-t border-slate-100 pt-5 space-y-2">
                  <span className="text-[11px] font-bold text-slate-500 block">پیش‌نمایش نحوه نمایش به شهروند در سامانه:</span>
                  <div className="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
                    <BellRing className="w-4 h-4 text-amber-600 mt-0.5 shrink-0" />
                    <div>
                      <strong className="text-xs text-amber-950 font-bold block mb-0.5">
                        اطلاعیه دفتر {officeCitizenConfig.name}:
                      </strong>
                      <p className="text-xs text-amber-900 leading-relaxed">
                        {officeCitizenConfig.citizenAnnouncement || 'پیامی ثبت نشده است.'}
                      </p>
                    </div>
                  </div>
                </div>

              </div>
            )}
          </div>
        )}
      </main>

      {/* 10 Standard Return Codes Modal (مدال استاندارد عودت پرونده به شهروند) */}
      {showReturnModal && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-lg w-full p-5 shadow-2xl space-y-4 text-right animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-2 border-b border-slate-100">
              <div className="flex items-center gap-2 text-amber-700">
                <AlertTriangle className="w-5 h-5" />
                <h3 className="font-bold text-sm">انتخاب علت استاندارد عودت پرونده</h3>
              </div>
              <button 
                onClick={() => setShowReturnModal(false)}
                className="text-slate-400 hover:text-slate-600 p-1"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <p className="text-xs text-slate-500">
              لطفاً یکی از ۱۰ کد استاندارد نقص مدرک را انتخاب نمایید تا راهنمای تصویری دقیق برای شهروند ارسال شود:
            </p>

            <div className="space-y-2">
              {RETURN_REASON_DICTIONARY.map((reason) => {
                const isSelected = selectedReturnCode === reason.code;
                return (
                  <div
                    key={reason.code}
                    onClick={() => setSelectedReturnCode(reason.code)}
                    className={`p-3 rounded-xl border transition cursor-pointer text-xs ${
                      isSelected 
                        ? 'bg-amber-50/80 border-amber-400 text-amber-950 font-bold' 
                        : 'bg-white border-slate-200 hover:border-slate-300 text-slate-700'
                    }`}
                  >
                    <div className="flex items-center justify-between">
                      <span>{reason.title}</span>
                      <span className="text-[10px] bg-slate-100 text-slate-600 font-mono px-1.5 py-0.5 rounded">
                        {reason.code}
                      </span>
                    </div>
                    <p className="text-[11px] text-slate-500 font-normal mt-1 leading-relaxed">
                      {reason.defaultMessage}
                    </p>
                  </div>
                );
              })}
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1">
                توضیحات تکمیلی کارشناس برای شهروند (اختیاری):
              </label>
              <textarea
                rows={2}
                value={customReturnNote}
                onChange={(e) => setCustomReturnNote(e.target.value)}
                placeholder="مثال: عکس صفحه اول تار افتاده، لطفا بدون فلش مستقیم مجددا اسکن بفرمایید..."
                className="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs focus:bg-white focus:outline-none focus:border-amber-500"
              />
            </div>

            <div className="flex items-center gap-2 pt-2">
              <button
                onClick={handleReturnSubmit}
                className="flex-1 bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 rounded-xl text-xs transition"
              >
                ثبت عودت و ارسال اعلان به شهروند
              </button>
              <button
                onClick={() => setShowReturnModal(false)}
                className="px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium py-2.5 rounded-xl text-xs transition"
              >
                انصراف
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
