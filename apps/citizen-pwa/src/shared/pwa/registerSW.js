import { registerSW } from 'virtual:pwa-register';
export function setupServiceWorker(callbacks = {}) {
    if ('serviceWorker' in navigator) {
        const updateSW = registerSW({
            onNeedRefresh() {
                callbacks.onNeedRefresh?.();
            },
            onOfflineReady() {
                callbacks.onOfflineReady?.();
            },
        });
        return updateSW;
    }
    return async () => { };
}
