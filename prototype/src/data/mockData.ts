import { 
  ServiceCategory, 
  CitizenService, 
  PishkhanOffice, 
  CitizenProfile, 
  CaseRequest, 
  WalletTransaction, 
  Appointment,
  ChatMessage,
  ReturnReasonDefinition,
  LegalDelegation
} from '../types';

import illusIdentity from '../assets/images/illus_identity_3d_1788033162106.jpg';
import illusHealth from '../assets/images/illus_health_3d_1788033174762.jpg';
import illusVehicle from '../assets/images/illus_vehicle_3d_1788033190044.jpg';
import illusWelfare from '../assets/images/illus_welfare_3d_1788033205885.jpg';
import illusGov from '../assets/images/illus_gov_3d_1788033220023.jpg';
import illusBank from '../assets/images/illus_bank_3d_1788033234556.jpg';
import illusHouse from '../assets/images/illus_house_3d_1788033248282.jpg';
import illusPost from '../assets/images/illus_post_3d_1788033261489.jpg';
import illusInternet from '../assets/images/illus_internet_3d_1788275403335.jpg';
import illusConsult from '../assets/images/illus_consult_3d_1788285048758.jpg';
import illusHeroBanner from '../assets/images/illus_hero_banner_1788033276617.jpg';

export const HERO_BANNER_IMAGE = illusHeroBanner;

export const RETURN_REASON_DICTIONARY: ReturnReasonDefinition[] = [
  {
    code: 'DOC_BLUR',
    title: 'تصویر تار / سریال ناخوانا',
    defaultMessage: 'تصویر مدرک بارگذاری‌شده تار است و شماره سریال یا مندرجات خوانا نیست. لطفاً در نور کافی و بدون لرزش مجدداً عکس بگیرید.',
    sampleImg: 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=600&q=80'
  },
  {
    code: 'DOC_CROP',
    title: 'برش ناقص / لبه‌های سند بریده شده',
    defaultMessage: 'لبه‌ها و کادر چهارگانه سند در تصویر مشخص نیست. کل سند باید کامل و بدون زاویه در کادر قرار گیرد.',
  },
  {
    code: 'DOC_EXPIRED',
    title: 'سند منقضی / بدون اعتبار زمانی',
    defaultMessage: 'تاریخ اعتبار قانونی مدرک منقضی شده است. لطفاً نسخه تمدیدشده یا تاییدیه معتبر را بارگذاری نمایید.',
  },
  {
    code: 'DOC_MISMATCH',
    title: 'مغایرت مندرجات مدرک با فرم',
    defaultMessage: 'اطلاعات واردشده در فرم با مندرجات مدرک اسکن‌شده مغایرت دارد.',
  },
  {
    code: 'DOC_MISSING',
    title: 'نقص مدرک تکمیلی الزامی',
    defaultMessage: 'مدرک الزامی این خدمت ارائه نشده است. لطفاً پیوست نمایید.',
  },
  {
    code: 'DOC_WRONG_TYPE',
    title: 'فایل یا سند نامرتبط',
    defaultMessage: 'فایل بارگذاری‌شده با عنوان مدرک درخواستی همخوانی ندارد.',
  },
  {
    code: 'FORM_INVALID',
    title: 'خطای ساختاری در فیلدهای فرم',
    defaultMessage: 'مقادیر ورودی با استانداردهای سازمانی مطابقت ندارد.',
  },
  {
    code: 'INQUIRY_MISMATCH',
    title: 'مغایرت با سامانه بالادست دولتی',
    defaultMessage: 'اطلاعات با پایگاه داده سامانه مرجع همخوانی ندارد. ابتدا نسبت به اصلاح رکورد ثبتی اقدام نمایید.',
  },
  {
    code: 'ELIGIBILITY_FAIL',
    title: 'عدم احراز شرایط قانونی خدمت',
    defaultMessage: 'شرایط قانونی دریافت این خدمت احراز نگردید.',
  },
  {
    code: 'PRESENCE_REQUIRED',
    title: 'نیاز به مراجعه حضوری و احراز بیومتریک',
    defaultMessage: 'این خدمت به دلیل الزامات هویتی نیازمند حضور شخص متقاضی جهت ثبت اثر انگشت و تطبیق چهره در باجه دفتر پیشخوان است.',
  }
];

export const CATEGORIES: ServiceCategory[] = [
  {
    id: 'consultation',
    title: 'مشاوره آنلاین دولتی و حقوقی',
    shortTitle: 'مشاوره تخصصی',
    iconName: 'Scale',
    color: 'bg-emerald-600',
    description: 'مشاوره فوری با وکلای مالیاتی، بیمه تامین اجتماعی، اداره کار و شهرداری ماده ۱۰۰',
    badge: 'آنلاین',
    serviceCount: 6,
    image: illusConsult
  },
  {
    id: 'identity',
    title: 'هویتی و ثبت احوال',
    shortTitle: 'ثبت احوال و هویت',
    iconName: 'Fingerprint',
    color: 'bg-emerald-600',
    description: 'شناسنامه، کارت ملی، تغییر نام و اسناد سجلی',
    badge: 'پرکاربرد',
    serviceCount: 4,
    image: illusIdentity
  },
  {
    id: 'vehicle',
    title: 'خدمات خودرویی و ترافیک',
    shortTitle: 'خودرو و ترافیک',
    iconName: 'Car',
    color: 'bg-blue-600',
    description: 'عوارض خودرو، خلافی، تعویض پلاک، گواهینامه و کارت سوخت',
    serviceCount: 5,
    image: illusVehicle
  },
  {
    id: 'health',
    title: 'خدمات سلامت و درمان',
    shortTitle: 'سلامت و بیمه',
    iconName: 'HeartPulse',
    color: 'bg-teal-600',
    description: 'کارت بهداشت، بیمه سلامت، نسخ و پرونده پزشکی',
    serviceCount: 3,
    image: illusHealth
  },
  {
    id: 'welfare',
    title: 'خدمات رفاهی و معیشتی',
    shortTitle: 'یارانه و رفاه',
    iconName: 'Gift',
    color: 'bg-amber-600',
    description: 'یارانه، کالابرگ الکترونیک، سهام عدالت و وام معیشتی',
    serviceCount: 3,
    image: illusWelfare
  },
  {
    id: 'government',
    title: 'خدمات دولتی و کسب‌وکار',
    shortTitle: 'کسب‌وکار و مالیات',
    iconName: 'Building2',
    color: 'bg-indigo-600',
    description: 'صندوق بازنشستگی، صدور مجوز کسب، مالیات و ثنا',
    badge: 'جدید',
    serviceCount: 4,
    image: illusGov
  },
  {
    id: 'housing',
    title: 'مسکن و شهرداری',
    shortTitle: 'مسکن و شهرداری',
    iconName: 'Home',
    color: 'bg-emerald-700',
    description: 'نهضت ملی مسکن، عوارض نوسازی، پروانه و استعلام ملک',
    serviceCount: 2,
    image: illusHouse
  },
  {
    id: 'banking',
    title: 'خدمات مالی و اعتباری',
    shortTitle: 'بانکی و سفته',
    iconName: 'CreditCard',
    color: 'bg-slate-700',
    description: 'سفته الکترونیک، اعتبارسنجی بانکی و توثیق دارایی',
    serviceCount: 2,
    image: illusBank
  },
  {
    id: 'postal',
    title: 'پست و مدارک مفقودی',
    shortTitle: 'پست و تاییدیه',
    iconName: 'Mail',
    color: 'bg-orange-600',
    description: 'رهگیری مرسولات، پست‌یافته و تاییدیه کد پستی',
    serviceCount: 1,
    image: illusPost
  },
  {
    id: 'internet',
    title: 'خدمات اینترنتی و سامانه‌ای',
    shortTitle: 'خدمات اینترنتی',
    iconName: 'Globe',
    color: 'bg-cyan-600',
    description: 'سامانه میخک، امور مالیاتی، اصناف، تامین اجتماعی، خودنویس و استعلامات برخط',
    badge: 'جامع',
    serviceCount: 49,
    image: illusInternet
  }
];


export const CITIZEN_SERVICES: CitizenService[] = [
  // هویتی
  {
    id: 'id-birth-cert',
    title: 'تعویض و صدور المثنی شناسنامه',
    categoryId: 'identity',
    tags: ['semi-online', 'in-person'],
    description: 'ثبت اطلاعات و مدارک اولیه آنلاین + مراجعه به دفتر جهت تطبیق چهره و تحویل پستی شناسنامه جدید',
    requirements: ['تصویر شناسنامه قدیمی', '۲ قطعه عکس جدید پرسنلی', 'کد پستی تایید شده'],
    estimatedDays: '۳ الی ۵ روز کاری',
    fee: 95000,
    icon: 'BookOpen',
    isPopular: true,
    department: 'سازمان ثبت احوال کشور'
  },
  {
    id: 'id-national-card',
    title: 'کارت هوشمند ملی (صدور و تمدید)',
    categoryId: 'identity',
    tags: ['in-person'],
    description: 'نوبت‌گیری آنلاین و مراجعه کامل به دفتر پیشخوان جهت اسکن اثر انگشت و بیومتریک چهره',
    requirements: ['اصل شناسنامه عکس‌دار', 'کد پستی معتبر', 'حضور شخص متقاضی'],
    estimatedDays: '۱۵ الی ۳۰ روز',
    fee: 120000,
    icon: 'IdCard',
    isPopular: true,
    department: 'سازمان ثبت احوال کشور'
  },
  {
    id: 'id-single-cert',
    title: 'گواهی تجرد و عدم سابقه ازدواج',
    categoryId: 'identity',
    tags: ['online'],
    description: 'صدور فوری گواهی تجرد با امضای الکترونیک بدون نیاز به مراجعه حضوری به دفتر',
    requirements: ['شناسنامه و کارت ملی فعال در سامانه'],
    estimatedDays: '۲۴ ساعت',
    fee: 45000,
    icon: 'FileCheck',
    isNew: true,
    department: 'سازمان ثبت احوال کشور'
  },
  {
    id: 'id-name-change',
    title: 'درخواست تغییر نام و نام خانوادگی',
    categoryId: 'identity',
    tags: ['semi-online'],
    description: 'بررسی آنلاین در کمیسیون ثبت احوال و اعلام نتیجه نهایی جهت امضا در دفتر پیشخوان',
    requirements: ['درخواست کتبی', 'استعلام سجل کیفری'],
    estimatedDays: '۱۰ الی ۲۰ روز',
    fee: 180000,
    icon: 'UserCheck',
    department: 'سازمان ثبت احوال کشور'
  },

  // سلامت
  {
    id: 'hl-health-card',
    title: 'صدور و تمدید کارت بهداشت اصناف',
    categoryId: 'health',
    tags: ['semi-online', 'online'],
    description: 'ثبت درخواست و پرداخت کارمزد به صورت آنلاین، ارسال پرونده آزمایشگاهی از طریق شبکه سلامت',
    requirements: ['گواهی آزمایش عدم اعتیاد و انگل‌شناسی', 'عکس پرسنلی'],
    estimatedDays: '۲ الی ۴ روز',
    fee: 85000,
    icon: 'ShieldCheck',
    isPopular: true,
    isNew: true,
    department: 'وزارت بهداشت، درمان و آموزش پزشکی'
  },
  {
    id: 'hl-insurance',
    title: 'صدور و تمدید دفترچه بیمه سلامت',
    categoryId: 'health',
    tags: ['online'],
    description: 'استعلام آنی و تمدید آنلاین اعتبار بیمه سلامت همگانی ایرانیان بدون مراجعه حضوری',
    requirements: ['کد ملی و شماره تلفن سرپرست خانوار'],
    estimatedDays: 'آنی (زیر ۱۰ دقیقه)',
    fee: 30000,
    icon: 'Activity',
    isPopular: true,
    department: 'سازمان بیمه سلامت ایران'
  },
  {
    id: 'hl-prescriptions',
    title: 'مشاهده پرونده و نسخ الکترونیک',
    categoryId: 'health',
    tags: ['online'],
    description: 'استعلام داروهای تجویزی پزشک، سوابق بستری و پاراکلینیک از سامانه سپاس',
    requirements: ['کد ملی و ارسال رمز یکبار مصرف امنیتی'],
    estimatedDays: 'آنی',
    fee: 0,
    icon: 'Stethoscope',
    department: 'وزارت بهداشت'
  },

  // خودرو
  {
    id: 'vh-penalties',
    title: 'استعلام و پرداخت تجمیعی خلافی خودرو',
    categoryId: 'vehicle',
    tags: ['online'],
    description: 'نمایش جزئیات ریز تخلفات به همراه عکس دوربین و تسویه آنی در شبکه راهور ناجا',
    requirements: ['شماره پلاک', 'شماره VIN خودرو', 'کد ملی مالک'],
    estimatedDays: 'آنی',
    fee: 15000,
    icon: 'Receipt',
    isPopular: true,
    department: 'پلیس راهنمایی و رانندگی فراجا'
  },
  {
    id: 'vh-toll',
    title: 'پرداخت عوارض سالیانه شهرداری و آزادراهی',
    categoryId: 'vehicle',
    tags: ['online'],
    description: 'تسویه عوارض سالیانه خودرو و تردد آزادراهی به همراه مفاصاحساب آنی',
    requirements: ['شماره پلاک و شماره شاسی'],
    estimatedDays: 'آنی',
    fee: 8000,
    icon: 'CreditCard',
    department: 'شهرداری و راهداری کشور'
  },
  {
    id: 'vh-plate-transfer',
    title: 'نوبت‌دهی و انتقال تعویض پلاک',
    categoryId: 'vehicle',
    tags: ['in-person', 'semi-online'],
    description: 'احراز هویت آنلاین خریدار و فروشنده، پرداخت نقل و انتقال و رزرو نوبت مرکز تعویض پلاک',
    requirements: ['برگ سبز خودرو', 'بیمه‌نامه شخص ثالث معتبر', 'معاینه فنی'],
    estimatedDays: '۱ روز کاری',
    fee: 110000,
    icon: 'RotateCw',
    isPopular: true,
    isNew: true,
    department: 'پلیس راهور فراجا'
  },
  {
    id: 'vh-fuel-card',
    title: 'درخواست صدور کارت سوخت المثنی',
    categoryId: 'vehicle',
    tags: ['semi-online', 'in-person'],
    description: 'ثبت مشخصات و اتصال به کارت بانکی یا درخواست صدور فیزیکی به دفتر پیشخوان',
    requirements: ['سند مالکیت خودرو', 'کارت شناسایی خودرو'],
    estimatedDays: '۷ الی ۱۴ روز',
    fee: 65000,
    icon: 'Fuel',
    department: 'شرکت ملی پخش فرآورده‌های نفتی'
  },

  // رفاهی
  {
    id: 'wf-subsidy',
    title: 'استعلام دهک‌بندی و یارانه نقدی',
    categoryId: 'welfare',
    tags: ['online'],
    description: 'مشاهده دهک درآمدی خانوار در پایگاه رفاه ایرانیان و ثبت اعتراض دهک‌بندی',
    requirements: ['کد ملی و شماره شبا سرپرست'],
    estimatedDays: 'آنی',
    fee: 0,
    icon: 'Users',
    isPopular: true,
    department: 'وزارت تعاون، کار و رفاه اجتماعی'
  },
  {
    id: 'wf-kalabarg',
    title: 'طرح کالابرگ الکترونیک و اعتبار خرید',
    categoryId: 'welfare',
    tags: ['online'],
    description: 'استعلام مانده اعتبار خرید کالاهای اساسی و نمایش نزدیک‌ترین فروشگاه‌های متصل',
    requirements: ['کد ملی سرپرست خانوار'],
    estimatedDays: 'آنی',
    fee: 0,
    icon: 'ShoppingBag',
    isPopular: true,
    isNew: true,
    department: 'وزارت تعاون و رفاه'
  },
  {
    id: 'wf-justice-shares',
    title: 'سامانه سهام عدالت و واریز سود',
    categoryId: 'welfare',
    tags: ['online'],
    description: 'مشاهده ارزش روز دارایی سهام عدالت، وضعیت واریز سود و تغییر شماره شبا',
    requirements: ['شماره شبا بانکی تایید شده در سامانه سجام'],
    estimatedDays: 'آنی',
    fee: 0,
    icon: 'TrendingUp',
    department: 'سازمان بورس و اوراق بهادار'
  },

  // دولتی و کسب و کار
  {
    id: 'gv-pension',
    title: 'خدمات صندوق بازنشستگان کشوری و لشکری',
    categoryId: 'government',
    tags: ['online', 'semi-online'],
    description: 'دریافت فیش حقوقی، حکم کارگزینی، ثبت‌نام وام بازنشستگان و بیمه تکمیلی',
    requirements: ['شماره دفترکل یا شماره پرسنلی', 'کد ملی'],
    estimatedDays: 'آنی',
    fee: 20000,
    icon: 'Briefcase',
    isPopular: true,
    department: 'صندوق بازنشستگی کشوری'
  },
  {
    id: 'gv-business-permit',
    title: 'صدور مجوز کسب‌وکار (درگاه ملی مجوزها)',
    categoryId: 'government',
    tags: ['semi-online', 'online'],
    description: 'ثبت استعلام، دریافت کد رهگیری ملی پروانه کسب و ارسال جهت تایید اتحادیه مربوطه',
    requirements: ['کد پستی واحد صنفی', 'گواهی عدم سوء‌پیشینه', 'کارت پایان خدمت یا معافیت'],
    estimatedDays: '۳ الی ۷ روز',
    fee: 140000,
    icon: 'Award',
    isPopular: true,
    isNew: true,
    department: 'وزارت امور اقتصادی و دارایی'
  },
  {
    id: 'gv-sana',
    title: 'احراز هویت و ابلاغیه قضایی ثنا',
    categoryId: 'government',
    tags: ['online'],
    description: 'احراز هویت بیومتریک آنلاین و مشاهده ابلاغیه‌های دادگستری و شورای حل اختلاف',
    requirements: ['کد ملی', 'ویدیو تایید هویت چهره'],
    estimatedDays: 'آنی',
    fee: 35000,
    icon: 'Scale',
    department: 'قوه قضائیه جمهوری اسلامی ایران'
  },
  {
    id: 'gv-tax-return',
    title: 'ثبت اظهارنامه مالیاتی مشاغل و املاک',
    categoryId: 'government',
    tags: ['semi-online', 'online'],
    description: 'محاسبه خودکار مالیات بر درآمد مقطوع (تبصره ۱۰۰) و ارسال تاییدیه مالیاتی',
    requirements: ['کد اقتصادی و پرونده مالیاتی فعال'],
    estimatedDays: '۲۴ ساعت',
    fee: 120000,
    icon: 'Calculator',
    department: 'سازمان امور مالیاتی کشور'
  },

  // مسکن و شهرداری
  {
    id: 'hs-national-housing',
    title: 'ثبت‌نام طرح نهضت ملی مسکن',
    categoryId: 'housing',
    tags: ['semi-online'],
    description: 'ثبت اطلاعات خانوار و بارگذاری مدارک احراز سابقه سکونت جهت تخصیص پروژه',
    requirements: ['گواهی سابقه ۵ سال سکونت', 'سند ازدواج', 'مدارک هویتی سرپرست'],
    estimatedDays: '۱۰ الی ۳۰ روز',
    fee: 50000,
    icon: 'Building',
    department: 'وزارت راه و شهرسازی'
  },
  {
    id: 'hs-postal-cert',
    title: 'استعلام و تاییدیه کد پستی ۱۰ رقمی',
    categoryId: 'housing',
    tags: ['online'],
    description: 'صدور گواهی پستی تایید شده با مهر دیجیتال اداره پست جهت ارائه به بانک‌ها و ادارات',
    requirements: ['کد پستی ۱۰ رقمی'],
    estimatedDays: 'آنی (کمتر از ۵ دقیقه)',
    fee: 25000,
    icon: 'MapPin',
    isPopular: true,
    isNew: true,
    department: 'شرکت ملی پست جمهوری اسلامی ایران'
  },

  // مالی
  {
    id: 'bk-safteh',
    title: 'صدور و ظهرنویسی سفته الکترونیک',
    categoryId: 'banking',
    tags: ['online'],
    description: 'صدور سفته دیجیتال با کد شناسه معتبر بانک مرکزی و امضای دیجیتال برای وام و ضمانت',
    requirements: ['امضای دیجیتال فعال یا احراز هویت ثنا'],
    estimatedDays: 'آنی',
    fee: 40000,
    icon: 'FileText',
    isPopular: true,
    isNew: true,
    department: 'بانک مرکزی جمهوری اسلامی ایران'
  },
  {
    id: 'bk-credit-score',
    title: 'استعلام رتبه اعتبارسنجی بانکی (سامانه صیاد)',
    categoryId: 'banking',
    tags: ['online'],
    description: 'دریافت گزارش جامع خوش‌حسابی، چک‌های برگشتی و اقساط معوق در شبکه بانکی کشور',
    requirements: ['کد ملی مالک شماره تلفن'],
    estimatedDays: 'آنی',
    fee: 28000,
    icon: 'BarChart3',
    isNew: true,
    department: 'شرکت اعتبارسنجی بانکی ایران'
  },

  // پستی
  {
    id: 'ps-tracking',
    title: 'رهگیری هوشمند مرسولات پستی سازمانی',
    categoryId: 'postal',
    tags: ['online'],
    description: 'ردیابی گواهینامه، کارت خودرو، گذرنامه و اسناد دولتی با شماره مرسوله و کد ملی',
    requirements: ['کد رهگیری ۲۴ رقمی پستی یا کد ملی'],
    estimatedDays: 'آنی',
    fee: 0,
    icon: 'Navigation',
    isNew: true,
    department: 'شرکت ملی پست'
  },
  {
    id: 'ps-lost-docs',
    title: 'سامانه پست‌یافته (مدارک مفقودی)',
    categoryId: 'postal',
    tags: ['online', 'semi-online'],
    description: 'استعلام شناسنامه، کارت ملی و مدارک پیدا شده در باجه‌های سراسر کشور و درخواست تحویل درب منزل',
    requirements: ['کد ملی صاحب مدرک مفقودی'],
    estimatedDays: '۲۴ ساعت',
    fee: 35000,
    icon: 'SearchCheck',
    department: 'شرکت ملی پست'
  },

  // خدمات اینترنتی و سامانه‌ای
  {
    id: 'net-mikhak',
    title: 'ثبت نام در سامانه میخک(امور خارجه)',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت نام و تشکیل پرونده در سامانه مدیریت یکپارچه خدمات کنسولی (میخک) جهت وکالت‌نامه، ترجمه و امور هموطنان خارج از کشور',
    requirements: ['اطلاعات هویتی متقاضی', 'شماره گذرنامه و مدرک اقامتی', 'شماره تلفن همراه فعال'],
    estimatedDays: '۲۴ ساعت',
    fee: 65000,
    icon: 'Globe',
    isPopular: true,
    isNew: true,
    department: 'وزارت امور خارجه'
  },
  {
    id: 'net-kasr-aghsat-keshvari',
    title: 'نامه کسر از اقساط بازنشستگان (کشوری)',
    categoryId: 'internet',
    tags: ['online'],
    description: 'صدور و دریافت اینترنتی گواهی کسر از حقوق و اقساط بازنشستگان و موظفین صندوق بازنشستگی کشوری با بارکد اصالت',
    requirements: ['شماره دفترکل یا کد ملی بازنشسته', 'شماره حساب حقوقی', 'مشخصات مرجع دریافت‌کننده (بانک)'],
    estimatedDays: 'آنی',
    fee: 35000,
    icon: 'FileText',
    department: 'صندوق بازنشستگی کشوری'
  },
  {
    id: 'net-khodnevis',
    title: 'سامانه خودنویس',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت رایگان و اخذ کد رهگیری قانونی قراردادهای اجاره مسکن بین موجر و مستاجر در سامانه ملی خودنویس',
    requirements: ['کد پستی و اطلاعات سند ملک', 'کد ملی و شماره موبایل موجر و مستاجر', 'مشخصات دو نفر شاهد'],
    estimatedDays: '۲۴ ساعت',
    fee: 55000,
    icon: 'Home',
    isPopular: true,
    isNew: true,
    department: 'وزارت راه و شهرسازی'
  },
  {
    id: 'net-mudian-factor',
    title: 'ثبت فاکتور مالیاتی در مودیان',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ارسال و ثبت صورتحساب الکترونیکی فروش کالا و خدمات در کارپوشه سامانه مودیان مطابق استانداردهای سازمان مالیاتی',
    requirements: ['کلید خصوصی و شناسه یکتا حافظه مالیاتی', 'فایل اقلام فاکتور فروش', 'شناسه کالا / خدمت'],
    estimatedDays: '۲۴ ساعت',
    fee: 75000,
    icon: 'Receipt',
    isPopular: true,
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-shenase-yekta-mojavvez',
    title: 'دریافت شناسه یکتا برای مجوزهای قدیمی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'تبدیل مجوزهای کاغذی و پروانه‌های کسب سنتی به شناسه یکتای الکترونیکی در درگاه ملی مجوزها (QR Code رسمی)',
    requirements: ['تصویر پروانه کسب قدیمی معتبر', 'کد پستی محل کسب', 'کد ملی و شماره صنفی'],
    estimatedDays: '۱ الی ۲ روز کاری',
    fee: 45000,
    icon: 'QrCode',
    isNew: true,
    department: 'درگاه ملی مجوزهای کشور'
  },
  {
    id: 'net-nezam-mohandesi',
    title: 'عضویت نظام مهندسی',
    categoryId: 'internet',
    tags: ['online', 'semi-online'],
    description: 'ثبت‌نام اولیه، بارگذاری مدارک تحصیلی و تشکیل پرونده الکترونیک عضویت در سازمان نظام مهندسی ساختمان',
    requirements: ['مدرک تحصیلی مهندسی معتبر', 'کارت ملی و شناسنامه', 'تصویر پرسنلی', 'گواهی عدم سوءپیشینه'],
    estimatedDays: '۳ الی ۵ روز کاری',
    fee: 120000,
    icon: 'Building2',
    department: 'سازمان نظام مهندسی ساختمان'
  },
  {
    id: 'net-sepamak',
    title: 'سامانه سپامک',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت درخواست صدور، تمدید یا ارتقای پایه پروانه اشتغال به کار مهندسی در سامانه سپامک وزارت راه و شهرسازی',
    requirements: ['کارنامه قبولی آزمون ورود به حرفه', 'کارت عضویت نظام مهندسی', 'گواهی دوره‌های آموزشی'],
    estimatedDays: '۳ الی ۷ روز کاری',
    fee: 85000,
    icon: 'Award',
    department: 'وزارت راه و شهرسازی'
  },
  {
    id: 'net-patmak',
    title: 'سامانه پاتمک',
    categoryId: 'internet',
    tags: ['online'],
    description: 'پرداخت الکترونیک عوارض و تعرفه‌های صدور و تمدید پروانه اشتغال در سامانه پاتمک امور مقررات ملی ساختمان',
    requirements: ['کد رهگیری سپامک یا شماره پرونده', 'کد ملی متقاضی'],
    estimatedDays: 'آنی',
    fee: 25000,
    icon: 'CreditCard',
    department: 'دفتر مقررات ملی و کنترل ساختمان'
  },
  {
    id: 'net-amoozesh-pezeshki',
    title: 'تکمیل اطلاعات سامانه جامع آموزش پزشکی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت و به‌روزرسانی پرونده آموزشی، مدارک دانش‌آموختگان و احراز مدارک رشته‌های علوم پزشکی',
    requirements: ['کد ملی و شماره دانشجویی / نظام پزشکی', 'تصاویر مدارک تحصیلی و ریز نمرات'],
    estimatedDays: '۲ الی ۴ روز کاری',
    fee: 60000,
    icon: 'GraduationCap',
    department: 'وزارت بهداشت، درمان و آموزش پزشکی'
  },
  {
    id: 'net-sameh-behdasht',
    title: 'ثبت نام صلاحیت بهداشتی در سامانه سامح',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت‌نام درخواست گواهی صلاحیت و ممیزی بهداشتی اصناف و اماکن تهیه و توزیع غذا در سامانه سامح وزارت بهداشت',
    requirements: ['پروانه کسب یا درخواست مجوز', 'کد پستی واحد صنفی', 'اطلاعات هویتی متصدی'],
    estimatedDays: '۲ الی ۵ روز کاری',
    fee: 70000,
    icon: 'ShieldCheck',
    department: 'مرکز سلامت محیط و کار وزارت بهداشت'
  },
  {
    id: 'net-kelas-asnaf',
    title: 'ثبت نام کلاس اصناف',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت‌نام دوره‌های آموزشی الزامی احکام تجارت و کسب‌وکار متقاضیان صدور و تمدید پروانه کسب',
    requirements: ['کد رهگیری درگاه ملی مجوزها', 'کد ملی و شماره همراه به نام متقاضی'],
    estimatedDays: '۲۴ ساعت',
    fee: 50000,
    icon: 'BookOpen',
    department: 'اتاق اصناف ایران'
  },
  {
    id: 'net-novin-asnaf-hoghooghi',
    title: 'ثبت نام مراحل اشخاص حقوقی در نوین اصناف',
    categoryId: 'internet',
    tags: ['online'],
    description: 'تکمیل فرایند صدور پروانه کسب برای شرکت‌ها و اشخاص حقوقی در سامانه نوین اصناف و اتصال به درگاه ملی',
    requirements: ['شناسه ملی شرکت و آگهی تاسیس', 'اسناد مالکیت/اجاره شرکت', 'کد پستی دفتر مرکزی', 'مدارک مدیرعامل'],
    estimatedDays: '۳ الی ۷ روز کاری',
    fee: 180000,
    icon: 'Building',
    department: 'سامانه نوین اصناف کشور'
  },
  {
    id: 'net-novin-asnaf-haghighi',
    title: 'ثبت نام مراحل اشخاص حقیقی در نوین اصناف',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت درخواست پروانه کسب صنفی اشخاص حقیقی، تعیین رسته شغلی و استعلامات دوازده‌گانه در نوین اصناف',
    requirements: ['کد رهگیری درگاه ملی مجوزها', 'اجاره‌نامه یا سند ملک تجاری با کد رهگیری', 'کارت پایان خدمت (آقایان)'],
    estimatedDays: '۲ الی ۵ روز کاری',
    fee: 130000,
    icon: 'UserCheck',
    isPopular: true,
    department: 'سامانه نوین اصناف کشور'
  },
  {
    id: 'net-police-man-amaken',
    title: 'درخواست بازدید اماکن در پلیس من',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت درخواست استعلام صلاحیت انتظامی و ترافیکی و تعیین نوبت بازدید پلیس نظارت بر اماکن عمومی',
    requirements: ['شماره پرونده صنفی نوین اصناف', 'کد پستی دقیق محل کسب', 'شماره همراه به نام متقاضی'],
    estimatedDays: '۲ الی ۴ روز کاری',
    fee: 55000,
    icon: 'Shield',
    department: 'پلیس نظارت بر اماکن عمومی فراجا'
  },
  {
    id: 'net-186-maliyati-payankhedmat',
    title: 'استعلام گواهی ۱۸۶ مالیاتی و کارت پایان خدمت',
    categoryId: 'internet',
    tags: ['online'],
    description: 'اخذ گواهی ماده ۱۸۶ قانون مالیات‌های مستقیم (عدم بدهی مالیاتی) و استعلام اصالت کارت پایان خدمت نظام وظیفه',
    requirements: ['کد ملی و پرونده مالیاتی فعال', 'شماره کارت هوشمند پایان خدمت / معافیت'],
    estimatedDays: 'آنی الی ۲۴ ساعت',
    fee: 40000,
    icon: 'CheckCircle2',
    department: 'سازمان امور مالیاتی / نظام وظیفه فراجا'
  },
  {
    id: 'net-polomp-dafater',
    title: 'پلمپ دفاتر قانونی',
    categoryId: 'internet',
    tags: ['online', 'semi-online'],
    description: 'ثبت‌نام و درخواست پلمپ دفاتر تجاری سالانه اشخاص حقیقی و حقوقی در سامانه اداره ثبت شرکت‌ها و تحویل پستی',
    requirements: ['شناسه ملی شرکت یا کد ملی متقاضی', 'آدرس پستی دقیق با کد ۱۰ رقمی', 'تصویر آگهی آخرین تغییرات'],
    estimatedDays: '۳ الی ۶ روز کاری',
    fee: 160000,
    icon: 'BookLock',
    isPopular: true,
    department: 'سازمان ثبت اسناد و املاک کشور'
  },
  {
    id: 'net-monaghesseh',
    title: 'ثبت مناقصه',
    categoryId: 'internet',
    tags: ['online'],
    description: 'جستجو، بارگذاری اسناد و ضمانت‌نامه‌ها و ثبت پیشنهاد قیمت در مناقصات دولتی سامانه ستاد ایران',
    requirements: ['توکن امضای الکترونیک فعال', 'حساب کاربری تامین‌کننده ستاد', 'اسناد و ضمانت‌نامه مناقصه'],
    estimatedDays: '۲۴ ساعت',
    fee: 150000,
    icon: 'FileCheck',
    department: 'سامانه تدارکات الکترونیکی دولت (ستاد)'
  },
  {
    id: 'net-mozayedeh',
    title: 'ثبت مزایده',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت پیشنهاد و ارائه فیش ودیعه در مزایده‌های اموال منقول و غیرمنقول دولتی در سامانه ستاد',
    requirements: ['گواهی امضای دیجیتال', 'اطلاعات هویتی و حساب بانکی', 'فیش واریز سپرده مزایده'],
    estimatedDays: '۲۴ ساعت',
    fee: 150000,
    icon: 'Gavel',
    department: 'سامانه تدارکات الکترونیکی دولت (ستاد)'
  },
  {
    id: 'net-setad-iran-avvaliye',
    title: 'ثبت نام اولیه ستاد ایران',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت‌نام و عضویت متقاضیان و کسب‌وکارها در سامانه ستاد ایران به عنوان تامین‌کننده یا مزایده‌گر',
    requirements: ['مدارک هویتی و آدرس پستی', 'اطلاعات حساب بانکی شبا', 'گواهی مالیاتی و پروانه فعالیت'],
    estimatedDays: '۱ الی ۲ روز کاری',
    fee: 90000,
    icon: 'UserPlus',
    isNew: true,
    department: 'مرکز توسعه تجارت الکترونیکی'
  },
  {
    id: 'net-kart-sookht',
    title: 'کارت سوخت',
    categoryId: 'internet',
    tags: ['online', 'semi-online'],
    description: 'ثبت درخواست صدور کارت هوشمند سوخت خودرو و موتورسیکلت، تغییر مالکیت و پیگیری مرسوله پستی',
    requirements: ['کارت شناسایی خودرو (برگ سبز)', 'بیمه‌نامه شخص ثالث معتبر', 'کد ملی و شماره موبایل مالک'],
    estimatedDays: '۱۵ الی ۳۰ روز',
    fee: 110000,
    icon: 'Fuel',
    isPopular: true,
    department: 'شرکت ملی پخش فرآورده‌های نفتی ایران'
  },
  {
    id: 'net-eskan-amlak',
    title: 'ثبت نام سامانه اسکان و املاک',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت اطلاعات سکونت و مالکیت تمامی املاک تحت تملک یا استیجاری در سامانه ملی املاک و اسکان کشور',
    requirements: ['کد ملی و شماره همراه سرپرست', 'کد پستی ۱۰ رقمی محل سکونت', 'شناسه قبض برق ملک'],
    estimatedDays: 'آنی',
    fee: 40000,
    icon: 'Home',
    isPopular: true,
    department: 'وزارت راه و شهرسازی'
  },
  {
    id: 'net-taeedie-tahsili',
    title: 'تائیدیه تحصیلی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'درخواست صدور تاییدیه تحصیلی مدرک دیپلم و پیش‌دانشگاهی جهت ارائه به دانشگاه‌ها، ادارات و سازمان‌ها',
    requirements: ['مشخصات شناسنامه‌ای', 'نام استان و منطقه آموزش و پرورش فارغ‌التحصیلی', 'سال فارغ‌التحصیلی'],
    estimatedDays: '۲۴ الی ۴۸ ساعت',
    fee: 45000,
    icon: 'GraduationCap',
    isPopular: true,
    department: 'وزارت آموزش و پرورش'
  },
  {
    id: 'net-nobat-pelak',
    title: 'نوبت دهی تعویض پلاک',
    categoryId: 'internet',
    tags: ['online'],
    description: 'رزرو اینترنتی نوبت مراکز تعویض پلاک خودرو و موتورسیکلت در تمامی استان‌ها و شهرهای کشور',
    requirements: ['شماره پلاک خودرو', 'شماره VIN مندرج در کارت خودرو', 'کد ملی خریدار یا فروشنده'],
    estimatedDays: 'آنی',
    fee: 35000,
    icon: 'CalendarCheck',
    isPopular: true,
    department: 'پلیس راهور فراجا'
  },
  {
    id: 'net-ehraz-neshani-post',
    title: 'احراز نشانی پست',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت درخواست احراز سکونت برخط و اعزام مامور پست به محل سکونت جهت تایید نشانی و صدور تاییدیه',
    requirements: ['کد پستی ۱۰ رقمی', 'شماره تماس متقاضی', 'نشانی دقیق'],
    estimatedDays: '۲۴ الی ۴۸ ساعت',
    fee: 50000,
    icon: 'MapPin',
    department: 'شرکت ملی پست جمهوری اسلامی ایران'
  },
  {
    id: 'net-pardakht-khelafi',
    title: 'پرداخت خلافی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'استعلام ریز جرایم رانندگی و تخلفات ثبت‌شده دوربین‌ها و پرداخت آنی با تسویه فوری راهور',
    requirements: ['شماره بارکد کارت خودرو یا پلاک + کد ملی مالک'],
    estimatedDays: 'آنی (تسویه زیر ۵ دقیقه)',
    fee: 20000,
    icon: 'CreditCard',
    isPopular: true,
    department: 'پلیس راهور فراجا'
  },
  {
    id: 'net-maliyat-khodro-naql',
    title: 'ثبت نام و پرداخت مالیات نقل و انتقال خودرو',
    categoryId: 'internet',
    tags: ['online'],
    description: 'محاسبه و پرداخت برخط مالیات نقل و انتقال انواع خودرو و موتورسیکلت قبل از تعویض پلاک و انتقال سند',
    requirements: ['شماره پلاک انتظامی', 'کد ملی فروشنده', 'شماره همراه مالک'],
    estimatedDays: 'آنی',
    fee: 35000,
    icon: 'Car',
    isPopular: true,
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-eblaghiye-maliyati',
    title: 'ابلاغیه های مالیاتی (به ازای هر ابلاغ)',
    categoryId: 'internet',
    tags: ['online'],
    description: 'مشاهده، دریافت و ثبت رسید برگ تشخیص، برگ قطعی و اوراق ابلاغیه الکترونیک در درگاه ملی مالیات',
    requirements: ['نام کاربری و رمز سامانه my.tax.gov.ir یا کد ملی'],
    estimatedDays: 'آنی',
    fee: 25000,
    icon: 'MailCheck',
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-maliyat-lux-khodro-maskan',
    title: 'ثبت نام و پرداخت و اعتراض مالیات خودرو و خانه لوکس',
    categoryId: 'internet',
    tags: ['online'],
    description: 'استعلام، پرداخت یا ثبت لایحه اعتراضی نسبت به مالیات بر واحدهای مسکونی و خودروهای گران‌قیمت و لوکس',
    requirements: ['کد ملی مالک', 'کد پستی ملک یا شماره پلاک خودرو', 'مستندات و دلایل اعتراض'],
    estimatedDays: '۲۴ ساعت',
    fee: 65000,
    icon: 'ShieldAlert',
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-maliyat-hoghoogh-personel',
    title: 'ارسال لیست مالیات حقوق کارکنان (هر نفر)',
    categoryId: 'internet',
    tags: ['online'],
    description: 'تنظیم و بارگذاری ماهانه فایل اطلاعات حقوق و دستمزد و ارسال لیست مالیات حقوق پرسنل در سامانه حقوق مالیاتی',
    requirements: ['لیست اطلاعات پرسنل و مبالغ ناخالص حقوق', 'کد ملی و شماره بیمه کارکنان'],
    estimatedDays: '۲۴ ساعت',
    fee: 20000,
    icon: 'Users',
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-moamelat-fasli',
    title: 'ارسال لیست معاملات فصلی (یک رکورد)',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت صورت معاملات فصلی خرید، فروش و قراردادها (ماده ۱۶۹ مکرر) در سامانه الکترونیکی معاملات فصلی',
    requirements: ['اطلاعات فاکتور خرید یا فروش', 'شناسه ملی/کد اقتصادی طرف معامله', 'مبالغ و مالیات ارزش افزوده'],
    estimatedDays: '۲۴ ساعت',
    fee: 25000,
    icon: 'FileSpreadsheet',
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-arzesh-afzoodeh',
    title: 'ارسال اظهارنامه ارزش افزوده',
    categoryId: 'internet',
    tags: ['online'],
    description: 'محاسبه، تکمیل جداول و تسلیم اظهارنامه مالیات بر ارزش افزوده دوره‌های فصلی و دریافت قبض پرداخت',
    requirements: ['مجموع مبالغ خرید و فروش فصل', 'گواهی‌ها و اعتبارات مالیاتی دوره', 'پرونده فعال ارزش افزوده'],
    estimatedDays: '۲۴ الی ۴۸ ساعت',
    fee: 110000,
    icon: 'Calculator',
    isPopular: true,
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-mudian-code-yekta',
    title: 'ثبت نام سامانه مودیان و دریافت کد یکتا',
    categoryId: 'internet',
    tags: ['online'],
    description: 'تولید کلیدهای عمومی و اختصاصی CSR، ثبت در سامانه مودیان و اخذ شناسه یکتای حافظه مالیاتی',
    requirements: ['اطلاعات هویتی مودی یا شرکت', 'پرونده مالیاتی فعال', 'مشخصات شعب یا محل فعالیت'],
    estimatedDays: '۲۴ ساعت',
    fee: 140000,
    icon: 'Key',
    isPopular: true,
    isNew: true,
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-code-eghtesadi-kamel',
    title: 'ثبت نام کد اقتصادی کامل',
    categoryId: 'internet',
    tags: ['online'],
    description: 'تشکیل پرونده مالیاتی، پیش‌ثبت‌نام و ثبت‌نام نهایی دریافت کد اقتصادی ۱۲ رقمی جدید اشخاص حقیقی و حقوقی',
    requirements: ['اسناد هویتی متقاضی یا شرکت', 'سند یا اجاره‌نامه با کد رهگیری', 'کد پستی تایید شده'],
    estimatedDays: '۲ الی ۴ روز کاری',
    fee: 120000,
    icon: 'Building2',
    isPopular: true,
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-pardakht-ghabz-maliyat',
    title: 'پرداخت قبض مالیات',
    categoryId: 'internet',
    tags: ['online'],
    description: 'استعلام شناسه ۳۰ رقمی قبض مالیاتی، پرداخت برخط و اخذ آنی مفاصاحساب و تاییدیه بانکی پرداخت',
    requirements: ['شناسه قبض و شناسه پرداخت مالیاتی ۳۰ رقمی'],
    estimatedDays: 'آنی',
    fee: 20000,
    icon: 'CreditCard',
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-ezharnamah-ers',
    title: 'اظهارنامه ارث',
    categoryId: 'internet',
    tags: ['online', 'semi-online'],
    description: 'تنظیم، ثبت ماترک و دارایی‌های متوفی و تسلیم اظهارنامه مالیات بر ارث در سامانه سازمان امور مالیاتی',
    requirements: ['گواهی فوت و گواهی حصر وراثت', 'مدارک هویتی تمام وراث', 'اسناد اموال، حساب‌ها و املاک متوفی'],
    estimatedDays: '۳ الی ۷ روز کاری',
    fee: 190000,
    icon: 'FileText',
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-tabsareh-100-sefr',
    title: 'اظهارنامه تبصره ۱۰۰ صفر',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ارسال فرم توافق تبصره ماده ۱۰۰ قانون مالیات‌های مستقیم با مالیات صفر برای مشاغل با گردش مالی مشمول معافیت',
    requirements: ['کد ملی و پرونده مالیاتی فعال', 'اطلاعات درگاه‌ها و کارتخوان‌های متصل'],
    estimatedDays: '۲۴ ساعت',
    fee: 70000,
    icon: 'FileCheck2',
    isPopular: true,
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-madeh-95-aadi',
    title: 'اظهارنامه عادی ماده ۹۵',
    categoryId: 'internet',
    tags: ['online'],
    description: 'تکمیل و ارسال اظهارنامه جامع مالیات بر درآمد مشاغل گروه‌های اول، دوم و سوم ماده ۹۵ با درج درآمد و هزینه‌ها',
    requirements: ['دفاتر و اسناد درآمد و هزینه سالانه', 'ترازنامه و سود و زیان (گروه ۱)', 'پرونده مالیاتی معتبر'],
    estimatedDays: '۲ الی ۵ روز کاری',
    fee: 160000,
    icon: 'FileSpreadsheet',
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-eteraz-maliyati',
    title: 'ثبت اعتراض مالیاتی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'تنظیم لایحه دفاعیه و ثبت اعتراض به برگ تشخیص مالیاتی جهت بررسی مجدد در ممیزی کل یا هیات حل اختلاف',
    requirements: ['شماره برگ تشخیص مورد اعتراض', 'متن لایحه دفاعیه مستدل', 'مدارک و فاکتورهای مثبته'],
    estimatedDays: '۲ الی ۴ روز کاری',
    fee: 95000,
    icon: 'AlertCircle',
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-faramooshi-ramz-maliyat',
    title: 'فراموشی رمز سامانه مالیاتی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'بازیابی و بازنشانی فوری کلمه عبور و نام کاربری درگاه ملی خدمات الکترونیک سازمان امور مالیاتی کشور',
    requirements: ['کد ملی یا شناسه ملی', 'شماره همراه ثبت‌شده در پرونده مالیاتی'],
    estimatedDays: 'آنی',
    fee: 30000,
    icon: 'KeyRound',
    department: 'سازمان امور مالیاتی کشور'
  },
  {
    id: 'net-bazresi-dafater-ghanuni',
    title: 'ثبت بازرسی دفاتر قانونی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت درخواست و پیگیری فرایند بازرسی و حسابرسی دفاتر قانونی شرکت‌ها در سازمان تامین اجتماعی',
    requirements: ['کد کارگاهی معتبر', 'اسناد پلمپ دفاتر تجاری سال مورد نظر', 'تراز مالی تایید شده'],
    estimatedDays: '۳ الی ۷ روز کاری',
    fee: 140000,
    icon: 'SearchCheck',
    department: 'سازمان تامین اجتماعی'
  },
  {
    id: 'net-eteraz-kargahi',
    title: 'اعتراض به بدهی های کارگاهی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت اعتراض الکترونیکی به اعلامیه بدهی و جرایم حق بیمه کارگاه‌ها جهت طرح در هیات‌های بدوی تشخیص مطالبات',
    requirements: ['کد کارگاهی و شماره اعلامیه بدهی', 'لایحه اعتراضی و مستندات پرداخت حقوق'],
    estimatedDays: '۲ الی ۴ روز کاری',
    fee: 90000,
    icon: 'AlertTriangle',
    department: 'سازمان تامین اجتماعی'
  },
  {
    id: 'net-ehraz-jame-kar',
    title: 'احراز هویت سامانه جامع کار',
    categoryId: 'internet',
    tags: ['online'],
    description: 'احراز هویت الکترونیک کارگران و کارفرمایان و ایجاد پروفایل رسمی در سامانه جامع روابط کار',
    requirements: ['کارت ملی و شناسنامه', 'شماره موبایل به نام شخص', 'کد پستی محل سکونت یا کارگاه'],
    estimatedDays: '۲۴ ساعت',
    fee: 55000,
    icon: 'ShieldCheck',
    isPopular: true,
    department: 'وزارت تعاون، کار و رفاه اجتماعی'
  },
  {
    id: 'net-bimeh-bikari',
    title: 'ثبت درخواست بیمه بیکاری',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ثبت دادخواست و تقاضای برقراری مقرری بیمه بیکاری در سامانه جامع روابط کار پس از اتمام قرارداد یا اخراج',
    requirements: ['نامه عدم نیاز کارفرما یا قرارداد کار', 'احراز هویت سامانه جامع کار', 'تصویر آخرین مدرک تحصیلی'],
    estimatedDays: '۳ الی ۷ روز کاری',
    fee: 95000,
    icon: 'UserX',
    isPopular: true,
    department: 'وزارت تعاون، کار و رفاه اجتماعی'
  },
  {
    id: 'net-fish-hoghoogh-bazneshastegi',
    title: 'دریافت فیش حقوق و حکم بازنشستگی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'مشاهده، استعلام و دریافت آخرین فیش حقوقی و حکم افزایش مستمری بازنشستگان و مستمری‌بگیران تامین اجتماعی',
    requirements: ['کد ملی و شماره مستمری بازنشسته', 'رمز عبور سامانه eservices.tamin.ir'],
    estimatedDays: 'آنی',
    fee: 25000,
    icon: 'FileText',
    isPopular: true,
    department: 'سازمان تامین اجتماعی'
  },
  {
    id: 'net-savabeghe-tamin',
    title: 'دریافت سوابق تامین اجتماعی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'استعلام مجموع سوابق پرداخت حق بیمه، ریز دستمزد سالانه و صدور گواهی سابقه ممهور به کد رهگیری',
    requirements: ['کد ملی و شماره تلفن همراه به نام متقاضی'],
    estimatedDays: 'آنی',
    fee: 35000,
    icon: 'Clock',
    isPopular: true,
    department: 'سازمان تامین اجتماعی'
  },
  {
    id: 'net-kasr-aghsat-tamin',
    title: 'نامه کسر از اقساط بازنشستگان تامین اجتماعی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'صدور اینترنتی گواهی کسر از حقوق بازنشستگان سازمان تامین اجتماعی برای بانک‌ها و موسسات اعتباری',
    requirements: ['کد ملی و شماره مستمری', 'نام و شعبه بانک یا موسسه وام‌دهنده', 'مبلغ قسط'],
    estimatedDays: 'آنی',
    fee: 35000,
    icon: 'FileCheck',
    department: 'سازمان تامین اجتماعی'
  },
  {
    id: 'net-profile-tamin',
    title: 'ثبت نام پروفایل تامین اجتماعی',
    categoryId: 'internet',
    tags: ['online'],
    description: 'ایجاد حساب کاربری و فعال‌سازی پرتال خدمات غیرحضوری سازمان تامین اجتماعی برای بیمه‌شدگان و مستمری‌بگیران',
    requirements: ['کد ملی', 'شماره همراه به نام بیمه‌شده', 'پاسخ به سوالات امنیتی هویتی'],
    estimatedDays: 'آنی الی ۲۴ ساعت',
    fee: 40000,
    icon: 'UserCheck',
    isPopular: true,
    department: 'سازمان تامین اجتماعی'
  },
  {
    id: 'net-enhesar-verasat',
    title: 'درخواست انحصار وراثت',
    categoryId: 'internet',
    tags: ['online', 'semi-online'],
    description: 'تنظیم دادخواست گواهی انحصار وراثت، ثبت مدارک وراث و ارسال پرونده به شورای حل اختلاف در سامانه عدل ایران',
    requirements: ['گواهی فوت و استشهادیه محضری فوت', 'شناسنامه و کارت ملی متوفی و تمامی وراث', 'حساب ثنا فعال'],
    estimatedDays: '۵ الی ۱۰ روز کاری',
    fee: 180000,
    icon: 'Scale',
    isPopular: true,
    department: 'قوه قضائیه - عدل ایران'
  },
  {
    id: 'net-adam-sooe-pishine',
    title: 'ثبت عدم سوء پیشینه',
    categoryId: 'internet',
    tags: ['online'],
    description: 'درخواست صدور گواهی عدم سوءپیشینه کیفری با امضای الکترونیک قوه قضائیه بدون نیاز به مراجعه حضوری',
    requirements: ['کد ملی و حساب کاربری ثنا فعال', 'تصویر پرسنلی جدید'],
    estimatedDays: '۲۴ الی ۴۸ ساعت',
    fee: 75000,
    icon: 'ShieldCheck',
    isPopular: true,
    department: 'قوه قضائیه - سامانه ثنا'
  }
];

export const MOCK_OFFICES: PishkhanOffice[] = [
  // ----------------------------------------------------
  // دسته ۱: عضو پلتفرم و آنلاین (REGISTERED & ONLINE)
  // ----------------------------------------------------
  {
    id: 'off-teh-mehregan',
    code: '72-1402',
    name: 'پیشخوان مهرگان ونک',
    managerName: 'مهندس سهراب صامتی',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.8,
    reviewCount: 428,
    medals: ['عضو طلایی سامانه ملی', 'پاسخگویی زیر ۱۵ دقیقه', 'رضایت ۹۹٪ مراجعین', 'رتبه ۱ خدمات هویتی'],
    specialties: ['ثبت احوال و شناسنامه VIP', 'خدمات خودرو و راهور', 'کارت بهداشت اصناف'],
    address: 'تهران، خیابان ولی‌عصر، بالاتر از میدان ونک، پلاک ۲۱۴',
    region: 'منطقه ۳ تهران',
    city: 'تهران',
    distanceKm: 1.2,
    coords: {
      lat: 35.7592,
      lng: 51.4083,
      mapX: 46,
      mapY: 48
    },
    phone: '021-88776655',
    workingHours: '۰۷:۳۰ الی ۱۹:۳۰ (یکسره)',
    activeCounters: 8,
    currentWaitingQueue: 2,
    supportedCategoryIds: ['identity', 'health', 'vehicle', 'welfare', 'government', 'housing', 'banking', 'postal', 'internet']
  },
  {
    id: 'off-teh-mirdamad',
    code: '72-1890',
    name: 'پیشخوان تخصصی میرداماد',
    managerName: 'مهندس آرش طاهری',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.9,
    reviewCount: 395,
    medals: ['عضو رسمی پلتفرم', 'رتبه ۱ سرعت باجه الکترونیک', 'رضایت ۱۰۰٪ ثبت احوال'],
    specialties: ['ثبت احوال و صدور فوری شناسنامه', 'امضای دیجیتال و توکن امنیتی', 'کد پستی', 'خدمات جامع اینترنتی'],
    address: 'تهران، بلوار میرداماد، جنب ایستگاه مترو میرداماد، پلاک ۱۸۲',
    region: 'منطقه ۳ تهران',
    city: 'تهران',
    distanceKm: 0.8,
    coords: {
      lat: 35.7565,
      lng: 51.4230,
      mapX: 52,
      mapY: 45
    },
    phone: '021-22904512',
    workingHours: '۰۸:۰۰ الی ۱۹:۰۰',
    activeCounters: 7,
    currentWaitingQueue: 1,
    supportedCategoryIds: ['identity', 'banking', 'government', 'postal', 'health', 'internet']
  },
  {
    id: 'off-teh-parsian',
    code: '72-2088',
    name: 'دفتر خدمات پارسیان سعادت‌آباد',
    managerName: 'خانم دکتر مریم انصاری',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.6,
    reviewCount: 312,
    medals: ['عضو نقره‌ای پلتفرم', 'امضای دیجیتال طلایی', 'پذیرش فوری الکترونیک'],
    specialties: ['درگاه ملی مجوزها و کسب‌وکار', 'امور مالیاتی و ثبت شرکت', 'سفته الکترونیک', 'سامانه مودیان و خودنویس'],
    address: 'تهران، سعادت‌آباد، میدان کاج، بلوار سرو غربی، مجتمع نیلوفر، طبقه همکف',
    region: 'منطقه ۲ تهران',
    city: 'تهران',
    distanceKm: 2.0,
    coords: {
      lat: 35.7812,
      lng: 51.3789,
      mapX: 64,
      mapY: 36
    },
    phone: '021-22334455',
    workingHours: '۰۸:۰۰ الی ۲۰:۰۰',
    activeCounters: 6,
    currentWaitingQueue: 1,
    supportedCategoryIds: ['identity', 'government', 'banking', 'housing', 'welfare', 'internet']
  },
  {
    id: 'off-teh-1105',
    code: '72-1105',
    name: 'مرکز جامع خدمات پیشخوان آزادی',
    managerName: 'علیرضا شمس',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.7,
    reviewCount: 540,
    medals: ['عضو رسمی پلتفرم', 'بزرگترین پایگاه خدمات خودرویی', 'مدال سرعت صدور مدارک'],
    specialties: ['تعویض پلاک و کارت سوخت', 'عوارض و خلافی خودرو', 'ثبت‌نام نهضت ملی مسکن'],
    address: 'تهران، خیابان آزادی، نرسیده به میدان انقلاب، نبش کوچه نور',
    region: 'منطقه ۶ تهران',
    city: 'تهران',
    distanceKm: 2.8,
    coords: {
      lat: 35.7005,
      lng: 51.3812,
      mapX: 24,
      mapY: 32
    },
    phone: '021-66554433',
    workingHours: '۰۷:۳۰ الی ۲۰:۰۰',
    activeCounters: 10,
    currentWaitingQueue: 4,
    supportedCategoryIds: ['vehicle', 'identity', 'welfare', 'health', 'postal', 'internet']
  },
  {
    id: 'off-teh-3419',
    code: '72-3419',
    name: 'دفتر پیشخوان تجریش و نیاوران',
    managerName: 'مهندس نوید رفیعی',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.9,
    reviewCount: 289,
    medals: ['عضو رسمی پلتفرم', 'دفتر نمونه خدمات بازنشستگان', 'پاسخگویی برخط سریع'],
    specialties: ['صندوق بازنشستگی و احکام', 'کارت ملی هوشمند', 'پست و اسناد مفقودی', 'خدمات اینترنتی'],
    address: 'تهران، میدان تجریش، ابتدای خیابان باهنر (نیاوران)، جنب بانک ملی',
    region: 'منطقه ۱ تهران',
    city: 'تهران',
    distanceKm: 3.5,
    coords: {
      lat: 35.8055,
      lng: 51.4312,
      mapX: 72,
      mapY: 18
    },
    phone: '021-22778899',
    workingHours: '۰۸:۰۰ الی ۱۷:۰۰',
    activeCounters: 5,
    currentWaitingQueue: 2,
    supportedCategoryIds: ['identity', 'government', 'welfare', 'health', 'postal', 'internet']
  },
  {
    id: 'off-teh-shahrak-gharb',
    code: '72-4022',
    name: 'مرکز پیشخوان شهرک غرب (ایران‌زمین)',
    managerName: 'دکتر کامران شایسته',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.85,
    reviewCount: 410,
    medals: ['عضو رسمی پلتفرم', 'دفتر درجه یک هوشمند', 'پشتیبانی VIP مراجعین'],
    specialties: ['امور مالیاتی و ثبت شرکت', 'سفته الکترونیک و تسهیلات', 'کارت ملی', 'سامانه‌های برخط'],
    address: 'تهران، شهرک غرب، خیابان ایران‌زمین، مجتمع تجاری مروارید، طبقه ۱',
    region: 'منطقه ۲ تهران',
    city: 'تهران',
    distanceKm: 2.7,
    coords: {
      lat: 35.7620,
      lng: 51.3650,
      mapX: 60,
      mapY: 42
    },
    phone: '021-88371900',
    workingHours: '۰۸:۰۰ الی ۲۰:۰۰',
    activeCounters: 8,
    currentWaitingQueue: 2,
    supportedCategoryIds: ['banking', 'government', 'identity', 'welfare', 'internet']
  },
  {
    id: 'off-teh-pasdaran',
    code: '72-3301',
    name: 'پیشخوان پاسداران و دروس',
    managerName: 'مهندس بهزاد ناصری',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.75,
    reviewCount: 320,
    medals: ['عضو رسمی پلتفرم', 'نشان طلایی سرعت راهور', 'باجه برتر سلامت'],
    specialties: ['خلافی و نقل و انتقال پلاک', 'دفترچه بیمه سلامت', 'کارت هوشمند ملی'],
    address: 'تهران، خیابان پاسداران، نبش بوستان پنجم، برج تندیس، همکف',
    region: 'منطقه ۴ تهران',
    city: 'تهران',
    distanceKm: 3.1,
    coords: {
      lat: 35.7725,
      lng: 51.4610,
      mapX: 68,
      mapY: 28
    },
    phone: '021-22589001',
    workingHours: '۰۸:۰۰ الی ۱۹:۰۰',
    activeCounters: 6,
    currentWaitingQueue: 2,
    supportedCategoryIds: ['vehicle', 'health', 'identity', 'postal', 'internet']
  },
  {
    id: 'off-teh-sadeghiyeh',
    code: '72-6110',
    name: 'دفتر پیشخوان فلکه دوم صادقیه',
    managerName: 'محمدرضا حیدری',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.65,
    reviewCount: 512,
    medals: ['عضو رسمی پلتفرم', 'پراستقبال‌ترین دفتر غرب تهران'],
    specialties: ['کارت سوخت و خلافی', 'ثبت‌نام مسکن ملی', 'شناسنامه'],
    address: 'تهران، فلکه دوم صادقیه، ابتدای بلوار آیت‌الله کاشانی، پلاک ۴۴',
    region: 'منطقه ۵ تهران',
    city: 'تهران',
    distanceKm: 3.9,
    coords: {
      lat: 35.7210,
      lng: 51.3380,
      mapX: 30,
      mapY: 55
    },
    phone: '021-44098230',
    workingHours: '۰۷:۳۰ الی ۱۹:۳۰',
    activeCounters: 9,
    currentWaitingQueue: 3,
    supportedCategoryIds: ['vehicle', 'housing', 'identity', 'welfare', 'internet']
  },
  {
    id: 'off-teh-seyed-khandan',
    code: '72-7720',
    name: 'مرکز خدمات پیشخوان سیدخندان',
    managerName: 'مهندس نادر کریمیان',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.7,
    reviewCount: 290,
    medals: ['عضو رسمی پلتفرم', 'قطب خدمات پستی و تاییدیه نشانی'],
    specialties: ['رهگیری مرسولات و اسناد مفقودی', 'کد پستی و احراز نشانی', 'ثبت شرکت'],
    address: 'تهران، زیر پل سیدخندان، ابتدای خیابان سهروردی شمالی، پلاک ۵۹۰',
    region: 'منطقه ۷ تهران',
    city: 'تهران',
    distanceKm: 2.4,
    coords: {
      lat: 35.7420,
      lng: 51.4460,
      mapX: 55,
      mapY: 40
    },
    phone: '021-88741020',
    workingHours: '۰۸:۰۰ الی ۱۸:۳۰',
    activeCounters: 6,
    currentWaitingQueue: 2,
    supportedCategoryIds: ['postal', 'housing', 'government', 'identity', 'internet']
  },
  {
    id: 'off-teh-piroozi',
    code: '72-9014',
    name: 'پیشخوان بزرگ شرق پیروزی',
    managerName: 'مهدی داوودی',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.6,
    reviewCount: 480,
    medals: ['عضو رسمی پلتفرم', 'بزرگترین پایگاه خدمات خودرویی شرق'],
    specialties: ['عوارض و خلافی خودرو', 'تعویض پلاک', 'کالابرگ الکترونیک و یارانه'],
    address: 'تهران، خیابان پیروزی، روبه‌روی مترو نبرد، پلاک ۳۲۲',
    region: 'منطقه ۱۴ تهران',
    city: 'تهران',
    distanceKm: 5.2,
    coords: {
      lat: 35.6940,
      lng: 51.4790,
      mapX: 75,
      mapY: 70
    },
    phone: '021-33129080',
    workingHours: '۰۸:۰۰ الی ۲۰:۰۰',
    activeCounters: 8,
    currentWaitingQueue: 3,
    supportedCategoryIds: ['vehicle', 'welfare', 'identity', 'postal', 'internet']
  },
  {
    id: 'off-teh-punak',
    code: '72-3904',
    name: 'پیشخوان پونک و سردار جنگل',
    managerName: 'مهندس احسان رستمی',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.8,
    reviewCount: 340,
    medals: ['عضو رسمی پلتفرم', 'پاسخگویی برخط زیر ۱۰ دقیقه', 'برگزیده شهروندان غرب'],
    specialties: ['کارت هوشمند ملی و شناسنامه', 'درگاه ملی مجوزها', 'سفته الکترونیک'],
    address: 'تهران، میدان پونک، بلوار میرزابابایی، جنب بانک ملت، پلاک ۸۸',
    region: 'منطقه ۵ تهران',
    city: 'تهران',
    distanceKm: 3.6,
    coords: {
      lat: 35.7680,
      lng: 51.3410,
      mapX: 35,
      mapY: 38
    },
    phone: '021-44498010',
    workingHours: '۰۸:۰۰ الی ۱۹:۳۰',
    activeCounters: 7,
    currentWaitingQueue: 1,
    supportedCategoryIds: ['identity', 'government', 'banking', 'housing', 'internet']
  },
  {
    id: 'off-teh-ekbatan',
    code: '72-8230',
    name: 'پیشخوان شهرک اکباتان',
    managerName: 'رویا کیانی',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.75,
    reviewCount: 375,
    medals: ['عضو رسمی پلتفرم', 'باجه مجهز الکترونیک', 'امضای دیجیتال و ثنا'],
    specialties: ['احراز هویت ثنا و ابلاغیه', 'استعلام اعتبارسنجی بانکی', 'عوارض خودرو'],
    address: 'تهران، شهرک اکباتان، فاز ۱، بازارچه شماره ۳، طبقه اول',
    region: 'منطقه ۵ تهران',
    city: 'تهران',
    distanceKm: 4.3,
    coords: {
      lat: 35.7080,
      lng: 51.3120,
      mapX: 20,
      mapY: 62
    },
    phone: '021-44669012',
    workingHours: '۰۸:۰۰ الی ۱۹:۰۰',
    activeCounters: 6,
    currentWaitingQueue: 2,
    supportedCategoryIds: ['government', 'banking', 'vehicle', 'identity', 'internet']
  },
  {
    id: 'off-teh-5512',
    code: '72-5512',
    name: 'دفتر پیشخوان بازار بزرگ خیام',
    managerName: 'حاج محمود معتمدی',
    membershipStatus: 'registered_online',
    isOnline: true,
    rating: 4.6,
    reviewCount: 620,
    medals: ['عضو رسمی پلتفرم', 'مرکز تخصصی اصناف بازار', 'تسویه آنی سفته الکترونیک'],
    specialties: ['کارت بهداشت اصناف', 'اظهارنامه مالیاتی', 'مجوزهای کسب‌وکار'],
    address: 'تهران، خیابان خیام، روبه‌روی درب ورودی بازار بزرگ، پاساژ صدری',
    region: 'منطقه ۱۲ تهران',
    city: 'تهران',
    distanceKm: 3.8,
    coords: {
      lat: 35.6792,
      lng: 51.4199,
      mapX: 36,
      mapY: 76
    },
    phone: '021-55667788',
    workingHours: '۰۸:۰۰ الی ۱۶:۳۰',
    activeCounters: 7,
    currentWaitingQueue: 4,
    supportedCategoryIds: ['government', 'health', 'banking', 'identity', 'internet']
  },

  // ----------------------------------------------------
  // دسته ۲: عضو پلتفرم و هم‌اکنون آفلاین (REGISTERED & OFFLINE)
  // ----------------------------------------------------
  {
    id: 'off-teh-motahari',
    code: '72-2460',
    name: 'دفتر پیشخوان مطهری و قائم‌مقام',
    managerName: 'فاطمه ابراهیمی',
    membershipStatus: 'registered_offline',
    isOnline: false,
    rating: 4.3,
    reviewCount: 142,
    medals: ['عضو رسمی سامانه پلتفرم', 'سابقه ۷ ساله خدمت‌رسانی'],
    specialties: ['عوارض شهرداری و نوسازی', 'تاییدیه پستی و نشانی'],
    address: 'تهران، خیابان استاد مطهری، تقاطع قائم‌مقام فراهانی، پلاک ۳۱۰',
    region: 'منطقه ۶ تهران',
    city: 'تهران',
    distanceKm: 1.9,
    coords: {
      lat: 35.7268,
      lng: 51.4172,
      mapX: 48,
      mapY: 60
    },
    phone: '021-88849012',
    workingHours: '۰۸:۰۰ الی ۱۴:۳۰ (پایان شیفت کاری امروز)',
    activeCounters: 3,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['housing', 'postal', 'welfare']
  },
  {
    id: 'off-teh-0941',
    code: '72-0941',
    name: 'دفتر پیشخوان تهرانپارس',
    managerName: 'حسین توکلی',
    membershipStatus: 'registered_offline',
    isOnline: false,
    rating: 4.5,
    reviewCount: 198,
    medals: ['عضو رسمی سامانه پلتفرم', 'سابقه درخشان ۱۰ ساله'],
    specialties: ['خدمات بیمه و سلامت', 'شهرداری و کد پستی'],
    address: 'تهران، فلکه اول تهرانپارس، خیابان رشید جنوبی، پلاک ۵۶',
    region: 'منطقه ۴ تهران',
    city: 'تهران',
    distanceKm: 4.5,
    coords: {
      lat: 35.7312,
      lng: 51.5289,
      mapX: 84,
      mapY: 58
    },
    phone: '021-77889900',
    workingHours: '۰۸:۰۰ الی ۱۵:۰۰ (هم‌اکنون غیرفعال)',
    activeCounters: 4,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['health', 'housing', 'postal', 'identity']
  },
  {
    id: 'off-teh-yousef-abad',
    code: '72-5188',
    name: 'پیشخوان یوسف‌آباد (ابن‌سینا)',
    managerName: 'زهره مقدسی',
    membershipStatus: 'registered_offline',
    isOnline: false,
    rating: 4.4,
    reviewCount: 168,
    medals: ['عضو رسمی پلتفرم', 'تکریم ارباب رجوع'],
    specialties: ['صندوق بازنشستگی', 'ثبت احوال و گواهی تجرد'],
    address: 'تهران، یوسف‌آباد، خیابان اسدآبادی، نبش خیابان ۳۴، پلاک ۱۱۰',
    region: 'منطقه ۶ تهران',
    city: 'تهران',
    distanceKm: 2.1,
    coords: {
      lat: 35.7380,
      lng: 51.4050,
      mapX: 45,
      mapY: 52
    },
    phone: '021-88065544',
    workingHours: '۰۸:۰۰ الی ۱۵:۰۰ (هم‌اکنون غیرفعال)',
    activeCounters: 4,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['government', 'identity', 'welfare']
  },
  {
    id: 'off-teh-haft-tir',
    code: '72-8812',
    name: 'دفتر پیشخوان میدان هفت‌تیر',
    managerName: 'سعید صبوری',
    membershipStatus: 'registered_offline',
    isOnline: false,
    rating: 4.35,
    reviewCount: 220,
    medals: ['عضو رسمی پلتفرم', 'دسترسی آسان مترو'],
    specialties: ['کارت بهداشت اصناف', 'اظهارنامه مالیاتی مشاغل'],
    address: 'تهران، ضلع شمال غربی میدان شهدای هفتم تیر، کوچه مهاجر، پلاک ۱۲',
    region: 'منطقه ۷ تهران',
    city: 'تهران',
    distanceKm: 2.9,
    coords: {
      lat: 35.7180,
      lng: 51.4250,
      mapX: 42,
      mapY: 65
    },
    phone: '021-88301144',
    workingHours: '۰۸:۰۰ الی ۱۴:۰۰ (هم‌اکنون غیرفعال)',
    activeCounters: 3,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['health', 'government', 'banking']
  },
  {
    id: 'off-teh-narmak',
    code: '72-1643',
    name: 'دفتر پیشخوان نارمک (هفت‌حوض)',
    managerName: 'حمیدرضا زمانی',
    membershipStatus: 'registered_offline',
    isOnline: false,
    rating: 4.45,
    reviewCount: 265,
    medals: ['عضو رسمی پلتفرم', 'خدمت‌رسانی تخصصی بازنشستگان شرق'],
    specialties: ['صندوق بازنشستگی کشوری', 'سهام عدالت و یارانه', 'بیمه سلامت'],
    address: 'تهران، نارمک، میدان نبوت (هفت‌حوض)، کوچه عظیمی، پلاک ۴',
    region: 'منطقه ۸ تهران',
    city: 'تهران',
    distanceKm: 4.8,
    coords: {
      lat: 35.7350,
      lng: 51.4980,
      mapX: 80,
      mapY: 50
    },
    phone: '021-77941200',
    workingHours: '۰۸:۰۰ الی ۱۵:۰۰ (هم‌اکنون غیرفعال)',
    activeCounters: 4,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['government', 'welfare', 'health', 'identity']
  },
  {
    id: 'off-teh-nazi-abad',
    code: '72-7102',
    name: 'مرکز پیشخوان نازی‌آباد (بازار دوم)',
    managerName: 'حاج علی اکبری',
    membershipStatus: 'registered_offline',
    isOnline: false,
    rating: 4.25,
    reviewCount: 210,
    medals: ['عضو رسمی پلتفرم', 'خدمات معیشتی و رفاهی جنوب'],
    specialties: ['یارانه و کالابرگ', 'کارت بهداشت اصناف', 'گواهی پستی'],
    address: 'تهران، نازی‌آباد، بازار دوم، خیابان مدائن، پلاک ۱۷۰',
    region: 'منطقه ۱۶ تهران',
    city: 'تهران',
    distanceKm: 6.5,
    coords: {
      lat: 35.6420,
      lng: 51.3980,
      mapX: 40,
      mapY: 88
    },
    phone: '021-55067890',
    workingHours: '۰۸:۰۰ الی ۱۳:۳۰ (هم‌اکنون غیرفعال)',
    activeCounters: 3,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['welfare', 'health', 'postal', 'identity']
  },
  {
    id: 'off-teh-jannat-abad',
    code: '72-3510',
    name: 'دفتر پیشخوان جنت‌آباد مرکزی',
    managerName: 'سید رضا حسینی',
    membershipStatus: 'registered_offline',
    isOnline: false,
    rating: 4.5,
    reviewCount: 175,
    medals: ['عضو رسمی سامانه پلتفرم'],
    specialties: ['تاییدیه پستی و نشانی', 'عوارض خودرو و طرح ترافیک'],
    address: 'تهران، جنت‌آباد مرکزی، بالاتر از تقاطع همت، کوچه اقاقیا، پلاک ۱۲',
    region: 'منطقه ۵ تهران',
    city: 'تهران',
    distanceKm: 4.1,
    coords: {
      lat: 35.7530,
      lng: 51.3120,
      mapX: 22,
      mapY: 44
    },
    phone: '021-44412390',
    workingHours: '۰۸:۰۰ الی ۱۴:۳۰ (هم‌اکنون غیرفعال)',
    activeCounters: 4,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['vehicle', 'postal', 'identity']
  },

  // ----------------------------------------------------
  // دسته ۳: ثبت‌نام نکرده در پلتفرم (UNREGISTERED / NON-MEMBER)
  // ----------------------------------------------------
  {
    id: 'off-teh-unreg-fatemi',
    code: '72-0112',
    name: 'دفتر پیشخوان میدان فاطمی (سنتی)',
    managerName: 'قاسم فراهانی',
    membershipStatus: 'unregistered',
    isOnline: false,
    rating: 3.8,
    reviewCount: 45,
    medals: ['دایرکتوری کشوری دفاتر (غیرعضو پلتفرم)'],
    specialties: ['خدمات سنتی پستی و سیم‌کارت'],
    address: 'تهران، میدان جهاد (فاطمی)، ابتدای خیابان جویبار، پلاک ۳۸',
    region: 'منطقه ۶ تهران',
    city: 'تهران',
    distanceKm: 2.3,
    coords: {
      lat: 35.7205,
      lng: 51.4075,
      mapX: 47,
      mapY: 62
    },
    phone: '021-88965412',
    workingHours: '۰۸:۳۰ الی ۱۳:۳۰ (پذیرش صرفاً حضوری سنتی)',
    activeCounters: 2,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['postal', 'identity']
  },
  {
    id: 'off-teh-unreg-jomhoori',
    code: '72-0440',
    name: 'دفتر خدمات پیشخوان جمهوری و حافظ',
    managerName: 'بهرام کاظمی',
    membershipStatus: 'unregistered',
    isOnline: false,
    rating: 3.9,
    reviewCount: 62,
    medals: ['دایرکتوری کشوری دفاتر (غیرعضو پلتفرم)'],
    specialties: ['امور امور مشترکین تلفن همراه و عوارض'],
    address: 'تهران، خیابان جمهوری اسلامی، تقاطع پل حافظ، پاساژ معتمدی، همکف',
    region: 'منطقه ۱۱ تهران',
    city: 'تهران',
    distanceKm: 3.6,
    coords: {
      lat: 35.6960,
      lng: 51.4110,
      mapX: 43,
      mapY: 72
    },
    phone: '021-66708912',
    workingHours: '۰۹:۰۰ الی ۱۴:۰۰ (پذیرش صرفاً حضوری سنتی)',
    activeCounters: 2,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['government', 'postal']
  },
  {
    id: 'off-teh-unreg-qazvin',
    code: '72-0789',
    name: 'دفتر پیشخوان میدان قزوین',
    managerName: 'حسین جودکی',
    membershipStatus: 'unregistered',
    isOnline: false,
    rating: 3.7,
    reviewCount: 38,
    medals: ['دایرکتوری کشوری دفاتر (غیرعضو پلتفرم)'],
    specialties: ['گواهی پستی و امور هویتی حضوری'],
    address: 'تهران، میدان قزوین، خیابان کارگر جنوبی، پلاک ۵۸۰',
    region: 'منطقه ۱۱ تهران',
    city: 'تهران',
    distanceKm: 4.7,
    coords: {
      lat: 35.6720,
      lng: 51.3930,
      mapX: 38,
      mapY: 82
    },
    phone: '021-55418902',
    workingHours: '۰۸:۰۰ الی ۱۳:۰۰ (پذیرش صرفاً حضوری سنتی)',
    activeCounters: 1,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['identity', 'postal']
  },
  {
    id: 'off-teh-unreg-emam-hossein',
    code: '72-0315',
    name: 'دفتر پیشخوان میدان امام حسین',
    managerName: 'علی‌اصغر مرادی',
    membershipStatus: 'unregistered',
    isOnline: false,
    rating: 3.6,
    reviewCount: 51,
    medals: ['دایرکتوری کشوری دفاتر (غیرعضو پلتفرم)'],
    specialties: ['خدمات بیمه و قبوض شهری'],
    address: 'تهران، ضلع جنوب شرقی میدان امام حسین، ابتدای خیابان ۱۷ شهریور',
    region: 'منطقه ۱۲ تهران',
    city: 'تهران',
    distanceKm: 4.9,
    coords: {
      lat: 35.7030,
      lng: 51.4550,
      mapX: 62,
      mapY: 66
    },
    phone: '021-77531245',
    workingHours: '۰۸:۳۰ الی ۱۴:۰۰ (پذیرش صرفاً حضوری سنتی)',
    activeCounters: 2,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['welfare', 'postal']
  },
  {
    id: 'off-teh-unreg-ray',
    code: '72-0690',
    name: 'دفتر پیشخوان شهرری (فرمانداری)',
    managerName: 'محسن کرمی',
    membershipStatus: 'unregistered',
    isOnline: false,
    rating: 4.0,
    reviewCount: 78,
    medals: ['دایرکتوری کشوری دفاتر (غیرعضو پلتفرم)'],
    specialties: ['ثبت‌نام سیم‌کارت و تاییدیه پستی'],
    address: 'شهرری، میدان فرمانداری، بلوار امام حسین، پلاک ۴۲',
    region: 'منطقه ۲۰ تهران (شهرری)',
    city: 'شهرری',
    distanceKm: 8.2,
    coords: {
      lat: 35.5920,
      lng: 51.4390,
      mapX: 50,
      mapY: 96
    },
    phone: '021-55904510',
    workingHours: '۰۸:۰۰ الی ۱۳:۳۰ (پذیرش صرفاً حضوری سنتی)',
    activeCounters: 2,
    currentWaitingQueue: 0,
    supportedCategoryIds: ['identity', 'postal', 'government']
  }
];

export const MOCK_LEGAL_DELEGATIONS: LegalDelegation[] = [
  {
    id: 'del-1',
    principalName: 'حسین رضایی دهکردی',
    principalNationalId: '1289456120',
    agentName: 'محمدرضا رضایی دهکردی',
    agentNationalId: '0019845621',
    relation: 'پدر (ولی/موکل)',
    validUntil: '۱۴۰۴/۱۲/۲۹',
    allowedServices: ['خودرو و ترافیک', 'صندوق بازنشستگی', 'ثبت احوال و شناسنامه'],
    maxAmountTomans: 2000000,
    status: 'active',
    documentNumber: 'وکالت‌نامه دفترخانه ۴۰۸ شمیران - ش/۹۸۴۱'
  }
];

export const INITIAL_CITIZEN_PROFILE: CitizenProfile = {
  fullName: 'محمدرضا رضایی دهکردی',
  nationalId: '0019845621',
  mobile: '09123456789',
  fatherName: 'حسین',
  birthDate: '1368/04/15',
  postalCode: '1997864321',
  address: 'تهران، خیابان میرداماد، میدان مادر، خیابان وزیری‌پور، پلاک ۱۸، واحد ۴',
  tier: 'silver',
  tierName: 'شهروند تایید هویت شده (سطح نقره‌ای)',
  sanaVerified: true,
  digitalSignatureActive: false,
  walletBalance: 485000,
  creditScore: 820,
  delegations: MOCK_LEGAL_DELEGATIONS,
  documents: [
    {
      id: 'doc-nid',
      title: 'کارت هوشمند ملی',
      type: 'national_id',
      docNumber: '0019845621',
      issueDate: '1398/02/10',
      expiryDate: '1408/02/10',
      isVerified: true,
      category: 'هویتی',
      attributes: [
        { label: 'شماره سریال کارت', value: '4A981240' },
        { label: 'وضعیت ثبت احوال', value: 'تایید شده و فعال' }
      ]
    },
    {
      id: 'doc-birth',
      title: 'شناسنامه دیجیتال',
      type: 'birth_cert',
      docNumber: '14589',
      issueDate: '1368/04/18',
      isVerified: true,
      category: 'هویتی',
      attributes: [
        { label: 'محل صدور', value: 'تهران حوزه ۳' },
        { label: 'سری و سریال', value: 'الف/۶۸ - ۱۲۴۹۸۰' }
      ]
    },
    {
      id: 'doc-license',
      title: 'گواهینامه رانندگی پایه دوم',
      type: 'driver_license',
      docNumber: '984512034',
      issueDate: '1399/06/15',
      expiryDate: '1409/06/15',
      isVerified: true,
      category: 'رانندگی و خودرو',
      attributes: [
        { label: 'پایه گواهینامه', value: 'پایه دوم و موتورسیکلت' },
        { label: 'نمره منفی خلافی', value: 'صفر (خوش‌حساب)' }
      ]
    },
    {
      id: 'doc-postal',
      title: 'گواهی تاییدیه نشانی و کد پستی',
      type: 'postal_cert',
      docNumber: '1997864321',
      issueDate: '1403/01/20',
      isVerified: true,
      category: 'سکونت و ملک',
      attributes: [
        { label: 'کد پستی', value: '1997864321' },
        { label: 'تاییدیه شهرداری', value: 'منطقه ۳ تهران - ملک مسکونی' }
      ]
    },
    {
      id: 'doc-health',
      title: 'دفترچه بیمه سلامت ایرانیان',
      type: 'health_booklet',
      docNumber: 'HLT-984021',
      issueDate: '1402/10/01',
      expiryDate: '1404/10/01',
      isVerified: true,
      category: 'سلامت و درمان',
      attributes: [
        { label: 'صندوق بیمه', value: 'ایرانیان دهک ۴' },
        { label: 'پزشک خانواده', value: 'مرکز بهداشت ونک' }
      ]
    }
  ]
};

export const INITIAL_CASES: CaseRequest[] = [
  {
    id: 'case-1001',
    trackingCode: 'PK-1403-89412',
    serviceId: 'id-birth-cert',
    serviceTitle: 'تعویض و صدور المثنی شناسنامه',
    serviceCategory: 'هویتی و ثبت احوال',
    serviceTag: 'semi-online',
    status: 'action_required', // Demonstrates the return for document fix required in prompt!
    createdAt: '۱۴۰۳/۰۶/۰۲ - ساعت ۱۰:۲۴',
    updatedAt: '۱۴۰۳/۰۶/۰۳ - ساعت ۱۴:۱۵',
    lastChangeText: '۳ ساعت پیش',
    deadlineCountdown: '۲ روز و ۴ ساعت تا انقضا',
    expiresInDays: 2,
    currentStepNumber: 3,
    totalSteps: 6,
    turnOwner: 'citizen',
    turnOwnerText: 'نوبت شما (رفع نقص مدرک)',
    estimatedCompletion: '⏸ تخمین متوقف — در انتظار اقدام شما',
    citizenName: 'محمدرضا رضایی دهکردی',
    citizenNationalId: '0019845621',
    feePaid: 95000,
    officeShareFee: 66500,
    officeId: 'off-teh-1024',
    assignedOffice: MOCK_OFFICES[0],
    returnReason: 'تصویر شناسنامه قدیمی بارگذاری شده تار بوده و شماره سریال صفحه اول ناخوانا می‌باشد. لطفاً عکس باکیفیت و بدون لرزش مجدداً بارگذاری نمایید.',
    returnReasonCode: 'DOC_BLUR',
    requiredFixField: 'شناسنامه قدیمی — صفحه اول',
    uploadedDocuments: [
      { name: 'شناسنامه قدیمی (صفحه اول)', type: 'image/jpeg', verified: false, code: 'OLD_CERT_P1', hasBlurWarning: true },
      { name: 'عکس پرسنلی ۴×۳ جدید', type: 'image/jpeg', verified: true, code: 'PHOTO_3X4' },
      { name: 'تاییدیه کد پستی معتبر', type: 'pdf', verified: true, code: 'POSTAL_CONFIRM' }
    ],
    timeline: [
      {
        id: 'step-1',
        title: 'ثبت اولیه درخواست و پرداخت کارمزد',
        description: 'درخواست در سامانه ثبت و کارمزد با موفقیت کسر شد.',
        timestamp: '۰۶/۰۲ - ۱۰:۲۴',
        status: 'done',
        icon: 'CheckCircle',
        turnOwner: 'system',
        turnOwnerLabel: 'سیستم',
        durationActual: '۲ دقیقه',
        durationTypical: '۲ دقیقه'
      },
      {
        id: 'step-2',
        title: 'ارجاع هوشمند و پذیرش توسط دفتر پیشخوان',
        description: 'دفتر پیشخوان ولی‌عصر (کد ۱۴۰۲) پرونده را پذیرفت.',
        timestamp: '۰۶/۰۲ - ۱۰:۲۶',
        status: 'done',
        icon: 'Building2',
        turnOwner: 'office',
        turnOwnerLabel: 'دفتر پیشخوان',
        durationActual: '۲ دقیقه',
        durationTypical: 'زیر ۵ دقیقه',
        officeNote: 'کارشناس پذیرش: سرکار خانم کریمی'
      },
      {
        id: 'step-3',
        title: 'بررسی مدارک و تطبیق هویت — عودت شد',
        description: 'تصویر شناسنامه ارسالی تار است و شماره سریال ناخواناست.',
        timestamp: '۰۶/۰۳ - ۱۴:۱۵',
        status: 'warning',
        icon: 'AlertTriangle',
        turnOwner: 'citizen',
        turnOwnerLabel: 'نوبت شما',
        durationActual: 'در انتظار کاربر',
        durationTypical: 'معمولاً ۴ ساعت',
        officeNote: 'اسکن صفحه اول تار است، سریال ناخوانا'
      },
      {
        id: 'step-4',
        title: 'استعلام از سامانه ثبت احوال کشور',
        description: 'پس از اصلاح مدرک، تاییدیه برخط ثبت احوال اخذ خواهد شد.',
        status: 'pending',
        icon: 'Database',
        turnOwner: 'government',
        turnOwnerLabel: 'سازمان ثبت احوال',
        durationTypical: 'معمولاً ۴ ساعت'
      },
      {
        id: 'step-5',
        title: 'چاپ و صدور جلد جدید شناسنامه',
        description: 'تولید سند در چاپخانه دولتی ثبت احوال',
        status: 'pending',
        icon: 'Printer',
        turnOwner: 'government',
        turnOwnerLabel: 'چاپخانه دولتی',
        durationTypical: '۲ روز کاری'
      },
      {
        id: 'step-6',
        title: 'ارسال با پست پیشتاز به آدرس شهروند',
        description: 'تحویل پاکت محرمانه پستی درب منزل با کد رهگیری',
        status: 'pending',
        icon: 'Truck',
        turnOwner: 'postal',
        turnOwnerLabel: 'شرکت ملی پست',
        durationTypical: '۳ روز کاری'
      }
    ]
  },
  {
    id: 'case-1002',
    trackingCode: 'PK-1403-91204',
    serviceId: 'vh-penalties',
    serviceTitle: 'استعلام و تسویه تجمیعی خلافی خودرو',
    serviceCategory: 'خدمات خودرویی و ترافیک',
    serviceTag: 'online',
    status: 'completed',
    createdAt: '۱۴۰۳/۰۶/۰۱ - ساعت ۱۶:۴۰',
    updatedAt: '۱۴۰۳/۰۶/۰۱ - ساعت ۱۶:۴۲',
    lastChangeText: '۱ روز پیش',
    currentStepNumber: 3,
    totalSteps: 3,
    turnOwner: 'system',
    turnOwnerText: 'تکمیل و مختومه شد',
    estimatedCompletion: 'تحویل و تسویه شد',
    citizenName: 'محمدرضا رضایی',
    citizenNationalId: '0019845621',
    feePaid: 15000,
    officeShareFee: 10500,
    officeId: 'off-teh-1105',
    assignedOffice: MOCK_OFFICES[2],
    uploadedDocuments: [
      { name: 'مفاصاحساب رسمی پلیس راهور', type: 'pdf', verified: true }
    ],
    timeline: [
      {
        id: 'step-1',
        title: 'ثبت استعلام و اتصال به سرور راهور',
        description: 'استعلام برخط شماره پلاک ۷۲- ایران ۳۳',
        timestamp: '۰۶/۰۱ - ۱۶:۴۰',
        status: 'done',
        icon: 'CheckCircle',
        turnOwner: 'system',
        turnOwnerLabel: 'سیستم',
        durationActual: '۱ دقیقه'
      },
      {
        id: 'step-2',
        title: 'پرداخت و تسویه کارمزد',
        description: 'مبلغ تسویه خلافی با موفقیت تایید گردید.',
        timestamp: '۰۶/۰۱ - ۱۶:۴۱',
        status: 'done',
        icon: 'CreditCard',
        turnOwner: 'system',
        turnOwnerLabel: 'سیستم',
        durationActual: '۱ دقیقه'
      },
      {
        id: 'step-3',
        title: 'صدور برگه مفاصاحساب رسمی با شناسه رهگیری',
        description: 'شناسه پیگیری ناجا صادر شد و رسید ممهور به امضای دیجیتال رسید.',
        timestamp: '۰۶/۰۱ - ۱۶:۴۲',
        status: 'done',
        icon: 'FileCheck',
        turnOwner: 'system',
        turnOwnerLabel: 'سیستم',
        durationActual: '۱ دقیقه'
      }
    ]
  },
  {
    id: 'case-1003',
    trackingCode: 'PK-1403-94881',
    serviceId: 'gv-business-permit',
    serviceTitle: 'صدور مجوز کسب‌وکار (درگاه ملی مجوزها)',
    serviceCategory: 'خدمات دولتی و کسب‌وکار',
    serviceTag: 'semi-online',
    status: 'government_inquiry',
    createdAt: '۱۴۰۳/۰۵/۲۸ - ساعت ۱۱:۰۰',
    updatedAt: '۱۴۰۳/۰۶/۰۲ - ساعت ۰۹:۳۰',
    lastChangeText: 'امروز ساعت ۰۹:۳۰',
    currentStepNumber: 3,
    totalSteps: 4,
    turnOwner: 'government',
    turnOwnerText: 'نوبت اماکن فراجا و امور مالیاتی',
    estimatedCompletion: '۳ روز کاری',
    citizenName: 'محمدرضا رضایی',
    citizenNationalId: '0019845621',
    feePaid: 140000,
    officeShareFee: 98000,
    officeId: 'off-teh-2088',
    assignedOffice: MOCK_OFFICES[1],
    uploadedDocuments: [
      { name: 'سند اجاره‌نامه تجاری', type: 'pdf', verified: true },
      { name: 'گواهی عدم سوء‌پیشینه', type: 'pdf', verified: true },
      { name: 'کارت بهداشت اصناف', type: 'image/jpeg', verified: true }
    ],
    timeline: [
      {
        id: 'step-1',
        title: 'ثبت و تکمیل آنلاین فرم درگاه ملی',
        description: 'رسته شغلی: فروشگاه تجهیزات الکترونیک و رایانه',
        timestamp: '۰۵/۲۸ - ۱۱:۰۰',
        status: 'done',
        icon: 'CheckCircle',
        turnOwner: 'system',
        turnOwnerLabel: 'سیستم'
      },
      {
        id: 'step-2',
        title: 'پذیرش در دفتر پیشخوان هوشمند سعادت‌آباد',
        description: 'دفتر ۲۰۸۸ مدارک بارگذاری شده را تایید و بارکد اختصاص داد.',
        timestamp: '۰۵/۲۸ - ۱۱:۱۵',
        status: 'done',
        icon: 'Building2',
        turnOwner: 'office',
        turnOwnerLabel: 'دفتر پیشخوان'
      },
      {
        id: 'step-3',
        title: 'استعلام از اماکن فراجا و سازمان امور مالیاتی',
        description: 'پرونده هم‌اکنون در کارتابل نظارتی پلیس نظارت بر اماکن عمومی قرار دارد.',
        timestamp: '۰۶/۰۲ - ۰۹:۳۰',
        status: 'current',
        icon: 'Clock',
        turnOwner: 'government',
        turnOwnerLabel: 'اماکن فراجا',
        durationTypical: 'معمولاً ۲ روز',
        officeNote: 'استعلام مالیاتی تایید شد. در انتظار تاییدیه اماکن.'
      },
      {
        id: 'step-4',
        title: 'صدور شناسه یکتای مجوز کسب (QR Code)',
        description: 'صدور پروانه کسب رسمی با اعتبار ۳ ساله',
        status: 'pending',
        icon: 'Award',
        turnOwner: 'government',
        turnOwnerLabel: 'درگاه ملی مجوزها'
      }
    ]
  }
];

export const MOCK_CHAT_MESSAGES: ChatMessage[] = [
  {
    id: 'msg-1',
    caseId: 'case-1001',
    sender: 'office',
    senderName: 'کارشناس سجلی (دفتر ولی‌عصر)',
    text: 'سلام و احترام آقای رضایی. تصویر شناسنامه‌ای که ارسال کردید در قسمت سریال گوشه بالا تار افتاده. اگر امکانش هست لطفاً یک تصویر شفاف‌تر بدون انعکاس نور مجدداً بارگذاری کنید تا سریعاً برای تاییدیه ثبت احوال ارسال کنیم.',
    time: '۰۶/۰۳ - ساعت ۱۴:۱۶'
  },
  {
    id: 'msg-2',
    caseId: 'case-1001',
    sender: 'citizen',
    senderName: 'محمدرضا رضایی',
    text: 'سلام، متشکرم. الان مجدداً با نور کافی و اسکن واضح ارسال می‌کنم.',
    time: '۰۶/۰۳ - ساعت ۱۴:۲۵'
  }
];


export const MOCK_TRANSACTIONS: WalletTransaction[] = [
  {
    id: 'tx-1',
    title: 'شارژ آنلاین کیف پول شهروندی',
    amount: 500000,
    type: 'deposit',
    date: '۱۴۰۳/۰۶/۰۱ - ۱۲:۳۰',
    trackingId: 'TRX-980124',
    status: 'success'
  },
  {
    id: 'tx-2',
    title: 'کارمزد ثبت درخواست تعویض شناسنامه',
    amount: -95000,
    type: 'service_fee',
    date: '۱۴۰۳/۰۶/۰۲ - ۱۰:۲۴',
    trackingId: 'TRX-980287',
    status: 'success'
  },
  {
    id: 'tx-3',
    title: 'تسویه استعلام خلافی خودرو',
    amount: -15000,
    type: 'service_fee',
    date: '۱۴۰۳/۰۶/۰۱ - ۱۶:۴۰',
    trackingId: 'TRX-979841',
    status: 'success'
  },
  {
    id: 'tx-4',
    title: 'پاداش نقدی شهروند طلایی (کش‌بک)',
    amount: 25000,
    type: 'cashback',
    date: '۱۴۰۳/۰۵/۳۰ - ۰۹:۰۰',
    trackingId: 'TRX-975510',
    status: 'success'
  }
];

export const MOCK_APPOINTMENTS: Appointment[] = [
  {
    id: 'app-1',
    officeId: 'off-teh-1024',
    officeName: 'دفتر پیشخوان دولت ولی‌عصر (کد ۱۴۰۲)',
    serviceTitle: 'کارت هوشمند ملی (اسکن بیومتریک چهره و اثر انگشت)',
    date: '۱۴۰۳/۰۶/۰۷ (دوشنبه)',
    timeSlot: '۱۰:۳۰ الی ۱۱:۰۰',
    trackingCode: 'NOBAT-98410',
    status: 'active',
    reminderEnabled: true,
    reminderType: 'all',
    reminderTime: '۰۹:۳۰ (۱ ساعت قبل از نوبت)',
    address: 'تهران، خیابان ولی‌عصر، بالاتر از میدان ونک، نبش کوچه شریفی، پلاک ۲۴',
    requiredDocs: ['اصل شناسنامه عکس‌دار', 'کد پستی ۱۰ رقمی محل سکونت', 'رسید پیش‌ثبت‌نام اینترنتی']
  }
];
