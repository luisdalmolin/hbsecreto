# Expo HAS CHANGED

Read the exact versioned docs at https://docs.expo.dev/versions/v57.0.0/ before writing any code.

## OpenAPI client

- `../backend/openapi/v1.json` is the API contract source of truth.
- `npm install` generates the client automatically. Run `npm run api:generate` after contract changes. The generated output in `src/api/generated/` is ignored by Git and must never be edited manually.
- Keep application-specific HTTP behavior, including the API URL, authentication, and error handling, in `src/api/http.ts`.
- Before handing off API client changes, run `npm run api:generate` and `npm run typecheck`.

## UI & design system

Styling uses **NativeWind** (Tailwind for RN) in the "Shadcn for React Native" style — primitives are owned in-repo, not pulled from a black-box library.

- **Design tokens** live in `tailwind.config.js` (the styling source of truth: the "Suave & Menta" palette, `font-*` families, radii). Raw values are mirrored in `src/theme/tokens.ts` for JS-only consumers (icon/gradient colors, native shadow styles) — keep the two in sync.
- **Fonts:** Baloo 2 (`font-display*`) and Nunito (`font-body*`), loaded in `src/app/_layout.tsx`. RN maps one file per weight, so weights are distinct `font-*` utilities, not `fontWeight`.
- **Primitives** (`src/components/ui/`): presentational, variant-driven (`cva` + the `cn` helper in `src/lib/utils.ts`), no domain knowledge. Reuse across screens. Barrel-exported from `@/components/ui`.
- **Feature components** (`src/components/<feature>/`, e.g. `home/`): compose primitives, take typed props, hold no data fetching — pass data in from the screen (mock data currently lives in `src/data/`).
- Icons: `lucide-react-native`. Gradients: `expo-linear-gradient`. Blur: `expo-blur`.
- **Navigation** uses expo-router's headless tabs (`expo-router/ui`). `<TabList>`/`<TabTrigger>` must stay literal children of `<Tabs>` in `src/app/(tabs)/_layout.tsx` or their routes won't register as screens.
- After UI changes run `npm run typecheck`; to see it rendered, `npm run web` and open the localhost URL.
