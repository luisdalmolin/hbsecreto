import { ChevronRight, Gift, MessageCircleQuestion } from "lucide-react-native";
import { useTranslation } from "react-i18next";
import { Pressable, View } from "react-native";

import type { Conversation } from "@/api/generated/models";
import { Badge, Card, Text } from "@/components/ui";
import {
  conversationTitle,
  formatConversationActivity,
} from "@/features/conversations/presentation";
import { cn } from "@/lib/utils";
import { palette } from "@/theme/tokens";

interface ConversationListItemProps {
  conversation: Conversation;
  onPress: () => void;
}

export function ConversationListItem({
  conversation,
  onPress,
}: ConversationListItemProps) {
  const { t, i18n } = useTranslation();
  const isGiver = conversation.role === "giver";
  const activity = formatConversationActivity(
    conversation.lastMessageAt,
    i18n.language,
  );

  return (
    <Card className="overflow-hidden p-0">
      <Pressable
        className="flex-row items-center gap-3 p-4 active:opacity-75"
        accessibilityRole="button"
        accessibilityLabel={conversationTitle(conversation, t)}
        onPress={onPress}
      >
        <View
          className={cn(
            "h-12 w-12 items-center justify-center rounded-tile",
            isGiver ? "bg-mint-tint" : "bg-pink-tint",
          )}
        >
          {isGiver ? (
            <Gift color={palette.mint} size={24} />
          ) : (
            <MessageCircleQuestion color={palette.pink} size={24} />
          )}
        </View>
        <View className="flex-1 gap-0.5">
          <Text variant="cardTitle">{conversationTitle(conversation, t)}</Text>
          <Text variant="caption">
            {t(isGiver ? "chat.personYouDrew" : "chat.yourSecretSanta")}
          </Text>
          <Text variant="caption">
            {activity
              ? t("chat.lastActivity", { value: activity })
              : t("chat.noMessages")}
          </Text>
        </View>
        <View className="items-end gap-2">
          {conversation.unreadCount > 0 ? (
            <Badge
              label={t("chat.unread", { count: conversation.unreadCount })}
            />
          ) : null}
          <ChevronRight color={palette.inkMuted} size={20} />
        </View>
      </Pressable>
    </Card>
  );
}
