import { registerSW } from 'virtual:pwa-register';

export interface SWRegistrationCallbacks {
  onNeedRefresh?: () => void;
  onOfflineReady?: () => void;
}

export function setupServiceWorker(callbacks: SWRegistrationCallbacks = {}): (reloadPage?: boolean) => Promise<void> {
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

  return async () => {};
}