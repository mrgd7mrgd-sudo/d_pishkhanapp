import { lazy } from 'react';
import type { RouteObject } from 'react-router-dom';

const Placeholder = (key: string) =>
  lazy(async () => {
    const mod = await import('@/shared/ui/PlaceholderPage');
    return { default: () => mod.PlaceholderPage({ titleKey: key }) };
  });

const Login = lazy(async () => {
  const mod = await import('@/features/auth');
  return { default: mod.AuthFlow };
});

const ServiceCatalog = lazy(async () => {
  const mod = await import('@/features/service-catalog');
  return { default: mod.ServiceCatalogView };
});

const OfficesMap = lazy(async () => {
  const mod = await import('@/features/offices-map');
  return { default: mod.OfficesMapView };
});

const ServiceRequest = lazy(async () => {
  const mod = await import('@/features/service-request');
  return { default: mod.ServiceRequestFlow };
});

const CaseList = lazy(async () => {
  const mod = await import('@/features/case-tracking');
  return { default: mod.CaseListPage };
});

const CaseDetail = lazy(async () => {
  const mod = await import('@/features/case-tracking');
  return { default: mod.CaseDetailPage };
});

const DocumentsVault = lazy(async () => {
  const mod = await import('@/features/documents-vault');
  return { default: mod.DocumentsVaultView };
});

const CaseChat = lazy(async () => {
  const mod = await import('@/features/messaging');
  return { default: mod.CaseChatView };
});

const Notifications = lazy(async () => {
  const mod = await import('@/features/messaging');
  return { default: mod.NotificationsView };
});

const WalletDashboard = lazy(async () => {
  const mod = await import('@/features/wallet');
  return { default: mod.WalletDashboardView };
});

const WalletTransactions = lazy(async () => {
  const mod = await import('@/features/wallet');
  return { default: mod.WalletTransactionsView };
});

/**
 * All 26 Citizen Routes defined in Architecture §4.4
 */
export const routes: RouteObject[] = [
  { path: '/', Component: Placeholder('home') },
  { path: '/services', Component: ServiceCatalog },
  { path: '/services/:categoryId', Component: ServiceCatalog },
  { path: '/services/:categoryId/:serviceId', Component: ServiceCatalog },

  { path: '/request/:serviceId', Component: ServiceRequest },
  { path: '/map', Component: OfficesMap },
  { path: '/map/offices/:officeId', Component: OfficesMap },
  { path: '/cases', Component: CaseList },
  { path: '/cases/:trackingCode', Component: CaseDetail },
  { path: '/cases/:trackingCode/chat', Component: CaseChat },
  { path: '/consultation', Component: Placeholder('consultation') },
  { path: '/consultation/advisors/:advisorId', Component: Placeholder('advisor_detail') },
  { path: '/consultation/sessions/:sessionId', Component: Placeholder('session_live') },
  { path: '/profile', Component: Placeholder('profile') },
  { path: '/profile/personal-info', Component: Placeholder('personal_info') },
  { path: '/profile/documents', Component: DocumentsVault },
  { path: '/profile/appointments', Component: Placeholder('appointments') },
  { path: '/profile/reminders', Component: Placeholder('reminders') },
  { path: '/profile/messages', Component: Notifications },
  { path: '/profile/delegations', Component: Placeholder('delegations') },
  { path: '/profile/settings', Component: Placeholder('settings') },
  { path: '/profile/support', Component: Placeholder('support') },
  { path: '/profile/about', Component: Placeholder('about') },
  { path: '/wallet', Component: WalletDashboard },
  { path: '/wallet/transactions', Component: WalletTransactions },
  { path: '/login', Component: Login },
  { path: '/offline', Component: Placeholder('offline') },
];
