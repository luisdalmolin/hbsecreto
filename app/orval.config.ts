import { defineConfig } from 'orval';

export default defineConfig({
  v1: {
    input: '../backend/openapi/v1.json',
    output: {
      client: 'fetch',
      mode: 'tags-split',
      target: 'src/api/generated/endpoints.ts',
      schemas: 'src/api/generated/models',
      override: {
        fetch: {
          includeHttpResponseReturnType: false,
        },
        mutator: {
          path: './src/api/http.ts',
          name: 'apiFetch',
        },
      },
    },
  },
});
