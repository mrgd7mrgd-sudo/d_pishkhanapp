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

/**
 * All 10 Operator Desk Routes defined in Architecture §4.4
 */
export const deskRoutes: RouteObject[] = [
  { path: '/login', Component: OperatorAuthFlowPage },
  { path: '/offers', Component: DeskPlaceholder('offers') },
  { path: '/workspace', Component: DeskPlaceholder('workspace') },
  { path: '/workspace/:caseId', Component: DeskPlaceholder('workspace_detail') },
  { path: '/queue', Component: DeskPlaceholder('queue') },
  { path: '/delivery', Component: DeskPlaceholder('delivery') },
  { path: '/delivery/:deliveryId/waybill', Component: DeskPlaceholder('waybill') },
  { path: '/finance', Component: DeskPlaceholder('finance') },
  { path: '/reviews', Component: DeskPlaceholder('reviews') },
  { path: '/office-profile', Component: DeskPlaceholder('office_profile') },
  { path: '/', Component: DeskPlaceholder('workspace') }, // default workspace redirect
];
