import { lazy } from 'react';
import type { RouteObject } from 'react-router-dom';

const DeskPlaceholder = (key: string) =>
  lazy(async () => {
    const mod = await import('@/shared/ui/DeskPlaceholderPage');
    return { default: () => mod.DeskPlaceholderPage({ titleKey: key }) };
  });

const OperatorAuthFlowPage = lazy(async () => {
  const mod = await import('@/features/auth');
  return { default: mod.OperatorAuthFlow };
});

const OffersPanelPage = lazy(async () => {
  const mod = await import('@/features/offers');
  return { default: mod.OffersPanel };
});

const WorkspacePage = lazy(async () => {
  const mod = await import('@/features/workspace');
  return { default: mod.WorkspacePage };
});

const QueuePage = lazy(async () => {
  const mod = await import('@/features/queue');
  return { default: mod.QueuePage };
});

const FinancePage = lazy(async () => {
  const mod = await import('@/features/finance');
  return { default: mod.FinanceView };
});

/**
 * All 10 Operator Desk Routes defined in Architecture §4.4
 */
export const deskRoutes: RouteObject[] = [
  { path: '/login', Component: OperatorAuthFlowPage },
  { path: '/offers', Component: OffersPanelPage },
  { path: '/workspace', Component: WorkspacePage },
  { path: '/workspace/:caseId', Component: WorkspacePage },
  { path: '/queue', Component: QueuePage },
  { path: '/delivery', Component: DeskPlaceholder('delivery') },
  { path: '/delivery/:deliveryId/waybill', Component: DeskPlaceholder('waybill') },
  { path: '/finance', Component: FinancePage },
  { path: '/reviews', Component: DeskPlaceholder('reviews') },
  { path: '/office-profile', Component: DeskPlaceholder('office_profile') },
  { path: '/', Component: WorkspacePage }, // default workspace redirect
];
