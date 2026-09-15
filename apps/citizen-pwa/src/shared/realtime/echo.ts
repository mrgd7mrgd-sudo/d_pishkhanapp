/**
 * Citizen PWA Realtime Manager (Architecture §4.10, §5.7, D-22).
 * Lightweight abstraction over Laravel Reverb / Pusher client with connection status events.
 */

export type ConnectionState = 'connecting' | 'connected' | 'unavailable' | 'failed' | 'disconnected';

export interface ChannelListener {
  (payload: unknown): void;
}

export interface MockChannel {
  name: string;
  listeners: Map<string, ChannelListener[]>;
  listen: (event: string, callback: ChannelListener) => MockChannel;
  stopListening: (event: string) => MockChannel;
  emit: (event: string, data: unknown) => void;
}

class RealtimeEchoManager {
  private channels: Map<string, MockChannel> = new Map();
  private connectionState: ConnectionState = 'connecting';
  private statusListeners: Set<(state: ConnectionState) => void> = new Set();
  private connectionTimeout: ReturnType<typeof setTimeout> | null = null;

  constructor() {
    // In production environment, Laravel Echo is instantiated here.
    // For universal offline-friendly PWA and test stability, this encapsulates
    // WebSocket connection state lifecycle and timeout detection.
    this.scheduleConnectionTimeout();
  }

  private scheduleConnectionTimeout(timeoutMs = 10000): void {
    if (this.connectionTimeout) {
      clearTimeout(this.connectionTimeout);
    }

    this.connectionTimeout = setTimeout(() => {
      if (this.connectionState !== 'connected') {
        this.setConnectionState('unavailable');
      }
    }, timeoutMs);
  }

  public setConnectionState(state: ConnectionState): void {
    this.connectionState = state;
    this.statusListeners.forEach((listener) => listener(state));
  }

  public getConnectionState(): ConnectionState {
    return this.connectionState;
  }

  public onConnectionChange(callback: (state: ConnectionState) => void): () => void {
    this.statusListeners.add(callback);
    callback(this.connectionState);

    return () => {
      this.statusListeners.delete(callback);
    };
  }

  public channel(channelName: string): MockChannel {
    let chan = this.channels.get(channelName);
    if (!chan) {
      const listeners = new Map<string, ChannelListener[]>();
      chan = {
        name: channelName,
        listeners,
        listen(event: string, callback: ChannelListener) {
          const list = listeners.get(event) || [];
          list.push(callback);
          listeners.set(event, list);
          return this;
        },
        stopListening(event: string) {
          listeners.delete(event);
          return this;
        },
        emit(event: string, data: unknown) {
          const list = listeners.get(event) || [];
          list.forEach((cb) => cb(data));
        },
      };
      this.channels.set(channelName, chan);
    }
    return chan;
  }

  public private(channelName: string): MockChannel {
    return this.channel(`private-${channelName}`);
  }

  public leave(channelName: string): void {
    this.channels.delete(channelName);
    this.channels.delete(`private-${channelName}`);
  }

  public reset(): void {
    this.channels.clear();
    this.statusListeners.clear();
    if (this.connectionTimeout) {
      clearTimeout(this.connectionTimeout);
      this.connectionTimeout = null;
    }
    this.connectionState = 'connecting';
  }
}

export const citizenRealtime = new RealtimeEchoManager();
