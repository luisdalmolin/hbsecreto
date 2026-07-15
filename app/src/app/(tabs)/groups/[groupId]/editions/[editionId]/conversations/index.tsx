import { router, useLocalSearchParams } from "expo-router";
import { useTranslation } from "react-i18next";
import { View } from "react-native";

import { listConversations } from "@/api/generated/conversations/conversations";
import { AppScreen } from "@/components/common/app-screen";
import { ScreenState } from "@/components/common/screen-state";
import { ConversationListItem } from "@/components/conversations/conversation-list-item";
import { apiErrorMessage, parseRouteId } from "@/features/shared/presentation";
import { useFocusResource } from "@/hooks/use-focus-resource";

export default function ConversationsScreen() {
  const { t } = useTranslation();
  const params = useLocalSearchParams<{ groupId: string; editionId: string }>();
  const groupId = parseRouteId(params.groupId);
  const editionId = parseRouteId(params.editionId);
  const load = (signal: AbortSignal) => {
    if (!groupId || !editionId) {
      return Promise.reject(new Error(t("common.errors.notFound")));
    }

    return listConversations(groupId, editionId, { signal });
  };
  const resource = useFocusResource(load);

  return (
    <AppScreen
      title={t("chat.title")}
      subtitle={t("chat.subtitle")}
      back
      refreshing={resource.isRefreshing}
      onRefresh={resource.refresh}
    >
      {!resource.data ? (
        <ScreenState
          kind={resource.isLoading ? "loading" : "error"}
          title={resource.isLoading ? t("chat.loading") : t("chat.loadError")}
          message={
            resource.error ? apiErrorMessage(resource.error, t) : undefined
          }
          retryLabel={t("common.retry")}
          onRetry={resource.refresh}
        />
      ) : resource.data.data.length === 0 ? (
        <ScreenState
          kind="empty"
          title={t("chat.empty")}
          message={t("chat.emptyHint")}
        />
      ) : (
        <View className="gap-3">
          {resource.data.data.map((conversation) => (
            <ConversationListItem
              key={conversation.id}
              conversation={conversation}
              onPress={() =>
                router.push({
                  pathname:
                    "/groups/[groupId]/editions/[editionId]/conversations/[conversationId]",
                  params: {
                    groupId: String(groupId),
                    editionId: String(editionId),
                    conversationId: String(conversation.id),
                  },
                })
              }
            />
          ))}
        </View>
      )}
    </AppScreen>
  );
}
