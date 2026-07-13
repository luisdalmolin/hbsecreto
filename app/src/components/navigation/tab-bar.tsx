import type { TabTriggerSlotProps } from "expo-router/ui";
import type { LucideIcon } from "lucide-react-native";
import { forwardRef } from "react";
import { Pressable, View } from "react-native";

import { Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

const INACTIVE = "#AEBBB2";

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
  {
    icon: Icon,
    label,
    isFocused,
    fillWhenActive = false,
    href: _href,
    ...pressableProps
  },
  ref,
) {
  const color = isFocused ? palette.mint : INACTIVE;

  return (
    <Pressable
      ref={ref}
      className="min-h-11 items-center justify-center gap-[3px] px-2 py-0.5 active:opacity-70"
      accessibilityRole="button"
      accessibilityState={{ selected: !!isFocused }}
      {...pressableProps}
    >
      <Icon
        color={color}
        size={24}
        strokeWidth={2}
        fill={isFocused && fillWhenActive ? palette.mint : "transparent"}
      />
      <Text
        className="font-body-x text-[11px] leading-[14px]"
        style={{ color }}
      >
        {label}
      </Text>
    </Pressable>
  );
});
