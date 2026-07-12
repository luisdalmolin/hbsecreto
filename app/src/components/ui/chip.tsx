import { View, type ViewProps } from 'react-native';

import { cn } from '@/lib/utils';

import { Text } from './text';

export interface ChipProps extends ViewProps {
  label: string;
}

/** Rounded tag, e.g. a wishlist item. */
export function Chip({ className, label, ...props }: ChipProps) {
  return (
    <View className={cn('self-start rounded-full bg-mint-tint px-3 py-1.5', className)} {...props}>
      <Text className="font-body-bold text-[13px] leading-[18px] text-mint-deep">{label}</Text>
    </View>
  );
}
