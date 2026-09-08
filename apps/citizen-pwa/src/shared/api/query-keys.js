export const qk = {
    services: {
        all: ['services'],
        list: (f) => ['services', 'list', f ?? {}],
        detail: (id) => ['services', 'detail', id],
    },
    categories: {
        all: ['categories'],
    },
    offices: {
        all: ['offices'],
        nearby: (p) => ['offices', 'nearby', p ?? {}],
        detail: (id) => ['offices', 'detail', id],
    },
    cases: {
        all: ['cases'],
        list: (f) => ['cases', 'list', f ?? {}],
        detail: (trackingCode) => ['cases', 'detail', trackingCode],
        timeline: (trackingCode) => ['cases', 'timeline', trackingCode],
        messages: (trackingCode) => ['cases', 'messages', trackingCode],
    },
    documents: {
        all: ['documents'],
    },
    wallet: {
        balance: ['wallet', 'balance'],
        transactions: ['wallet', 'transactions'],
    },
    consultation: {
        advisors: ['consultation', 'advisors'],
        sessions: ['consultation', 'sessions'],
        detail: (id) => ['consultation', 'advisor', id],
    },
    profile: {
        me: ['profile', 'me'],
        appointments: ['profile', 'appointments'],
        reminders: ['profile', 'reminders'],
        messages: ['profile', 'messages'],
        delegations: ['profile', 'delegations'],
    },
};
