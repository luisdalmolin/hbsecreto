import { router, useLocalSearchParams } from "expo-router";
import { ArrowRight } from "lucide-react-native";
import { useTranslation } from "react-i18next";
import { FlatList, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { listAssignments } from "@/api/generated/assignments/assignments";
import type { Assignment } from "@/api/generated/models";
import { ScreenState } from "@/components/common/screen-state";
import { Button, Card, Text } from "@/components/ui";
import { apiErrorMessage, parseRouteId } from "@/features/shared/presentation";
import { useFocusResource } from "@/hooks/use-focus-resource";
import { palette } from "@/theme/tokens";

export default function AssignmentsScreen() {
  const { t } = useTranslation();
  const params = useLocalSearchParams<{ groupId: string; editionId: string }>();
  const groupId = parseRouteId(params.groupId);
  const editionId = parseRouteId(params.editionId);
  const load = (signal: AbortSignal) => {
    if (!groupId || !editionId)
      return Promise.reject(new Error(t("common.errors.notFound")));
    return listAssignments(groupId, editionId, { signal });
  };
  const resource = useFocusResource(load);

  return (
    <View className="flex-1 bg-bg">
      <SafeAreaView edges={["top"]} className="flex-1">
        <FlatList
          data={resource.data?.data ?? []}
          keyExtractor={(item) =>
            `${item.giver.participantId}-${item.receiver.participantId}`
          }
          contentContainerStyle={{
            gap: 12,
            paddingHorizontal: 18,
            paddingBottom: 36,
            flexGrow: 1,
          }}
          refreshing={resource.isRefreshing}
          onRefresh={resource.refresh}
          ListHeaderComponent={
            <View className="mb-1 flex-row items-center gap-3 pt-3">
              <Button
                label={t("common.back")}
                variant="light"
                onPress={() => router.back()}
              />
              <View className="flex-1">
                <Text variant="title">{t("assignments.listTitle")}</Text>
                <Text variant="caption">{t("assignments.listSubtitle")}</Text>
              </View>
            </View>
          }
          renderItem={({ item }) => <AssignmentRow assignment={item} />}
          ListEmptyComponent={
            resource.isLoading ? (
              <ScreenState kind="loading" title={t("common.loading")} />
            ) : resource.error ? (
              <ScreenState
                kind="error"
                title={t("assignments.unavailable")}
                message={apiErrorMessage(resource.error, t)}
                retryLabel={t("common.retry")}
                onRetry={resource.refresh}
              />
            ) : (
              <ScreenState kind="empty" title={t("assignments.empty")} />
            )
          }
        />
      </SafeAreaView>
    </View>
  );
}

function AssignmentRow({ assignment }: { assignment: Assignment }) {
  const { t } = useTranslation();
  return (
    <Card
      className="flex-row items-center gap-3 p-4"
      accessibilityLabel={t("assignments.pair", {
        giver: assignment.giver.displayName,
        receiver: assignment.receiver.displayName,
      })}
    >
      <View className="flex-1">
        <Text variant="cardTitle">{assignment.giver.displayName}</Text>
      </View>
      <ArrowRight color={palette.mint} size={20} />
      <View className="flex-1">
        <Text variant="cardTitle" className="text-right">
          {assignment.receiver.displayName}
        </Text>
      </View>
    </Card>
  );
}
