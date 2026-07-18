import { LinearGradient } from "expo-linear-gradient";
import { CalendarDays, Gift, PartyPopper, Shuffle } from "lucide-react-native";
import { StyleSheet, View } from "react-native";

import type { DashboardEdition } from "@/api/generated/models";
import { Badge, Button, Card, Text } from "@/components/ui";
import { gradients, palette } from "@/theme/tokens";

interface ActiveEditionCardProps {
  edition: DashboardEdition;
  eyebrow: string;
  groupLabel: string;
  statusLabel: string;
  participantLabel: string;
  eventDateLabel: string;
  budgetLabel: string;
  message: string;
  actionLabel: string;
  onPress: () => void;
}

const editionIcon = {
  draft: CalendarDays,
  open: Shuffle,
  drawn: Gift,
  revealed: PartyPopper,
} as const;

export function ActiveEditionCard({
  edition,
  eyebrow,
  groupLabel,
  statusLabel,
  participantLabel,
  eventDateLabel,
  budgetLabel,
  message,
  actionLabel,
  onPress,
}: ActiveEditionCardProps) {
  const Icon = editionIcon[edition.status];
  const details = [
    participantLabel,
    edition.eventDate ? eventDateLabel : undefined,
    edition.budgetCents !== null ? budgetLabel : undefined,
  ].filter((detail): detail is string => Boolean(detail));

  return (
    <Card
      shadow="hero"
      className="overflow-hidden rounded-hero border border-hairline p-[22px]"
    >
      <LinearGradient
        colors={gradients.brand}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={styles.topBar}
      />
      <View className="mt-1 flex-row items-start justify-between gap-3">
        <View className="flex-1 gap-1">
          <Text variant="eyebrow" className="text-mint">
            {eyebrow}
          </Text>
          <Text variant="section">{edition.editionName}</Text>
          <Text variant="caption">{groupLabel}</Text>
        </View>
        <View className="h-12 w-12 items-center justify-center rounded-tile bg-mint-tint">
          <Icon color={palette.mint} size={25} strokeWidth={2} />
        </View>
      </View>

      <View className="mt-4 flex-row flex-wrap items-center gap-2">
        <Badge
          label={statusLabel}
          variant={
            edition.status === "drawn" || edition.status === "revealed"
              ? "success"
              : "neutral"
          }
        />
        {details.map((detail) => (
          <View
            key={detail}
            className="rounded-full border border-hairline bg-bg px-3 py-1.5"
          >
            <Text variant="caption" className="text-ink-soft">
              {detail}
            </Text>
          </View>
        ))}
      </View>

      <Text className="mb-4 mt-4 text-ink-soft">{message}</Text>
      <Button label={actionLabel} onPress={onPress} />
    </Card>
  );
}

const styles = StyleSheet.create({
  topBar: {
    position: "absolute",
    top: 0,
    left: 0,
    right: 0,
    height: 6,
  },
});
