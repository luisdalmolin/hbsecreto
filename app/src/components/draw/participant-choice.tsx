import { Check, UserRound } from "lucide-react-native";
import { Pressable, View } from "react-native";

import { Text } from "@/components/ui";
import { cn } from "@/lib/utils";
import { palette } from "@/theme/tokens";

interface ParticipantChoiceProps {
  label: string;
  selected: boolean;
  disabled?: boolean;
  onPress: () => void;
}

export function ParticipantChoice({
  label,
  selected,
  disabled = false,
  onPress,
}: ParticipantChoiceProps) {
  return (
    <Pressable
      className={cn(
        "min-h-12 flex-row items-center gap-3 rounded-card border bg-card px-4 py-3 active:opacity-80",
        selected ? "border-mint bg-mint-tint" : "border-hairline",
        disabled && "opacity-40",
      )}
      accessibilityRole="button"
      accessibilityState={{ selected, disabled }}
      disabled={disabled}
      onPress={onPress}
    >
      <View
        className={cn(
          "h-8 w-8 items-center justify-center rounded-full",
          selected ? "bg-mint" : "bg-mint-tint",
        )}
      >
        {selected ? (
          <Check color={palette.white} size={17} />
        ) : (
          <UserRound color={palette.mintDeep} size={17} />
        )}
      </View>
      <Text variant="bodyBold" className="flex-1">
        {label}
      </Text>
    </Pressable>
  );
}
