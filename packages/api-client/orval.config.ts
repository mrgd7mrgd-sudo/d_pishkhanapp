import { defineConfig } from 'orval';

export default defineConfig({
  pishkhan: {
    input: {
      target: '../../apps/api/openapi.json',
    },
    output: {
      mode: 'tags-split',
      target: 'src/generated/endpoints',
      schemas: 'src/generated/model',
      client: 'react-query',
      httpClient: 'axios',
      mock: false,
      clean: true,
      override: {
        mutator: {
          path: './src/custom-instance.ts',
          name: 'customInstance',
        },
      },
    },
  },
});
