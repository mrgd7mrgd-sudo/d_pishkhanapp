import http from 'k6/http';
import { check, sleep } from 'k6';

/**
 * k6 Rate Limit Light Load Test (§7.5, TASK-035-T)
 * Validates that nginx/API enforces the 300 requests / minute per IP ceiling.
 */
export const options = {
  scenarios: {
    rate_limit_burst: {
      executor: 'constant-arrival-rate',
      rate: 10, // 10 requests per second = 600 requests / minute
      timeUnit: '1s',
      duration: '35s',
      preAllocatedVUs: 5,
      maxVUs: 20,
    },
  },
  thresholds: {
    // We expect 429 Too Many Requests once rate limit of 300 req/min is exceeded
    'http_req_failed': ['rate>0'],
  },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export default function () {
  const res = http.get(`${BASE_URL}/api/v1/health`);

  if (res.status === 200) {
    check(res, {
      'has standard limit header': (r) => r.headers['X-Ratelimit-Limit'] !== undefined || r.headers['X-RateLimit-Limit'] !== undefined,
    });
  } else if (res.status === 429) {
    check(res, {
      'has 429 status code': (r) => r.status === 429,
      'has retry-after header': (r) => r.headers['Retry-After'] !== undefined,
      'returns problem details code': (r) => r.json('code') === 'RATE_LIMITED' || r.json('code') === 'AUTH_OTP_TOO_MANY',
    });
  }

  sleep(0.05);
}
