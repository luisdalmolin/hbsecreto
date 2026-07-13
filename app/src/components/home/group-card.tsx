import { Gift } from "lucide-react-native";
import { useTranslation } from "react-i18next";
import { Pressable, View } from "react-native";

import { Badge, Button, Card, Text } from "@/components/ui";
import type { Group, GroupAccent } from "@/data/home";
import { cn } from "@/lib/utils";
import { palette } from "@/theme/tokens";

const tileAccent: Record<GroupAccent, { bg: string; color: string }> = {
  mint: { bg: "bg-mint-tint", color: palette.mint },
  pink: { bg: "bg-pink-tint", color: palette.pink },
};

export interface GroupCardProps {
  group: Group;
  onPress?: (group: Group) => void;
  onPressDraw?: (group: Group) => void;
}

/** A single group row: icon tile, name, member count, and status/action. */
export function GroupCard({ group, onPress, onPressDraw }: GroupCardProps) {
  const { t } = useTranslation();
  const accent = tileAccent[group.accent];

  return (
    <Card className="flex-row items-center gap-[13px] p-4">
      <Pressable
        className="flex-1 flex-row items-center gap-[13px] active:opacity-70"
        onPress={() => onPress?.(group)}
        accessibilityRole="button"
      >
        <View
          className={cn(
            "h-12 w-12 items-center justify-center rounded-tile",
            accent.bg,
          )}
        >
          <Gift color={accent.color} size={24} strokeWidth={2} />
        </View>
        <View className="flex-1">
          <Text variant="cardTitle">{group.name}</Text>
          <Text className="font-body text-[13px] leading-[18px] text-ink-muted">
            {t("home.groups.members", { value: group.memberCount })}
          </Text>
        </View>
      </Pressable>
      {group.status === "drawn" ? (
        <Badge variant="success" label={t("home.groups.drawn")} />
      ) : !onPressDraw ? (
        <Badge variant="neutral" label={t("home.groups.pending")} />
      ) : (
        <Button
          variant="pink"
          size="sm"
          label={t("home.groups.draw")}
          onPress={() => onPressDraw?.(group)}
        />
      )}
    </Card>
  );
}
