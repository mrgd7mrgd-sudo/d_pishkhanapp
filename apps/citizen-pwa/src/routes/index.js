import { lazy } from 'react';
const Placeholder = (key) => lazy(async () => {
    const mod = await import('@/shared/ui/PlaceholderPage');
    return { default: () => mod.PlaceholderPage({ titleKey: key }) };
});
const Login = lazy(async () => {
    const mod = await import('@/features/auth');
    return { default: mod.AuthFlow };
});
/**
 * All 26 Citizen Routes defined in Architecture §4.4
 */
export const routes = [
    { path: '/', Component: Placeholder('home') },
    { path: '/services', Component: Placeholder('services') },
    { path: '/services/:categoryId', Component: Placeholder('category') },
    { path: '/services/:categoryId/:serviceId', Component: Placeholder('service_detail') },
    { path: '/request/:serviceId', Component: Placeholder('request') },
    { path: '/map', Component: Placeholder('map') },
    { path: '/map/offices/:officeId', Component: Placeholder('office_detail') },
    { path: '/cases', Component: Placeholder('cases') },
    { path: '/cases/:trackingCode', Component: Placeholder('case_detail') },
    { path: '/cases/:trackingCode/chat', Component: Placeholder('case_chat') },
    { path: '/consultation', Component: Placeholder('consultation') },
    { path: '/consultation/advisors/:advisorId', Component: Placeholder('advisor_detail') },
    { path: '/consultation/sessions/:sessionId', Component: Placeholder('session_live') },
    { path: '/profile', Component: Placeholder('profile') },
    { path: '/profile/personal-info', Component: Placeholder('personal_info') },
    { path: '/profile/documents', Component: Placeholder('documents') },
    { path: '/profile/appointments', Component: Placeholder('appointments') },
    { path: '/profile/reminders', Component: Placeholder('reminders') },
    { path: '/profile/messages', Component: Placeholder('messages') },
    { path: '/profile/delegations', Component: Placeholder('delegations') },
    { path: '/profile/settings', Component: Placeholder('settings') },
    { path: '/profile/support', Component: Placeholder('support') },
    { path: '/profile/about', Component: Placeholder('about') },
    { path: '/wallet', Component: Placeholder('wallet') },
    { path: '/wallet/transactions', Component: Placeholder('wallet_transactions') },
    { path: '/login', Component: Login },
    { path: '/offline', Component: Placeholder('offline') },
];
