import { Bell } from "lucide-react-native";
import { View } from "react-native";

import { Avatar, IconButton, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

export interface HomeHeaderProps {
  greeting: string;
  name: string;
  initials: string;
  notificationsLabel: string;
  onPressNotifications?: () => void;
}

/** Top bar: gradient avatar, greeting + name, and a notifications button. */
export function HomeHeader({
  greeting,
  name,
  initials,
  notificationsLabel,
  onPressNotifications,
}: HomeHeaderProps) {
  return (
    <View className="flex-row items-center justify-between">
      <View className="flex-row items-center gap-3">
        <Avatar initials={initials} />
        <View>
          <Text className="font-body-bold text-[13px] leading-[16px] text-ink-muted">
            {greeting}
          </Text>
          <Text variant="title">{name}</Text>
        </View>
      </View>
      <IconButton
        accessibilityLabel={notificationsLabel}
        onPress={onPressNotifications}
      >
        <Bell color={palette.mint} size={20} strokeWidth={2} />
      </IconButton>
    </View>
  );
}
