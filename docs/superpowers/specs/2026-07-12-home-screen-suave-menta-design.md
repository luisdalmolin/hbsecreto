# Home Screen — "Suave & Menta" Design Implementation

**Date:** 2026-07-12
**Scope:** Implement the `1c — Suave & Menta` home-screen design in the Expo app. Design/UI only — no API wiring. Establish a reusable component + styling foundation that scales to future screens.

## Decisions (confirmed with user)

1. **Styling foundation:** NativeWind (Tailwind for RN) + React Native Reusables pattern ("Shadcn for React Native"). We own the primitives in `src/components/ui/`, authored in the RNR/shadcn style (`cn()` util, `cva` variants, NativeWind `className`). Icons via `lucide-react-native`.
2. **Navigation:** Full `expo-router` `(tabs)` group with a custom floating pill tab bar wired to a real Home screen plus placeholder Groups/Profile screens.

## Design tokens ("Suave & Menta")

- **Palette:** bg `#F1F7F3`; primary/mint `#3FA88A`, mint-deep `#2F8E73`, mint-tint `#EAF6F0`; pink `#E39BA0`, pink-deep `#EBA9AD`, pink-tint `#FBEDEE`; text `#26332C`, muted `#93A199`, muted-soft `#63756B`; card `#FFFFFF`, border `#E4EFE8`, dashed `#B6D8C8`.
- **Fonts:** Baloo 2 (display / headings, weights 500–800), Nunito (body, 400–900). Loaded via `@expo-google-fonts/*`.
- **Radii:** cards 22–28, pills 999, icon tiles 12–16.
- **Shadows:** soft mint-tinted elevation on cards and floating tab bar.

## Structure

```
src/
  lib/utils.ts               # cn() = clsx + tailwind-merge
  theme/tokens.ts            # raw palette/radii values (single source; feeds tailwind + JS consumers)
  components/
    ui/                      # owned primitives: text, card, badge, button, chip, avatar, icon-button
    home/                    # home-header, countdown-pill, section-header,
                             #   draw-result-card (blur reveal), group-card, create-group-card
    navigation/tab-bar.tsx   # custom floating pill tab bar for expo-router Tabs
  app/(tabs)/
    _layout.tsx              # Tabs navigator using custom tab bar
    index.tsx                # Home (full design), composes home/* + ui/*
    groups.tsx               # placeholder
    profile.tsx              # placeholder
  data/home.ts               # static mock data (user, draw, groups)
```

## Home screen sections (top → bottom)

1. **Header** — gradient avatar w/ initials, greeting ("Boa noite,") + name, bell `IconButton`.
2. **CountdownPill** — white pill, "🎄 Faltam 12 dias para o Natal".
3. **DrawResultCard** — white card, gradient top bar, "VOCÊ TIROU" + gift icon; drawn name + wishlist chips are blurred behind a "Toque para revelar" overlay (tap to reveal, animated). Budget + "Ver desejos →" button.
4. **SectionHeader** — "Meus grupos" + "Ver todos".
5. **GroupCard ×N** — icon tile, name, participant count, status `Badge` (`Sorteado ✓` / `Sortear`).
6. **CreateGroupCard** — dashed pill CTA "Criar novo grupo".
7. **Custom floating TabBar** — Início / Grupos / Perfil.

## Component boundaries

- `ui/*` primitives: presentational, variant-driven, no domain knowledge — reusable across all screens.
- `home/*` components: compose primitives, accept typed props, hold no data-fetching — data passed in from the screen (mock for now, API later).
- Reveal interaction is local UI state inside `DrawResultCard`.

## Trade-offs vs. the HTML mockup

- Custom inline gift/bell SVGs → `lucide-react-native` equivalents (Gift, Bell, Users, User, House, Eye, Plus, ChevronRight). Cleaner, consistent, still on-brand.
- CSS `filter: blur` on text → `expo-blur` `BlurView` overlay fading out on reveal (native equivalent).
- CSS gradients → `expo-linear-gradient`.

## Out of scope

API calls, real auth/user data, draw logic, group CRUD, dark mode (design is light-only for now).
