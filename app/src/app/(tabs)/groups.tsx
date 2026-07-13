import { router } from "expo-router";
import { Plus } from "lucide-react-native";
import { useTranslation } from "react-i18next";
import { FlatList, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { listGroups } from "@/api/generated/groups/groups";
import { IconButton, Text } from "@/components/ui";
import { ScreenState } from "@/components/common/screen-state";
import { GroupListItem } from "@/components/groups/group-list-item";
import { useFocusResource } from "@/hooks/use-focus-resource";
import { apiErrorMessage } from "@/features/shared/presentation";
import { palette } from "@/theme/tokens";

const loadGroups = (signal: AbortSignal) => listGroups({ signal });

export default function GroupsScreen() {
  const { t } = useTranslation();
  const groups = useFocusResource(loadGroups);

  return (
    <View className="flex-1 bg-bg">
      <SafeAreaView edges={["top"]} className="flex-1">
        <FlatList
          data={groups.data?.data ?? []}
          keyExtractor={(group) => String(group.id)}
          renderItem={({ item }) => <GroupListItem group={item} />}
          contentContainerStyle={{
            gap: 12,
            paddingHorizontal: 18,
            paddingTop: 12,
            paddingBottom: 28,
            flexGrow: 1,
          }}
          refreshing={groups.isRefreshing}
          onRefresh={groups.refresh}
          ListHeaderComponent={
            <View className="mb-1 gap-2">
              <View className="flex-row items-center gap-3">
                <View className="flex-1">
                  <Text variant="title">{t("groups.title")}</Text>
                  <Text variant="caption">{t("groups.subtitle")}</Text>
                </View>
                <IconButton
                  accessibilityLabel={t("groups.create")}
                  onPress={() => router.push("/groups/new")}
                >
                  <Plus color={palette.mintDeep} size={22} />
                </IconButton>
              </View>
              {groups.error && groups.data ? (
                <Text className="text-pink-deep" accessibilityRole="alert">
                  {apiErrorMessage(groups.error, t)}
                </Text>
              ) : null}
            </View>
          }
          ListEmptyComponent={
            groups.isLoading ? (
              <ScreenState kind="loading" title={t("groups.loading")} />
            ) : groups.error ? (
              <ScreenState
                kind="error"
                title={t("groups.loadError")}
                message={apiErrorMessage(groups.error, t)}
                retryLabel={t("common.retry")}
                onRetry={groups.refresh}
              />
            ) : (
              <ScreenState
                kind="empty"
                title={t("groups.empty")}
                message={t("groups.emptyHint")}
              />
            )
          }
        />
      </SafeAreaView>
    </View>
  );
}
