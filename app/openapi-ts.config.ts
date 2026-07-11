import { defineConfig } from '@hey-api/openapi-ts';

export default defineConfig({
  input: '../backend/openapi/v1.json',
  output: 'src/api/generated',
});
