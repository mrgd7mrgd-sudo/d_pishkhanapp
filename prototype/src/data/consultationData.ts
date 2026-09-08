import { 
  ConsultationAdvisor, 
  ConsultationSpecialtyItem, 
  BusinessSubscriptionPlan, 
  ConsultationSession 
} from '../types';

export const CONSULTATION_SPECIALTIES: ConsultationSpecialtyItem[] = [
  {
    id: 'tax',
    category: 'tax',
    title: 'امور مالیاتی و مودیان',
    subtitle: 'سامانه مودیان، پایانه‌ها، تراکنش بانکی و اظهارنامه',
    iconName: 'Calculator',
    badge: 'پرتکرارترین',
    popularTopics: ['سامانه مودیان و کارپوشه', 'مالیات بر تراکنش‌های بانکی', 'اظهارنامه مشاغل و اشخاص', 'لوایح دادرسی مالیاتی']
  },
  {
    id: 'insurance_labor',
    category: 'insurance_labor',
    title: 'بیمه، بازنشستگی و اداره کار',
    subtitle: 'اختلافات کارگری، سوابق بیمه، بیمه بیکاری و مشاغل سخت',
    iconName: 'ShieldAlert',
    badge: 'فوری',
    popularTopics: ['حل اختلاف کارگر و کارفرما', 'اعتراض به سوابق نامشخص بیمه', 'برقراری بیمه بیکاری', 'مشاغل سخت و زیان‌آور']
  },
  {
    id: 'legal_registry',
    category: 'legal_registry',
    title: 'حقوقی، ثبتی و اسناد',
    subtitle: 'نقل‌وانتقال سند، انحصار وراثت، ثبت شرکت و تغییرات',
    iconName: 'Gavel',
    popularTopics: ['استعلام و سند تک‌برگ', 'گواهی انحصار وراثت و ارث', 'ثبت تاسیس شرکت و برند', 'امضای دیجیتال و توکن']
  },
  {
    id: 'tenders_permits',
    category: 'tenders_permits',
    title: 'مناقصات و درگاه ملی مجوزها',
    subtitle: 'سامانه ستاد ایران، صدور پروانه کسب و گواهی صلاحیت',
    iconName: 'FileCheck2',
    popularTopics: ['درگاه ملی مجوزها (G4B)', 'سامانه تدارکات الکترونیکی (ستاد)', 'سامانه نوین اصناف', 'گواهی صلاحیت ایمنی پیمانکاری']
  },
  {
    id: 'municipal',
    category: 'municipal',
    title: 'شهرداری و کمیسیون ماده ۱۰۰',
    subtitle: 'خلافی ساختمان، طرح تفصیلی، پایان‌کار و تغییر کاربری',
    iconName: 'Building2',
    popularTopics: ['دفاعیه کمیسیون ماده ۱۰۰', 'عوارض نوسازی و کسب‌وپیشه', 'طرح تفصیلی و تراکم', 'پایان‌کار و عدم خلاف']
  },
  {
    id: 'business_startup',
    category: 'business_startup',
    title: 'شرکت‌ها و استارتاپ‌ها',
    subtitle: 'قراردادهای تجاری، پلمپ دفاتر و تکالیف قانونی',
    iconName: 'Briefcase',
    popularTopics: ['دفاتر قانونی و پلمپ دفاتر', 'قرارداد عدم افشا (NDA)', 'ساختار سهامداری و اساسنامه', 'معافیت‌های دانش‌بنیان']
  }
];

export const MOCK_ADVISORS: ConsultationAdvisor[] = [
  {
    id: 'adv-tax-1',
    name: 'دکتر محمدرضا شایگان',
    avatar: 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=240&auto=format&fit=crop&q=80',
    title: 'مستشار و کارشناس ارشد امور مالیاتی و مودیان',
    category: 'tax',
    categoryTitle: 'امور مالیاتی و مودیان',
    credentialsBadge: 'عضو ارشد جامعه حسابداران رسمی ایران',
    licenseNumber: 'IACPA-94182',
    experienceYears: 18,
    rating: 4.95,
    reviewCount: 840,
    ratingBreakdown: {
      accuracy: 5.0,
      eloquence: 4.9,
      patience: 4.9
    },
    specialties: ['سامانه مودیان و کارپوشه جامع', 'مالیات تراکنش‌های بانکی مشکوک', 'تنظیم لایحه دفاعیه هیئت حل اختلاف', 'مالیات بر ارث و مستغلات'],
    isOnline: true,
    isVerified: true,
    bio: '۱۸ سال سابقه در حوزه دعاوی مالیاتی، ممیزی ارشد سابق و مشاور بیش از ۵۰۰ شرکت و صنف در زمینه اجرای قانون پایانه‌های فروشگاهی و سامانه مودیان.',
    consultationCount: 1260,
    pricing: {
      textChat: 95000,
      phonePerMinute: 18000,
      caseDeepReview: 480000
    },
    linkedActionServices: [
      {
        serviceId: 'srv-net-tax-portal',
        title: 'ثبت‌نام و مدیریت کارپوشه سامانه مودیان',
        description: 'انتقال سریع به سرویس ثبت رسمی در پیشخوان'
      },
      {
        serviceId: 'srv-net-tax-return',
        title: 'ثبت اظهارنامه عملکرد و مالیات مشاغل',
        description: 'ارسال الکترونیک اظهارنامه در پیشخوان'
      }
    ],
    recentReviews: [
      {
        id: 'rev-1',
        userName: 'مهندس رضوی (مدیرعامل شرکت پویا)',
        rating: 5,
        date: '۲ روز پیش',
        comment: 'با راهنمایی ایشان توانستیم جریمه سنگین عدم ارسال صورتحساب الکترونیک را کاملاً لغو کنیم. تسلط به بخشنامه‌های ۱۴۰۳ فوق‌العاده بود.',
        consultationType: 'بررسی عمیق پرونده و تنظیم لایحه'
      },
      {
        id: 'rev-2',
        userName: 'آقای کاظمی (صنف پوشاک)',
        rating: 5,
        date: 'هفته گذشته',
        comment: 'توضیحات بسیار شفاف در تماس تلفنی ۵ دقیقه‌ای در مورد تفکیک حساب تجاری و شخصی دادند.',
        consultationType: 'تماس صوتی'
      }
    ]
  },
  {
    id: 'adv-labor-1',
    name: 'استاد علی‌اکبر فروتن',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=240&auto=format&fit=crop&q=80',
    title: 'مشاور ارشد قانون کار و رئیس سابق اداره تامین اجتماعی',
    category: 'insurance_labor',
    categoryTitle: 'بیمه، بازنشستگی و اداره کار',
    credentialsBadge: 'بازنشسته ارشد سازمان تامین اجتماعی',
    licenseNumber: 'SSO-RET-4109',
    experienceYears: 22,
    rating: 4.92,
    reviewCount: 1150,
    ratingBreakdown: {
      accuracy: 4.9,
      eloquence: 5.0,
      patience: 4.9
    },
    specialties: ['دعاوی کارگر و کارفرما در هیئت‌های تشخیص', 'احیای سوابق بیمه گم‌شده و غیرهمپوشان', 'بیمه بیکاری و بازنشستگی پیش از موعد', 'مشاغل سخت و زیان‌آور'],
    isOnline: true,
    isVerified: true,
    bio: '۲۲ سال خدمت در سازمان تامین اجتماعی و هیئت‌های حل اختلاف وزارت کار. متخصص تدوین دادخواست و لوایح دفاعیه کارگاهی و حقوق پرسنلی.',
    consultationCount: 1940,
    pricing: {
      textChat: 85000,
      phonePerMinute: 15000,
      caseDeepReview: 420000
    },
    linkedActionServices: [
      {
        serviceId: 'srv-net-sso-history',
        title: 'جمع‌آوری و تجمیع سوابق بیمه تامین اجتماعی',
        description: 'درخواست رسمی تجمیع سوابق نامشخص'
      },
      {
        serviceId: 'srv-welfare-retirement',
        title: 'بررسی و صدور احکام بازنشستگی',
        description: 'اقدام پرونده بازنشستگی در باجه پیشخوان'
      }
    ],
    recentReviews: [
      {
        id: 'rev-3',
        userName: 'خانم علیزاده',
        rating: 5,
        date: 'دیروز',
        comment: 'پرونده بیمه بیکاری من که رد شده بود را با یک لایحه کوتاه و ارجاع به ماده قانون کار برطرف کردند و حکم صادر شد.',
        consultationType: 'مشاوره متنی فوری'
      }
    ]
  },
  {
    id: 'adv-legal-1',
    name: 'سرکار خانم دکتر مریم سلیمانی',
    avatar: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=240&auto=format&fit=crop&q=80',
    title: 'وکیل پایه یک دادگستری و متخصص امور ثبتی و املاک',
    category: 'legal_registry',
    categoryTitle: 'حقوقی، ثبتی و اسناد',
    credentialsBadge: 'پروانه وکالت پایه یک کانون وکلای دادگستری',
    licenseNumber: 'IBA-78219',
    experienceYears: 14,
    rating: 4.89,
    reviewCount: 620,
    ratingBreakdown: {
      accuracy: 4.9,
      eloquence: 4.9,
      patience: 4.8
    },
    specialties: ['انحصار وراثت و تحریر ترکه', 'استعلام اسناد تک‌برگ و بنچاق', 'ثبت و تغییرات شرکت‌های تجاری', 'قراردادهای مشارکت در ساخت'],
    isOnline: true,
    isVerified: true,
    bio: 'وکیل پایه یک دادگستری با بیش از ۱۴ سال سابقه در دعاوی ملکی، ثبتی، تقسیم ارث و پرونده‌های کمیسیون‌های ثبتی اسناد.',
    consultationCount: 890,
    pricing: {
      textChat: 110000,
      phonePerMinute: 22000,
      caseDeepReview: 590000
    },
    linkedActionServices: [
      {
        serviceId: 'srv-net-inheritance',
        title: 'ثبت پرونده و استعلام مالیات بر ارث و انحصار وراثت',
        description: 'پیگیری اجرایی انحصار وراثت در پیشخوان'
      },
      {
        serviceId: 'srv-net-company-reg',
        title: 'ثبت تغییرات و آگهی روزنامه رسمی شرکت',
        description: 'ثبت سیستمی تغییرات ثبتی'
      }
    ],
    recentReviews: [
      {
        id: 'rev-4',
        userName: 'آقای بهرامی',
        rating: 5,
        date: '۳ روز پیش',
        comment: 'با کمترین هزینه و در ۱۰ دقیقه ابهام تقسیم ماترک و مراحل مالیات بر ارث را کامل برای ما روشن کردند.',
        consultationType: 'تماس صوتی'
      }
    ]
  },
  {
    id: 'adv-tenders-1',
    name: 'مهندس نوید مهرآرا',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=240&auto=format&fit=crop&q=80',
    title: 'کارشناس ارشد درگاه ملی مجوزها و سامانه تدارکات ستاد',
    category: 'tenders_permits',
    categoryTitle: 'مناقصات و درگاه ملی مجوزها',
    credentialsBadge: 'مشاور تایید شده درگاه ملی مجوزهای کشور',
    licenseNumber: 'G4B-EXP-1102',
    experienceYears: 10,
    rating: 4.88,
    reviewCount: 530,
    ratingBreakdown: {
      accuracy: 4.9,
      eloquence: 4.8,
      patience: 5.0
    },
    specialties: ['رفع نقص و تسریع صدور پروانه کسب در G4B', 'ضمانت‌نامه‌های بانکی و اسناد مناقصات ستاد', 'اخذ رتبه و گواهی صلاحیت پیمانکاری', 'سامانه نوین اصناف و سامح'],
    isOnline: false,
    isVerified: true,
    bio: 'مشاور تخصصی پیاده‌سازی و اتصال به درگاه ملی مجوزها (G4B) و سامانه تدارکات الکترونیکی دولت (ستاد). تسهیل‌گر صدور صدها پروانه صنفی و صنعتی.',
    consultationCount: 760,
    pricing: {
      textChat: 90000,
      phonePerMinute: 16000,
      caseDeepReview: 450000
    },
    linkedActionServices: [
      {
        serviceId: 'srv-net-g4b-license',
        title: 'ثبت و پیگیری درگاه ملی مجوزها (G4B)',
        description: 'اتصال مستقیم به سامانه صدور پروانه'
      },
      {
        serviceId: 'srv-net-setad',
        title: 'ثبت‌نام در سامانه تدارکات الکترونیکی دولت (ستاد)',
        description: 'اخذ توکن و ثبت نام مناقصات'
      }
    ]
  },
  {
    id: 'adv-muni-1',
    name: 'دکتر هادی فلاحی',
    avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=240&auto=format&fit=crop&q=80',
    title: 'کارشناس رسمی دادگستری در امور شهرداری و کمیسیون ماده ۱۰۰',
    category: 'municipal',
    categoryTitle: 'شهرداری و کمیسیون ماده ۱۰۰',
    credentialsBadge: 'کارشناس رسمی دادگستری (امور ثبتی و ساختمانی)',
    licenseNumber: 'EXP-COURT-5510',
    experienceYears: 16,
    rating: 4.96,
    reviewCount: 710,
    ratingBreakdown: {
      accuracy: 5.0,
      eloquence: 4.9,
      patience: 4.9
    },
    specialties: ['لایحه دفاعیه تخریب و قلع بنا کمیسیون ماده ۱۰۰', 'عوارض ارزش افزوده و تراکم ساختمانی', 'طرح تفصیلی و تغییر کاربری مجاز', 'اخذ پایان‌کار و عدم خلاف املاک تجاری'],
    isOnline: true,
    isVerified: true,
    bio: '۱۶ سال فعالیت در پرونده‌های کلان شهرداری‌های مناطق تهران و کلان‌شهرها، مشاور ارشد دفاعیات ماده ۱۰۰ و احقاق حقوق مالکین.',
    consultationCount: 1020,
    pricing: {
      textChat: 120000,
      phonePerMinute: 24000,
      caseDeepReview: 650000
    },
    linkedActionServices: [
      {
        serviceId: 'srv-net-khodro',
        title: 'استعلام طرح تفصیلی و عوارض نوسازی',
        description: 'ثبت استعلام رسمی در دفاتر پیشخوان'
      }
    ]
  },
  {
    id: 'adv-tax-2',
    name: 'سرکار خانم فاطمه دادخواه',
    avatar: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=240&auto=format&fit=crop&q=80',
    title: 'حسابدار رسمی و مشاور مالیاتی شرکت‌های دانش‌بنیان',
    category: 'tax',
    categoryTitle: 'امور مالیاتی و مودیان',
    credentialsBadge: 'عضو جامعه مشاوران رسمی مالیاتی ایران',
    licenseNumber: 'ITA-9941',
    experienceYears: 12,
    rating: 4.91,
    reviewCount: 490,
    ratingBreakdown: {
      accuracy: 4.9,
      eloquence: 5.0,
      patience: 4.9
    },
    specialties: ['مالیات بر ارزش افزوده و گزارشات فصلی (ماده ۱۶۹)', 'سامانه مودیان اصناف طلا و آهن و پوشاک', 'پلمپ دفاتر و اظهارنامه عملکرد', 'معافیت‌های صادراتی و استارتاپی'],
    isOnline: true,
    isVerified: true,
    bio: 'متخصص حل گره‌های کارپوشه سامانه مودیان، تنظیم دفاتر و صورت‌های مالی قانونی و لوایح بخشودگی جرایم مالیاتی.',
    consultationCount: 730,
    pricing: {
      textChat: 90000,
      phonePerMinute: 17000,
      caseDeepReview: 460000
    },
    linkedActionServices: [
      {
        serviceId: 'srv-net-tax-books',
        title: 'درخواست پلمپ دفاتر تجاری و قانونی',
        description: 'سفارش آنلاین پلمپ دفاتر پیشخوان'
      }
    ]
  }
];

export const BUSINESS_SUBSCRIPTION_PLANS: BusinessSubscriptionPlan[] = [
  {
    id: 'plan-guild-basic',
    title: 'اشتراک پایه اصناف و کسب‌وکارهای خرد',
    badge: 'ویژه فروشگاه‌ها و اصناف',
    isPopular: false,
    priceMonthly: 490000,
    periodText: 'ماهانه',
    targetAudience: 'مغازه‌داران، خرده‌فروشان، اصناف پوشاک، رستوران‌ها و خدمات',
    features: [
      'بررسی ماهانه کارپوشه سامانه مودیان و صورتحساب‌های الکترونیکی',
      '۴۵ دقیقه مکالمه تلفنی امن با مشاور اختصاصی مالیاتی و بیمه در ماه',
      'مشاوره متنی نامحدود جهت رفع خطاهای سامانه‌ای',
      'تنظیم ۱ لایحه اعتراضی مالیاتی یا اداره کار در ماه',
      'ارتباط مستقیم جهت انجام پرونده در دفاتر پیشخوان بدون نوبت'
    ],
    quota: {
      monthlyTaxReview: '۱ شرکت یا ۱ واحد صنفی',
      laborDisputeDefense: '۱ لایحه در ماه',
      fastPassSupport: 'اولویت VIP',
      phoneMinutes: 45
    }
  },
  {
    id: 'plan-business-pro',
    title: 'اشتراک طلایی شرکت‌ها و استارتاپ‌ها',
    badge: 'پیشنهاد پیشخوانو',
    isPopular: true,
    priceMonthly: 1290000,
    periodText: 'ماهانه',
    targetAudience: 'شرکت‌های مسئولیت محدود و سهامی، استارتاپ‌ها و شرکت‌های بازرگانی',
    features: [
      'پشتیبانی جامع مالیات، ارزش افزوده، گزارشات فصلی و سامانه مودیان',
      '۱۲۰ دقیقه مکالمه تلفنی مستقیم با وکلای پایه یک و حسابداران رسمی',
      'تنظیم تا ۳ لایحه دفاعیه مالیاتی، هیئت حل اختلاف یا ماده ۱۰۰',
      'بازبینی قراردادهای پرسنلی و چک‌لیست بیمه‌ای جهت جلوگیری از جرایم بازرسی',
      'پنل اختصاصی مدیریت پرونده‌ها و ارجاع آنی به باجه‌های پیشخوان'
    ],
    quota: {
      monthlyTaxReview: 'تا ۳ کد کارگاهی / مودیان',
      laborDisputeDefense: '۳ لایحه جامع',
      fastPassSupport: 'پاسخگویی زیر ۱۵ دقیقه',
      phoneMinutes: 120
    }
  },
  {
    id: 'plan-enterprise',
    title: 'اشتراک سازمانی و کارگاه‌های تولیدی',
    badge: 'جامع‌ترین',
    isPopular: false,
    priceMonthly: 2890000,
    periodText: 'ماهانه',
    targetAudience: 'کارخانجات، پیمانکاران عمرانی، شرکت‌های واردات/صادرات و هلدینگ‌ها',
    features: [
      'حسابرس و وکیل مقیم اختصاصی برای پیگیری تمامی امور اداری و دولتی',
      '۳۰۰ دقیقه تماس مستقیم با تیم کارشناسان ارشد',
      'بررسی نامحدود پرونده‌ها، تنظیم لوایح شورای عالی مالیاتی و دیوان عدالت',
      'پشتیبانی اسناد مناقصات ستاد، گواهی‌های صلاحیت و درگاه ملی مجوزها',
      'تخفیف ۲۰ درصدی بر روی تمامی کارمزدهای خدمات دفاتر پیشخوان کشور'
    ],
    quota: {
      monthlyTaxReview: 'نامحدود',
      laborDisputeDefense: 'نامحدود',
      fastPassSupport: 'تیم پشتیبانی اختصاصی ۲۴/۷',
      phoneMinutes: 300
    }
  }
];

export const INITIAL_CONSULTATION_SESSIONS: ConsultationSession[] = [
  {
    id: 'ses-101',
    advisorId: 'adv-tax-1',
    advisorName: 'دکتر محمدرضا شایگان',
    advisorAvatar: 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=240&auto=format&fit=crop&q=80',
    advisorTitle: 'مستشار و کارشناس ارشد امور مالیاتی و مودیان',
    categoryTitle: 'امور مالیاتی و مودیان',
    mode: 'case_review',
    status: 'completed',
    totalFee: 480000,
    createdAt: '۱۴۰۳/۰۸/۲۲ - ۱۰:۳۰',
    trackingCode: 'CNS-89410',
    durationSeconds: 1800,
    advisorVerdict: 'لایحه اعتراضی به برگ تشخیص مالیات عملکرد سال ۱۴۰۲ با استناد به ماده ۲۳۸ قانون مالیات‌های مستقیم تدوین و تحویل شد.',
    linkedServiceToExecute: {
      serviceId: 'srv-net-tax-return',
      title: 'ثبت لایحه در کارپوشه سامانه مودیان'
    }
  }
];
