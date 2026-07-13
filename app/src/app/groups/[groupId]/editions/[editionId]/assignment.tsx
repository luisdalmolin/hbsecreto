import { useFocusEffect, useLocalSearchParams } from "expo-router";
import { Eye, Gift } from "lucide-react-native";
import { useCallback, useState } from "react";
import { useTranslation } from "react-i18next";
import { ActivityIndicator, View } from "react-native";

import { getMyAssignment } from "@/api/generated/assignments/assignments";
import type { MyAssignment } from "@/api/generated/models";
import { AppScreen } from "@/components/common/app-screen";
import { Button, Card, Text } from "@/components/ui";
import { apiErrorMessage, parseRouteId } from "@/features/shared/presentation";
import { palette } from "@/theme/tokens";

export default function MyAssignmentScreen() {
  const { t } = useTranslation();
  const params = useLocalSearchParams<{ groupId: string; editionId: string }>();
  const groupId = parseRouteId(params.groupId);
  const editionId = parseRouteId(params.editionId);
  const [assignment, setAssignment] = useState<MyAssignment>();
  const [error, setError] = useState<unknown>();
  const [revealing, setRevealing] = useState(false);

  useFocusEffect(
    useCallback(
      () => () => {
        setAssignment(undefined);
        setError(undefined);
      },
      [],
    ),
  );

  async function reveal(): Promise<void> {
    if (!groupId || !editionId || revealing) return;
    setError(undefined);
    setRevealing(true);
    try {
      // Privacy boundary: this is the only place that requests the receiver,
      // and it only runs after an explicit user gesture.
      setAssignment(await getMyAssignment(groupId, editionId));
    } catch (exception) {
      setError(exception);
    }
    setRevealing(false);
  }

  return (
    <AppScreen title={t("editions.myAssignment")} back>
      <Card
        shadow="hero"
        className="items-center gap-4 overflow-hidden border border-hairline p-7"
      >
        <View className="h-16 w-16 items-center justify-center rounded-3xl bg-mint-tint">
          <Gift color={palette.mint} size={34} />
        </View>
        {assignment ? (
          <>
            <Text variant="eyebrow" className="text-mint">
              {t("assignments.youDrew")}
            </Text>
            <Text
              variant="hero"
              className="text-center"
              accessibilityLiveRegion="polite"
            >
              {assignment.receiver.displayName}
            </Text>
            <Text variant="caption" className="text-center">
              {t("assignments.privateHint")}
            </Text>
          </>
        ) : (
          <>
            <Text variant="section" className="text-center">
              {t("assignments.sealedTitle")}
            </Text>
            <Text variant="caption" className="text-center">
              {t("assignments.sealedBody")}
            </Text>
            <Button
              label={
                revealing
                  ? t("assignments.revealing")
                  : t("assignments.revealMine")
              }
              leftIcon={
                revealing ? (
                  <ActivityIndicator color={palette.white} />
                ) : (
                  <Eye color={palette.white} size={18} />
                )
              }
              disabled={revealing || !groupId || !editionId}
              onPress={() => void reveal()}
              accessibilityHint={t("assignments.sealedBody")}
            />
          </>
        )}
        {error ? (
          <Text
            className="text-center text-pink-deep"
            accessibilityRole="alert"
          >
            {apiErrorMessage(error, t)}
          </Text>
        ) : null}
      </Card>
    </AppScreen>
  );
}
