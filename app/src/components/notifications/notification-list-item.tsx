import type { Notification as AppNotification } from "@/api/generated/models";
import { Eye, Gift, MessageCircle } from "lucide-react-native";
import { Pressable, View } from "react-native";

import { Card, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

const dateTimeFormatters = new Map<string, Intl.DateTimeFormat>();

interface NotificationListItemProps {
  notification: AppNotification;
  locale: string;
  onPress(): void;
}

export function NotificationListItem({
  notification,
  locale,
  onPress,
}: NotificationListItemProps) {
  const Icon =
    notification.type === "conversation-message"
      ? MessageCircle
      : notification.type === "edition-revealed"
        ? Eye
        : Gift;

  return (
    <Pressable onPress={onPress} accessibilityRole="button">
      <Card className="flex-row gap-3 p-4 active:opacity-85">
        <View className="h-10 w-10 items-center justify-center rounded-full bg-mint-tint">
          <Icon color={palette.mintDeep} size={20} />
        </View>
        <View className="flex-1 gap-1">
          <View className="flex-row items-start gap-2">
            <Text variant="cardTitle" className="flex-1">
              {notification.title}
            </Text>
            {!notification.readAt ? (
              <View
                className="mt-1.5 h-2.5 w-2.5 rounded-full bg-pink"
                accessibilityLabel={notification.title}
              />
            ) : null}
          </View>
          <Text variant="caption" className="text-ink-soft">
            {notification.body}
          </Text>
          <Text variant="caption" className="mt-1 text-[11px]">
            {dateTimeFormatter(locale).format(new Date(notification.createdAt))}
          </Text>
        </View>
      </Card>
    </Pressable>
  );
}

function dateTimeFormatter(locale: string): Intl.DateTimeFormat {
  const existing = dateTimeFormatters.get(locale);
  if (existing) return existing;

  const formatter = new Intl.DateTimeFormat(locale, {
    dateStyle: "short",
    timeStyle: "short",
  });
  dateTimeFormatters.set(locale, formatter);
  return formatter;
}
