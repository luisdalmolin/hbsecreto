import type { TabTriggerSlotProps } from 'expo-router/ui';
import type { LucideIcon } from 'lucide-react-native';
import { forwardRef } from 'react';
import { Pressable, View } from 'react-native';

import { Text } from '@/components/ui';
import { palette, shadows } from '@/theme/tokens';

const INACTIVE = '#AEBBB2';

/**
 * Style for the floating pill that wraps the tab triggers. Applied to the
 * headless `<TabList>` directly in the tabs layout — `TabList` must remain a
 * literal child of `<Tabs>` for its triggers to register as screens, so this
 * is exported as a style rather than a wrapper component.
 */
export const tabBarPillStyle = [
  shadows.floating,
  {
    flexDirection: 'row' as const,
    alignItems: 'center' as const,
    justifyContent: 'space-around' as const,
    backgroundColor: palette.card,
    borderRadius: 22,
    paddingHorizontal: 8,
    paddingTop: 12,
    paddingBottom: 14,
  },
];

export interface TabButtonProps extends TabTriggerSlotProps {
  icon: LucideIcon;
  label: string;
  /** Fill the icon when the tab is active (used for the home glyph). */
  fillWhenActive?: boolean;
}

/**
 * A single tab item. Rendered as the `asChild` slot of a `<TabTrigger>`, which
 * injects `isFocused`, `onPress`, and the rest of the pressable props.
 */
export const TabButton = forwardRef<View, TabButtonProps>(function TabButton(
  { icon: Icon, label, isFocused, fillWhenActive = false, href: _href, ...pressableProps },
  ref,
) {
  const color = isFocused ? palette.mint : INACTIVE;

  return (
    <Pressable
      ref={ref}
      className="items-center gap-[3px] px-2 py-0.5 active:opacity-70"
      accessibilityRole="button"
      accessibilityState={{ selected: !!isFocused }}
      {...pressableProps}
    >
      <Icon
        color={color}
        size={24}
        strokeWidth={2}
        fill={isFocused && fillWhenActive ? palette.mint : 'transparent'}
      />
      <Text className="font-body-x text-[11px] leading-[14px]" style={{ color }}>
        {label}
      </Text>
    </Pressable>
  );
});
