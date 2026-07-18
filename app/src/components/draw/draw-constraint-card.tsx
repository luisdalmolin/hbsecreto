import { Ban, Trash2 } from "lucide-react-native";
import { View } from "react-native";

import { Card, IconButton, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

interface DrawConstraintCardProps {
  firstName: string;
  secondName: string;
  removeLabel: string;
  disabled?: boolean;
  onRemove: () => void;
}

export function DrawConstraintCard({
  firstName,
  secondName,
  removeLabel,
  disabled = false,
  onRemove,
}: DrawConstraintCardProps) {
  return (
    <Card className="flex-row items-center gap-3 p-4">
      <View className="h-10 w-10 items-center justify-center rounded-tile bg-pink-tint">
        <Ban color={palette.pink} size={20} />
      </View>
      <View className="flex-1">
        <Text variant="cardTitle">{firstName}</Text>
        <Text variant="caption">{secondName}</Text>
      </View>
      <IconButton
        className="bg-pink-tint"
        accessibilityLabel={removeLabel}
        accessibilityState={{ disabled }}
        disabled={disabled}
        onPress={onRemove}
      >
        <Trash2 color={palette.pink} size={19} />
      </IconButton>
    </Card>
  );
}
