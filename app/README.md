# Hey Brother Secreto app

Universal Expo application for iOS, Android, and web. It uses Expo Router, strict TypeScript, and Brazilian Portuguese as its default language.

## Development

```sh
cp .env.example .env.local
npm install
npm start
```

Use `npm run ios`, `npm run android`, or `npm run web` to open a target directly. Set `EXPO_PUBLIC_API_URL` to an address the target can reach. A physical device needs your computer's LAN address instead of `localhost`.

## API contract

The API SDK in `src/api/generated` is generated from `../backend/openapi/v1.json`. Do not edit generated files manually.

```sh
npm run api:generate
```

Call `configureApiClient(accessToken)` before a protected API request, then import endpoint functions such as `login`, `logout`, and `getCurrentUser` from `@/api/generated/sdk.gen`.

## Checks

```sh
npm run typecheck
npx expo-doctor
```
