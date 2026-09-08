import config from './packages/config-eslint/index.js';

export default [
  ...config,
  {
    ignores: [
      '**/node_modules/**',
      '**/dist/**',
      '**/build/**',
      '**/storage/**',
      '**/vendor/**',
      '**/prototype/**',
      '**/.turbo/**',
      'graphify-out/**',
    ],
  },
];
