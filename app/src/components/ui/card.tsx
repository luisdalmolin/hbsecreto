import { View, type ViewProps } from 'react-native';

import { cn } from '@/lib/utils';
import { shadows } from '@/theme/tokens';

export interface CardProps extends ViewProps {
  /** Native elevation preset. `none` disables the shadow. */
  shadow?: keyof typeof shadows | 'none';
}

/** White rounded surface. The base for group tiles, the hero card, etc. */
export function Card({ className, style, shadow = 'card', ...props }: CardProps) {
  return (
    <View
      className={cn('rounded-card bg-card', className)}
      style={[shadow !== 'none' && shadows[shadow], style]}
      {...props}
    />
  );
}
