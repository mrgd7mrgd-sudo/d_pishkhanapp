import { useEffect, useState } from 'react';

export type DevicePerformance = 'low' | 'high';

interface NavigatorWithMemory extends Navigator {
  deviceMemory?: number;
}

/**
 * Hook to assess client hardware capabilities (Architecture §4.7 & HC-2):
 * - If deviceMemory <= 2GB or hardwareConcurrency <= 2, classifies device as 'low'
 * - Sets data-perf="low" attribute on <html> element to disable expensive blur effects
 */
export function useDeviceCapability(): { performance: DevicePerformance; isLowPerf: boolean } {
  const [performance, setPerformance] = useState<DevicePerformance>('high');

  useEffect(() => {
    if (typeof window === 'undefined') return;

    const nav = navigator as NavigatorWithMemory;
    const memory = nav.deviceMemory ?? 4;
    const cores = nav.hardwareConcurrency ?? 4;

    // Detect low-performance devices: RAM <= 2GB or CPU <= 2 cores
    const isLow = memory <= 2 || cores <= 2;
    const perfLevel: DevicePerformance = isLow ? 'low' : 'high';

    setPerformance(perfLevel);

    const root = document.documentElement;
    if (isLow) {
      root.setAttribute('data-perf', 'low');
    } else {
      root.removeAttribute('data-perf');
    }
  }, []);

  return {
    performance,
    isLowPerf: performance === 'low',
  };
}
