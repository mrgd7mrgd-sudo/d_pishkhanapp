export const qk = {
  services: {
    all: ['services'] as const,
    list: (f?: Record<string, unknown>) => ['services', 'list', f ?? {}] as const,
    detail: (id: string) => ['services', 'detail', id] as const,
  },
  categories: {
    all: ['categories'] as const,
  },
  offices: {
    all: ['offices'] as const,
    nearby: (p?: Record<string, unknown>) => ['offices', 'nearby', p ?? {}] as const,
    detail: (id: string) => ['offices', 'detail', id] as const,
  },
  cases: {
    all: ['cases'] as const,
    list: (f?: Record<string, unknown>) => ['cases', 'list', f ?? {}] as const,
    detail: (trackingCode: string) => ['cases', 'detail', trackingCode] as const,
    timeline: (trackingCode: string) => ['cases', 'timeline', trackingCode] as const,
    messages: (trackingCode: string) => ['cases', 'messages', trackingCode] as const,
  },
  documents: {
    all: ['documents'] as const,
  },
  wallet: {
    balance: ['wallet', 'balance'] as const,
    transactions: ['wallet', 'transactions'] as const,
  },
  consultation: {
    advisors: ['consultation', 'advisors'] as const,
    sessions: ['consultation', 'sessions'] as const,
    detail: (id: string) => ['consultation', 'advisor', id] as const,
  },
  profile: {
    me: ['profile', 'me'] as const,
    appointments: ['profile', 'appointments'] as const,
    reminders: ['profile', 'reminders'] as const,
    messages: ['profile', 'messages'] as const,
    delegations: ['profile', 'delegations'] as const,
  },
};
