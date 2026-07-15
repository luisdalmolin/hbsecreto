import { View } from "react-native";

import type { Message } from "@/api/generated/models";
import { Text } from "@/components/ui";
import { cn } from "@/lib/utils";

interface MessageBubbleProps {
  message: Message;
  time: string;
}

export function MessageBubble({ message, time }: MessageBubbleProps) {
  return (
    <View className={cn("max-w-[84%] gap-1", message.isMine && "self-end")}>
      <View
        className={cn(
          "rounded-card px-4 py-3",
          message.isMine
            ? "rounded-br-md bg-mint"
            : "rounded-bl-md border border-hairline bg-card",
        )}
      >
        <Text className={message.isMine ? "text-white" : undefined}>
          {message.body}
        </Text>
      </View>
      <Text
        variant="caption"
        className={cn("px-2 text-[11px]", message.isMine && "text-right")}
      >
        {time}
      </Text>
    </View>
  );
}
