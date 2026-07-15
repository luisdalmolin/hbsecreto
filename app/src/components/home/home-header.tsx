import { Bell } from "lucide-react-native";
import { View } from "react-native";

import { Avatar, IconButton, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

export interface HomeHeaderProps {
  greeting: string;
  name: string;
  initials: string;
  notificationsLabel: string;
  notificationCount?: number;
  onPressNotifications?: () => void;
}

/** Top bar: gradient avatar, greeting + name, and a notifications button. */
export function HomeHeader({
  greeting,
  name,
  initials,
  notificationsLabel,
  notificationCount = 0,
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
        {notificationCount > 0 ? (
          <View className="absolute -right-1 -top-1 min-w-[18px] items-center justify-center rounded-full bg-pink px-1 py-0.5">
            <Text className="font-body-black text-[10px] leading-[12px] text-white">
              {notificationCount > 99 ? "99+" : notificationCount}
            </Text>
          </View>
        ) : null}
      </IconButton>
    </View>
  );
}
