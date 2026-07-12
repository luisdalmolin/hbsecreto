import { cva, type VariantProps } from 'class-variance-authority';
import { Text as RNText, type TextProps } from 'react-native';

import { cn } from '@/lib/utils';

/**
 * Typographic scale for "Suave & Menta". Each variant pins a font family
 * (weight) and size; color defaults to ink but can be overridden via
 * `className` (Tailwind conflicts resolve last-wins through `cn`).
 */
const textVariants = cva('text-ink', {
  variants: {
    variant: {
      hero: 'font-display-x text-[30px] leading-[34px]',
      title: 'font-display text-[19px] leading-[22px]',
      section: 'font-display text-[18px] leading-[22px]',
      cardTitle: 'font-body-x text-[15px] leading-[20px]',
      body: 'font-body text-[15px] leading-[21px]',
      bodyBold: 'font-body-bold text-[14px] leading-[20px]',
      label: 'font-body-x text-[11px] leading-[16px] tracking-[0.5px]',
      eyebrow: 'font-body-black text-[12px] leading-[16px] tracking-[1.5px]',
      caption: 'font-body text-[13px] leading-[18px] text-ink-muted',
    },
  },
  defaultVariants: { variant: 'body' },
});

export type TextVariant = NonNullable<VariantProps<typeof textVariants>['variant']>;

export interface TextComponentProps extends TextProps, VariantProps<typeof textVariants> {}

export function Text({ className, variant, ...props }: TextComponentProps) {
  return <RNText className={cn(textVariants({ variant }), className)} {...props} />;
}
