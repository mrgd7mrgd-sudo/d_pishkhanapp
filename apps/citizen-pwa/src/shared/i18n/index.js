import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import commonFa from '@/locales/fa/common.json';
void i18n
    .use(initReactI18next)
    .init({
    lng: 'fa',
    fallbackLng: 'fa',
    resources: {
        fa: {
            common: commonFa,
        },
    },
    defaultNS: 'common',
    interpolation: {
        escapeValue: false,
    },
});
export default i18n;
