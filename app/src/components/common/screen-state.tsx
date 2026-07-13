import { AlertCircle, Inbox } from "lucide-react-native";
import { ActivityIndicator, View } from "react-native";

import { Button, Card, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

interface ScreenStateProps {
  kind: "loading" | "empty" | "error";
  title: string;
  message?: string;
  retryLabel?: string;
  onRetry?: () => void;
}

export function ScreenState({
  kind,
  title,
  message,
  retryLabel,
  onRetry,
}: ScreenStateProps) {
  const Icon = kind === "error" ? AlertCircle : Inbox;

  return (
    <Card className="items-center gap-3 p-6" accessibilityLiveRegion="polite">
      {kind === "loading" ? (
        <ActivityIndicator color={palette.mint} accessibilityLabel={title} />
      ) : (
        <Icon
          color={kind === "error" ? palette.pink : palette.mint}
          size={30}
        />
      )}
      <View className="gap-1">
        <Text variant="cardTitle" className="text-center">
          {title}
        </Text>
        {message ? (
          <Text variant="caption" className="text-center">
            {message}
          </Text>
        ) : null}
      </View>
      {onRetry && retryLabel ? (
        <Button label={retryLabel} variant="light" onPress={onRetry} />
      ) : null}
    </Card>
  );
}
