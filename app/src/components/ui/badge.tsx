import { cva, type VariantProps } from 'class-variance-authority';
import { View, type ViewProps } from 'react-native';

import { cn } from '@/lib/utils';

import { Text } from './text';

const badgeVariants = cva('self-start rounded-full px-[11px] py-1.5', {
  variants: {
    variant: {
      success: 'bg-mint-tint',
      neutral: 'bg-mint-tint',
    },
  },
  defaultVariants: { variant: 'success' },
});

const badgeTextVariants = cva('font-body-x text-[12px] leading-[16px]', {
  variants: {
    variant: {
      success: 'text-mint-deep',
      neutral: 'text-ink-soft',
    },
  },
  defaultVariants: { variant: 'success' },
});

export interface BadgeProps extends ViewProps, VariantProps<typeof badgeVariants> {
  label: string;
}

/** Small status pill, e.g. "Sorteado ✓". */
export function Badge({ className, variant, label, ...props }: BadgeProps) {
  return (
    <View className={cn(badgeVariants({ variant }), className)} {...props}>
      <Text className={badgeTextVariants({ variant })}>{label}</Text>
    </View>
  );
}
