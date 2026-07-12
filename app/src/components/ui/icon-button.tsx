import { Pressable, type PressableProps } from 'react-native';

import { cn } from '@/lib/utils';
import { shadows } from '@/theme/tokens';

export type IconButtonProps = PressableProps;

/** White rounded-square button that holds a single icon (e.g. notifications). */
export function IconButton({ className, children, ...props }: IconButtonProps) {
  return (
    <Pressable
      className={cn(
        'h-11 w-11 items-center justify-center rounded-[14px] bg-card active:opacity-80',
        className,
      )}
      style={shadows.pill}
      accessibilityRole="button"
      {...props}
    >
      {children}
    </Pressable>
  );
}
