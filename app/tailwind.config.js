/**
 * "Suave & Menta" design tokens.
 *
 * This Tailwind theme is the styling source of truth. The raw values are
 * mirrored in `src/theme/tokens.ts` for JS-only consumers (SVG/icon colors,
 * gradients, native shadow styles). Keep the two in sync.
 *
 * @type {import('tailwindcss').Config}
 */
module.exports = {
  content: ['./src/**/*.{js,jsx,ts,tsx}'],
  presets: [require('nativewind/preset')],
  theme: {
    extend: {
      colors: {
        bg: '#F1F7F3',
        mint: { DEFAULT: '#3FA88A', deep: '#2F8E73', tint: '#EAF6F0' },
        pink: { DEFAULT: '#E39BA0', deep: '#EBA9AD', tint: '#FBEDEE' },
        ink: { DEFAULT: '#26332C', muted: '#93A199', soft: '#63756B' },
        card: '#FFFFFF',
        hairline: '#E4EFE8',
        outline: '#B6D8C8',
      },
      fontFamily: {
        // Display — Baloo 2
        display: ['Baloo2_700Bold'],
        'display-semi': ['Baloo2_600SemiBold'],
        'display-x': ['Baloo2_800ExtraBold'],
        // Body — Nunito
        'body-reg': ['Nunito_400Regular'],
        body: ['Nunito_600SemiBold'],
        'body-bold': ['Nunito_700Bold'],
        'body-x': ['Nunito_800ExtraBold'],
        'body-black': ['Nunito_900Black'],
      },
      borderRadius: {
        tile: '15px',
        card: '22px',
        hero: '28px',
      },
    },
  },
  plugins: [],
};
