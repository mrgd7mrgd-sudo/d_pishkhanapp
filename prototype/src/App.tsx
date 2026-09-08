import React, { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  CATEGORIES, 
  CITIZEN_SERVICES, 
  MOCK_OFFICES, 
  INITIAL_CITIZEN_PROFILE, 
  INITIAL_CASES, 
  MOCK_TRANSACTIONS, 
  MOCK_APPOINTMENTS,
  MOCK_LEGAL_DELEGATIONS,
  MOCK_CHAT_MESSAGES,
  HERO_BANNER_IMAGE
} from './data/mockData';
import { 
  CitizenService, 
  PishkhanOffice, 
  CaseRequest, 
  CitizenProfile, 
  DocumentItem, 
  Appointment, 
  WalletTransaction,
  LegalDelegation,
  ChatMessage
} from './types';
import { Header } from './components/Header';
import { Navbar, NavTab } from './components/Navbar';
import { WalletCard } from './components/WalletCard';
import { WalletDetailsView } from './components/WalletDetailsView';
import { CategoryFilter } from './components/CategoryFilter';
import { ServiceRequestModal } from './components/ServiceRequestModal';
import { OfficesMap } from './components/OfficesMap';
import { CaseTrackingView } from './components/CaseTrackingView';
import { UserProfileView } from './components/UserProfileView';
import { VoiceAssistantModal } from './components/VoiceAssistantModal';
import { SmartChatbotModal } from './components/SmartChatbotModal';
import { AppointmentModal } from './components/AppointmentModal';
import { OfficePortalView } from './components/OfficePortalView';
import { MessagesView } from './components/MessagesView';
import { LegalDelegationModal } from './components/LegalDelegationModal';
import { ServicesCatalogView } from './components/ServicesCatalogView';
import { CtaSlider } from './components/CtaSlider';
import { ConsultationHubView } from './components/ConsultationHubView';
import { CitizenLoginView } from './components/CitizenLoginView';
import { OfficeLoginView } from './components/OfficeLoginView';
import { 
  Sparkles, 
  AlertTriangle, 
  Mic,
  MapPin, 
  ShieldCheck,
  ChevronLeft,
  Search,
  CheckCircle2,
  Building2,
  Users,
  Grid
} from 'lucide-react';

export default function App() {
  // App Mode: Citizen Mode vs Office Operator Desk
  const [appMode, setAppMode] = useState<'citizen' | 'office'>('citizen');

  // Authentication State
  const [isCitizenLoggedIn, setIsCitizenLoggedIn] = useState<boolean>(false);
  const [isOfficeLoggedIn, setIsOfficeLoggedIn] = useState<boolean>(false);
  const [showCitizenLoginModal, setShowCitizenLoginModal] = useState<boolean>(false);
  const [currentOfficeDesk, setCurrentOfficeDesk] = useState<PishkhanOffice>(MOCK_OFFICES[0]);

  // Navigation
  const [activeTab, setActiveTab] = useState<NavTab>('home');
  const [tabDirection, setTabDirection] = useState<number>(0);
  const [selectedCategoryId, setSelectedCategoryId] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [showConsultationPage, setShowConsultationPage] = useState(false);

  const TAB_ORDER: Record<NavTab, number> = {
    home: 0,
    services: 1,
    map: 2,
    cases: 3,
    profile: 4
  };

  const handleTabChange = (newTab: NavTab) => {
    setShowConsultationPage(false);
    if (newTab === activeTab) return;
    const currentIdx = TAB_ORDER[activeTab] ?? 0;
    const nextIdx = TAB_ORDER[newTab] ?? 0;
    // In RTL, switching to higher index (moving left) comes from left/right accordingly
    setTabDirection(nextIdx > currentIdx ? 1 : -1);
    setShowWalletDetails(false);
    setActiveTab(newTab);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Core State
  const [profile, setProfile] = useState<CitizenProfile>(INITIAL_CITIZEN_PROFILE);
  const [cases, setCases] = useState<CaseRequest[]>(INITIAL_CASES);
  const [offices, setOffices] = useState<PishkhanOffice[]>(MOCK_OFFICES);
  const [appointments, setAppointments] = useState<Appointment[]>(MOCK_APPOINTMENTS);
  const [transactions, setTransactions] = useState<WalletTransaction[]>(MOCK_TRANSACTIONS);
  const [delegations, setDelegations] = useState<LegalDelegation[]>(MOCK_LEGAL_DELEGATIONS);
  const [messages, setMessages] = useState<ChatMessage[]>(MOCK_CHAT_MESSAGES);

  // Modals
  const [activeRequestService, setActiveRequestService] = useState<CitizenService | null>(null);
  const [preSelectedOfficeId, setPreSelectedOfficeId] = useState<string | null>(null);
  const [activeAppointmentOffice, setActiveAppointmentOffice] = useState<PishkhanOffice | null>(null);
  const [showVoiceAssistant, setShowVoiceAssistant] = useState(false);
  const [showSmartChatbot, setShowSmartChatbot] = useState(false);
  const [showDelegationsModal, setShowDelegationsModal] = useState(false);
  const [focusedCaseId, setFocusedCaseId] = useState<string | null>(null);
  const [showWalletDetails, setShowWalletDetails] = useState(false);

  // Wallet top-up / transaction handler
  const handleTopUpWallet = (amount: number, customTitle?: string) => {
    setProfile(prev => ({
      ...prev,
      walletBalance: prev.walletBalance + amount
    }));

    let txTitle = customTitle;
    if (!txTitle) {
      txTitle = amount > 0 ? 'افزایش موجودی آنلاین کیف پول' : 'پرداخت کارمزد خدمت / قبض';
    }

    const newTx: WalletTransaction = {
      id: `tx-${Date.now()}`,
      title: txTitle,
      amount,
      type: amount > 0 ? 'deposit' : 'service_fee',
      date: '۱۴۰۳/۰۶/۰۲ - لحظاتی پیش',
      trackingId: `TRX-${Math.floor(100000 + Math.random() * 900000)}`,
      status: 'success'
    };

    setTransactions(prev => [newTx, ...prev]);
  };

  // Add new case (from Snapp-style dispatch)
  const handleSubmitNewCase = (newCase: CaseRequest) => {
    setCases(prev => [newCase, ...prev]);
    
    // Deduct fee if paid
    if (newCase.feePaid > 0) {
      handleTopUpWallet(-newCase.feePaid);
    }
  };

  // Fix deficient document on returned case
  const handleFixCaseDocument = (caseId: string, docName: string, newFile: string) => {
    setCases(prev => prev.map(c => {
      if (c.id === caseId) {
        const updatedTimeline = c.timeline.map(st => {
          if (st.status === 'warning') {
            return {
              ...st,
              status: 'done' as const,
              description: 'نقص مدرک توسط شهروند برطرف شد و مدارک تایید گردید.',
              timestamp: '۱۴۰۳/۰۶/۰۲ - هم‌اکنون'
            };
          }
          if (st.id === 'step-4') {
            return {
              ...st,
              status: 'current' as const,
              description: 'در حال استعلام برخط تاییدیه نهایی ثبت احوال'
            };
          }
          return st;
        });

        return {
          ...c,
          status: 'assigned_to_office' as const,
          returnReason: undefined,
          turnOwner: 'office' as const,
          timeline: updatedTimeline,
          updatedAt: '۱۴۰۳/۰۶/۰۲ - اصلاح شد'
        };
      }
      return c;
    }));
  };

  // Operator accepts case
  const handleOperatorAcceptCase = (caseId: string) => {
    setCases(prev => prev.map(c => {
      if (c.id === caseId) {
        return {
          ...c,
          status: 'assigned_to_office',
          turnOwner: 'office',
          updatedAt: 'هم‌اکنون'
        };
      }
      return c;
    }));
  };

  // Operator returns case for deficiency
  const handleOperatorReturnCase = (caseId: string, reasonCode: string, reasonTitle: string, note?: string) => {
    setCases(prev => prev.map(c => {
      if (c.id === caseId) {
        return {
          ...c,
          status: 'action_required',
          returnReasonCode: reasonCode,
          returnReason: reasonTitle,
          operatorReturnNote: note,
          turnOwner: 'citizen',
          updatedAt: 'هم‌اکنون'
        };
      }
      return c;
    }));
  };

  // Operator marks case complete
  const handleOperatorCompleteCase = (caseId: string) => {
    setCases(prev => prev.map(c => {
      if (c.id === caseId) {
        return {
          ...c,
          status: 'completed',
          turnOwner: 'completed',
          updatedAt: 'هم‌اکنون'
        };
      }
      return c;
    }));
  };

  // Send message in chat
  const handleSendMessage = (caseId: string, text: string) => {
    const userMsg: ChatMessage = {
      id: `msg-${Date.now()}`,
      caseId,
      sender: 'citizen',
      senderName: profile.fullName,
      text,
      time: 'لحظاتی پیش'
    };

    setMessages(prev => [...prev, userMsg]);

    // Simulated reply from assigned office
    setTimeout(() => {
      const officeReply: ChatMessage = {
        id: `msg-${Date.now() + 1}`,
        caseId,
        sender: 'office',
        senderName: 'کارشناس باجه سجلی دفتر پیشخوان',
        text: 'پیام شما دریافت شد. در حال استعلام اطلاعات از پایگاه داده ثبت احوال هستیم.',
        time: 'لحظاتی پیش'
      };
      setMessages(prev => [...prev, officeReply]);
    }, 1500);
  };

  // Direct Assign from Map
  const handleDirectAssignFromMap = (office: PishkhanOffice) => {
    setPreSelectedOfficeId(office.id);
    const supportedService = CITIZEN_SERVICES.find(s => office.supportedCategoryIds.includes(s.categoryId)) || CITIZEN_SERVICES[0];
    setActiveRequestService(supportedService);
  };

  // Book In-Person Appointment
  const handleBookAppointmentFromMap = (office: PishkhanOffice) => {
    setActiveAppointmentOffice(office);
  };

  const handleAddAppointment = (newApp: Appointment) => {
    setAppointments(prev => [newApp, ...prev]);
  };

  // Tier Upgrade
  const handleUpgradeTier = () => {
    setProfile(prev => ({
      ...prev,
      tier: 'gold',
      tierName: 'شهروند طلایی VIP (امضای دیجیتال فعال)',
      digitalSignatureActive: true
    }));
  };

  // Add Document to Vault
  const handleAddDocumentToVault = (newDoc: DocumentItem) => {
    setProfile(prev => ({
      ...prev,
      documents: [newDoc, ...prev.documents]
    }));
  };

  const actionRequiredCase = cases.find(c => c.status === 'action_required');

  return (
    <div className="min-h-screen bg-slate-100 text-slate-900 flex flex-col antialiased">
      
      {/* If in Office Desk Mode */}
      {appMode === 'office' ? (
        !isOfficeLoggedIn ? (
          /* Office Login Screen */
          <OfficeLoginView
            offices={offices}
            onLoginSuccess={(selectedOffice) => {
              setCurrentOfficeDesk(selectedOffice);
              setIsOfficeLoggedIn(true);
            }}
            onBackToCitizen={() => setAppMode('citizen')}
          />
        ) : (
          /* Office Operator Desk (میز کار باجه پیشخوان) */
          <div className="min-h-screen bg-slate-900 text-slate-100 flex flex-col">
            <OfficePortalView
              office={currentOfficeDesk}
              cases={cases}
              appointments={appointments}
              onAcceptCase={handleOperatorAcceptCase}
              onReturnCase={handleOperatorReturnCase}
              onCompleteCase={handleOperatorCompleteCase}
              onSwitchToCitizenMode={() => setAppMode('citizen')}
              onSwitchToCitizenView={() => setAppMode('citizen')}
            />
          </div>
        )
      ) : (
        /* Citizen Mobile Application View */
        <div className="flex-1 flex flex-col bg-slate-50">
          
          {/* Top Header Matching Screenshot (Ewano Brand & Smart Chatbot Mascot) - Hidden on Map view or Consultation page */}
          {activeTab !== 'map' && !showConsultationPage && (
            <Header
              onOpenChatbot={() => setShowSmartChatbot(true)}
              isCitizenLoggedIn={isCitizenLoggedIn}
              profile={profile}
              onOpenLoginModal={() => setShowCitizenLoginModal(true)}
              onOpenProfile={() => handleTabChange('profile')}
              onOpenOfficeLogin={() => setAppMode('office')}
            />
          )}

          {/* Main Container - Responsive Fullscreen for Map, Centered Shell for other tabs */}
          <main className={`flex-1 w-full mx-auto relative overflow-hidden ${
            activeTab === 'map' && !showConsultationPage
              ? 'p-0 max-w-none w-full h-[calc(100dvh-64px)] flex flex-col flex-1 pb-0' 
              : 'max-w-lg p-3.5 sm:p-4 pb-24'
          }`}>
            
            {showConsultationPage ? (
              <ConsultationHubView
                userWalletBalance={profile.walletBalance}
                onExecuteLinkedService={(serviceId) => {
                  const srv = CITIZEN_SERVICES.find(s => s.id === serviceId) || CITIZEN_SERVICES[0];
                  setShowConsultationPage(false);
                  setActiveRequestService(srv);
                }}
                onBackToHome={() => setShowConsultationPage(false)}
              />
            ) : (
            <AnimatePresence mode="wait" initial={false} custom={tabDirection}>
              <motion.div
                key={activeTab}
                custom={tabDirection}
                initial={{
                  opacity: 0,
                  x: tabDirection > 0 ? 28 : tabDirection < 0 ? -28 : 0,
                  filter: 'blur(4px)'
                }}
                animate={{
                  opacity: 1,
                  x: 0,
                  filter: 'blur(0px)'
                }}
                exit={{
                  opacity: 0,
                  x: tabDirection > 0 ? -24 : tabDirection < 0 ? 24 : 0,
                  filter: 'blur(4px)'
                }}
                transition={{
                  duration: 0.24,
                  ease: [0.25, 1, 0.5, 1]
                }}
                className={`w-full relative ${activeTab === 'map' ? 'h-full flex-1 flex flex-col min-h-0' : ''}`}
              >
                {/* VIEW 1: HOME (صفحه خانه) - قابل مشاهده برای همه کاربران */}
                {activeTab === 'home' && (
                  showWalletDetails ? (
                    <WalletDetailsView
                      profile={profile}
                      transactions={transactions}
                      onBack={() => setShowWalletDetails(false)}
                      onTopUp={handleTopUpWallet}
                    />
                  ) : (
                    <div className="space-y-4">
                      
                      {/* Top Wallet Section (Matching Screenshot 1) */}
                      <WalletCard
                        profile={profile}
                        isCitizenLoggedIn={isCitizenLoggedIn}
                        transactions={transactions}
                        onTopUp={handleTopUpWallet}
                        onOpenDetails={() => {
                          if (!isCitizenLoggedIn) {
                            setShowCitizenLoginModal(true);
                          } else {
                            setShowWalletDetails(true);
                          }
                        }}
                        onOpenLoginModal={() => setShowCitizenLoginModal(true)}
                        onOpenProfile={() => handleTabChange('profile')}
                        onOpenNotifications={() => handleTabChange('profile')}
                        onOpenHistory={() => setShowWalletDetails(true)}
                      />

                      {/* Action Required Banner (عودت برای اصلاح مدرک) */}
                      {actionRequiredCase && isCitizenLoggedIn && (
                        <div 
                          onClick={() => {
                            setFocusedCaseId(actionRequiredCase.id);
                            handleTabChange('cases');
                          }}
                          className="bg-amber-500 text-slate-950 p-2.5 rounded-xl shadow-md flex items-center justify-between gap-3 cursor-pointer hover:bg-amber-400 transition-all"
                        >
                          <div className="flex items-center gap-2">
                            <div className="w-8 h-8 rounded-lg bg-white text-amber-600 flex items-center justify-center font-black shrink-0">
                              <AlertTriangle className="w-4 h-4" />
                            </div>
                            <div>
                              <h3 className="font-extrabold text-xs">
                                پرونده «{actionRequiredCase.serviceTitle}» نیازمند اصلاح است!
                              </h3>
                            </div>
                          </div>

                          <span className="bg-slate-900 text-white text-[10px] font-bold px-2 py-1 rounded-lg shrink-0">
                            اصلاح فوری
                          </span>
                        </div>
                      )}

                      {/* Category Grid (دسته‌بندی‌های جذاب) */}
                      <CategoryFilter
                        selectedCategoryId={null}
                        onSelectCategory={(categoryId) => {
                          setSelectedCategoryId(categoryId);
                          handleTabChange('services');
                        }}
                        onOpenConsultation={() => setShowConsultationPage(true)}
                      />

                      {/* Dynamic Interactive CTA Slider (اسلایدر تعاملی پیشنهادات و خدمات) */}
                      <CtaSlider
                        onOpenVoiceAssistant={() => setShowVoiceAssistant(true)}
                        onOpenDelegations={() => setShowDelegationsModal(true)}
                        onOpenMap={() => handleTabChange('map')}
                        onOpenServices={() => handleTabChange('services')}
                        onOpenProfile={() => handleTabChange('profile')}
                        onOpenConsultation={() => setShowConsultationPage(true)}
                      />

                    </div>
                  )
                )}

                {/* VIEW 2: SERVICES FULL CATALOG (فهرست کامل خدمات) - قابل مشاهده برای همه */}
                {activeTab === 'services' && (
                  <ServicesCatalogView
                    services={CITIZEN_SERVICES}
                    initialCategoryId={selectedCategoryId}
                    searchQuery={searchQuery}
                    onSelectService={(s) => {
                      setPreSelectedOfficeId(null);
                      setActiveRequestService(s);
                    }}
                  />
                )}

                {/* VIEW 3: MAP OF OFFICES (نقشه دفاتر پیشخوان) - قابل مشاهده برای همه */}
                {activeTab === 'map' && (
                  <OfficesMap
                    offices={offices}
                    services={CITIZEN_SERVICES}
                    onDirectAssign={handleDirectAssignFromMap}
                    onBookAppointment={handleBookAppointmentFromMap}
                    onOpenChatbot={() => setShowSmartChatbot(true)}
                  />
                )}

                {/* VIEW 4: CASES & TRACKING (پیگیری پرونده‌ها) - نیازمند ورود کاربر */}
                {activeTab === 'cases' && (
                  !isCitizenLoggedIn ? (
                    <CitizenLoginView
                      targetTabName="پیگیری پرونده‌ها"
                      contextMessage="جهت رهگیری وضعیت درخواست‌ها و مدارک صادرشده، لطفاً وارد حساب کاربری خود شوید."
                      onLoginSuccess={(loggedProfile) => {
                        setProfile(loggedProfile);
                        setIsCitizenLoggedIn(true);
                      }}
                    />
                  ) : (
                    <CaseTrackingView
                      cases={cases}
                      onFixCaseDocument={handleFixCaseDocument}
                      selectedCaseId={focusedCaseId}
                      messages={messages}
                      onSendMessage={handleSendMessage}
                    />
                  )
                )}

                {/* VIEW 5: USER PROFILE, NOTICES & VAULT (حساب کاربری و مدارک) - نیازمند ورود کاربر */}
                {activeTab === 'profile' && (
                  !isCitizenLoggedIn ? (
                    <CitizenLoginView
                      targetTabName="حساب کاربری شهروندی"
                      contextMessage="جهت دسترسی به اطلاعات کاربری، مخزن اسناد هویتی و ابلاغیه‌ها، لطفاً وارد شوید."
                      onLoginSuccess={(loggedProfile) => {
                        setProfile(loggedProfile);
                        setIsCitizenLoggedIn(true);
                      }}
                    />
                  ) : (
                    <UserProfileView
                      profile={profile}
                      appointments={appointments}
                      onUpgradeTier={handleUpgradeTier}
                      onAddDocument={handleAddDocumentToVault}
                      onUpdateProfile={(updated) => setProfile(prev => ({ ...prev, ...updated }))}
                      onBackToHome={() => handleTabChange('home')}
                      onCancelAppointment={(appId) => setAppointments(prev => prev.filter(a => a.id !== appId))}
                      onSwitchToOfficeDesk={() => setAppMode('office')}
                      onLogout={() => {
                        setIsCitizenLoggedIn(false);
                        handleTabChange('home');
                      }}
                      messages={messages}
                      cases={cases}
                      onSendMessage={handleSendMessage}
                    />
                  )
                )}

              </motion.div>
            </AnimatePresence>
            )}

          </main>

          {/* Bottom Mobile Navbar - Always accessible and visible */}
          <Navbar
            activeTab={activeTab}
            onChangeTab={handleTabChange}
            cases={cases}
          />
        </div>
      )}

      {/* MODAL 0: Smart AI Chatbot Mascot Modal */}
      {showSmartChatbot && (
        <SmartChatbotModal
          services={CITIZEN_SERVICES}
          cases={cases}
          offices={offices}
          onClose={() => setShowSmartChatbot(false)}
          onSelectService={(s) => {
            setActiveRequestService(s);
          }}
          onNavigateToCases={() => {
            setActiveTab('cases');
          }}
          onNavigateToMap={() => {
            setActiveTab('map');
          }}
        />
      )}

      {/* MODAL 1: Service Request Flow (Snapp Dispatch / Auto-Fill) */}
      {activeRequestService && (
        <ServiceRequestModal
          service={activeRequestService}
          profile={profile}
          offices={offices}
          preSelectedOfficeId={preSelectedOfficeId}
          onClose={() => {
            setActiveRequestService(null);
            setPreSelectedOfficeId(null);
          }}
          onSubmitCase={(newCase) => {
            handleSubmitNewCase(newCase);
            setFocusedCaseId(newCase.id);
            if (!isCitizenLoggedIn) {
              setIsCitizenLoggedIn(true);
            }
            setActiveTab('cases');
          }}
        />
      )}

      {/* MODAL 2: Voice Assistant Easy-Mode */}
      {showVoiceAssistant && (
        <VoiceAssistantModal
          services={CITIZEN_SERVICES}
          onClose={() => setShowVoiceAssistant(false)}
          onSelectService={(s) => {
            setActiveRequestService(s);
          }}
          onGoToMap={() => {
            setActiveTab('map');
          }}
        />
      )}

      {/* MODAL 3: In-Person Appointment Booking */}
      {activeAppointmentOffice && (
        <AppointmentModal
          office={activeAppointmentOffice}
          onClose={() => setActiveAppointmentOffice(null)}
          onBook={handleAddAppointment}
        />
      )}

      {/* MODAL 4: Legal Delegation & Family Representation */}
      {showDelegationsModal && (
        <LegalDelegationModal
          delegations={delegations}
          onClose={() => setShowDelegationsModal(false)}
          onAddDelegation={(del) => setDelegations(prev => [del, ...prev])}
        />
      )}

      {/* MODAL 5: Citizen Login Modal (When triggered from Header or Wallet Card) */}
      {showCitizenLoginModal && (
        <CitizenLoginView
          isModal
          onCancel={() => setShowCitizenLoginModal(false)}
          onLoginSuccess={(loggedProfile) => {
            setProfile(loggedProfile);
            setIsCitizenLoggedIn(true);
            setShowCitizenLoginModal(false);
          }}
        />
      )}

    </div>
  );
}

