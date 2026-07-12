import type { ViewStyle } from 'react-native';

/**
 * Raw "Suave & Menta" design values for JS-only consumers that cannot use a
 * NativeWind `className` — icon/SVG colors, gradient stops, and native shadow
 * styles. The Tailwind theme in `tailwind.config.js` is the styling source of
 * truth; keep these values in sync with it.
 */
export const palette = {
  bg: '#F1F7F3',
  mint: '#3FA88A',
  mintDeep: '#2F8E73',
  mintTint: '#EAF6F0',
  pink: '#E39BA0',
  pinkDeep: '#EBA9AD',
  pinkTint: '#FBEDEE',
  ink: '#26332C',
  inkMuted: '#93A199',
  inkSoft: '#63756B',
  card: '#FFFFFF',
  hairline: '#E4EFE8',
  outline: '#B6D8C8',
  white: '#FFFFFF',
} as const;

/** Linear-gradient color stops (start -> end). */
export const gradients = {
  brand: [palette.mint, palette.pink] as const,
};

/**
 * Native elevation presets. iOS reads `shadow*`, Android reads `elevation`;
 * each platform ignores the props it does not use.
 */
export const shadows = {
  /** Resting card / group tiles. */
  card: {
    shadowColor: palette.ink,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.06,
    shadowRadius: 14,
    elevation: 3,
  },
  /** The hero "draw result" card. */
  hero: {
    shadowColor: palette.mint,
    shadowOffset: { width: 0, height: 14 },
    shadowOpacity: 0.18,
    shadowRadius: 24,
    elevation: 8,
  },
  /** Small floating pills (countdown, icon button). */
  pill: {
    shadowColor: palette.mint,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.12,
    shadowRadius: 12,
    elevation: 4,
  },
  /** Floating bottom tab bar. */
  floating: {
    shadowColor: palette.ink,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.12,
    shadowRadius: 20,
    elevation: 12,
  },
} satisfies Record<string, ViewStyle>;
