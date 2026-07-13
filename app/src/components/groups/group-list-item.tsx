import { router } from "expo-router";
import { ChevronRight, Gift } from "lucide-react-native";
import { Pressable, View } from "react-native";

import type { Group } from "@/api/generated/models";
import { Card, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

export function GroupListItem({ group }: { group: Group }) {
  return (
    <Pressable
      onPress={() =>
        router.push({
          pathname: "/groups/[groupId]",
          params: { groupId: String(group.id) },
        })
      }
      accessibilityRole="button"
      accessibilityLabel={group.name}
      className="active:opacity-75"
    >
      <Card className="flex-row items-center gap-3 p-4">
        <View className="h-12 w-12 items-center justify-center rounded-tile bg-mint-tint">
          <Gift color={palette.mint} size={24} />
        </View>
        <View className="flex-1 gap-0.5">
          <Text variant="cardTitle">{group.name}</Text>
          {group.description ? (
            <Text variant="caption" numberOfLines={2}>
              {group.description}
            </Text>
          ) : null}
        </View>
        <ChevronRight color={palette.inkMuted} size={20} />
      </Card>
    </Pressable>
  );
}
