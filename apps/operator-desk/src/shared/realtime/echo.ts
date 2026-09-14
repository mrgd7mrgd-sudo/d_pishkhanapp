/**
 * Real-time Echo Client & Audio/Web-Notification Service (§4.4, §5.7, TASK-077).
 */

export interface RealtimeListener<T = unknown> {
  (payload: T): void;
}

export interface RealtimeChannel {
  listen<T = unknown>(event: string, callback: RealtimeListener<T>): RealtimeChannel;
  stopListening(event: string): RealtimeChannel;
}

export interface RealtimeClient {
  private(channelName: string): RealtimeChannel;
  leave(channelName: string): void;
  disconnect(): void;
}

class MockRealtimeChannel implements RealtimeChannel {
  private listeners = new Map<string, Set<RealtimeListener>>();

  constructor(public readonly name: string) {}

  listen<T = unknown>(event: string, callback: RealtimeListener<T>): RealtimeChannel {
    const cleanEvent = event.startsWith('.') ? event.slice(1) : event;
    if (!this.listeners.has(cleanEvent)) {
      this.listeners.set(cleanEvent, new Set());
    }
    this.listeners.get(cleanEvent)?.add(callback as RealtimeListener);
    return this;
  }

  stopListening(event: string): RealtimeChannel {
    const cleanEvent = event.startsWith('.') ? event.slice(1) : event;
    this.listeners.delete(cleanEvent);
    return this;
  }

  emit(event: string, payload: unknown): void {
    const cleanEvent = event.startsWith('.') ? event.slice(1) : event;
    const callbacks = this.listeners.get(cleanEvent);
    if (callbacks) {
      callbacks.forEach((cb) => cb(payload));
    }
  }
}

class EchoRealtimeManager implements RealtimeClient {
  private channels = new Map<string, MockRealtimeChannel>();

  private(channelName: string): RealtimeChannel {
    if (!this.channels.has(channelName)) {
      this.channels.set(channelName, new MockRealtimeChannel(channelName));
    }
    return this.channels.get(channelName)!;
  }

  leave(channelName: string): void {
    this.channels.delete(channelName);
  }

  disconnect(): void {
    this.channels.clear();
  }

  getChannel(channelName: string): MockRealtimeChannel | undefined {
    return this.channels.get(channelName);
  }
}

export const realtimeManager = new EchoRealtimeManager();

/**
 * Generates an audio chime using Web Audio API synthesizer.
 */
export function playNotificationSound(): void {
  try {
    const AudioContextClass = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
    if (!AudioContextClass) return;

    const ctx = new AudioContextClass();
    const now = ctx.currentTime;

    const osc1 = ctx.createOscillator();
    const osc2 = ctx.createOscillator();
    const gain = ctx.createGain();

    osc1.type = 'sine';
    osc1.frequency.setValueAtTime(587.33, now); // D5
    osc1.frequency.exponentialRampToValueAtTime(880, now + 0.15); // A5

    osc2.type = 'triangle';
    osc2.frequency.setValueAtTime(880, now + 0.15);
    osc2.frequency.exponentialRampToValueAtTime(1174.66, now + 0.35); // D6

    gain.gain.setValueAtTime(0.15, now);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);

    osc1.connect(gain);
    osc2.connect(gain);
    gain.connect(ctx.destination);

    osc1.start(now);
    osc1.stop(now + 0.15);
    osc2.start(now + 0.15);
    osc2.stop(now + 0.5);
  } catch {
    // Audio playback not allowed or supported
  }
}

/**
 * Triggers native desktop Web Notification if permission granted.
 */
export function showDesktopNotification(title: string, body: string): void {
  if (typeof window === 'undefined' || !('Notification' in window)) {
    return;
  }

  if (Notification.permission === 'granted') {
    new Notification(title, {
      body,
      icon: '/pwa-192x192.png',
      tag: 'pishkhan-offer',
    });
  } else if (Notification.permission !== 'denied') {
    void Notification.requestPermission().then((permission) => {
      if (permission === 'granted') {
        new Notification(title, {
          body,
          icon: '/pwa-192x192.png',
          tag: 'pishkhan-offer',
        });
      }
    });
  }
}
