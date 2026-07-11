# Expo HAS CHANGED

Read the exact versioned docs at https://docs.expo.dev/versions/v57.0.0/ before writing any code.

## OpenAPI client

- `../backend/openapi/v1.json` is the API contract source of truth.
- `npm install` generates the client automatically. Run `npm run api:generate` after contract changes. The generated output in `src/api/generated/` is ignored by Git and must never be edited manually.
- Keep application-specific HTTP behavior, including the API URL, authentication, and error handling, in `src/api/http.ts`.
- Before handing off API client changes, run `npm run api:generate` and `npm run typecheck`.
