import { router } from "expo-router";
import { useTranslation } from "react-i18next";
import { ScrollView, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { useAuthSession } from "@/auth/auth-session";
import { listEditions } from "@/api/generated/editions/editions";
import { listGroupMembers } from "@/api/generated/group-members/group-members";
import { listGroups } from "@/api/generated/groups/groups";
import { ScreenState } from "@/components/common/screen-state";
import {
  CreateGroupCard,
  GroupCard,
  HomeHeader,
  SectionHeader,
} from "@/components/home";
import type { Group as HomeGroup } from "@/data/home";
import { apiErrorMessage, initials } from "@/features/shared/presentation";
import { useFocusResource } from "@/hooks/use-focus-resource";
import { getGreetingKey } from "@/lib/greeting";
import { useNotifications } from "@/notifications/notification-context";

async function loadHomeGroups(signal: AbortSignal): Promise<HomeGroup[]> {
  const collection = await listGroups({ signal });
  return Promise.all(
    collection.data.slice(0, 3).map(async (group, index) => {
      const [members, editions] = await Promise.all([
        listGroupMembers(group.id, { signal }),
        listEditions(group.id, { signal }),
      ]);
      return {
        id: String(group.id),
        name: group.name,
        memberCount: members.data.filter(
          (member) => member.status !== "inactive",
        ).length,
        status: editions.data.some(
          (edition) =>
            edition.status === "drawn" || edition.status === "revealed",
        )
          ? ("drawn" as const)
          : ("pending" as const),
        accent: index % 2 === 0 ? ("mint" as const) : ("pink" as const),
      };
    }),
  );
}

export default function HomeScreen() {
  const { t } = useTranslation();
  const { user } = useAuthSession();
  const greeting = t(`home.greeting.${getGreetingKey()}`);
  const groups = useFocusResource(loadHomeGroups);
  const { unreadCount } = useNotifications();

  return (
    <View className="flex-1 bg-bg">
      <SafeAreaView edges={["top"]} className="flex-1">
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={{
            gap: 18,
            paddingHorizontal: 18,
            paddingTop: 8,
            paddingBottom: 24,
          }}
        >
          <HomeHeader
            greeting={greeting}
            name={user?.name ?? ""}
            initials={initials(user?.name ?? "")}
            notificationsLabel={t("home.notifications")}
            notificationCount={unreadCount}
            onPressNotifications={() => router.push("/notifications")}
          />
          <SectionHeader
            title={t("home.groups.title")}
            actionLabel={t("home.groups.seeAll")}
            onPressAction={() => router.push("/groups")}
          />
          {groups.isLoading && !groups.data ? (
            <ScreenState kind="loading" title={t("groups.loading")} />
          ) : null}
          {groups.error && !groups.data ? (
            <ScreenState
              kind="error"
              title={t("groups.loadError")}
              message={apiErrorMessage(groups.error, t)}
              retryLabel={t("common.retry")}
              onRetry={groups.refresh}
            />
          ) : null}
          {groups.data?.map((group) => (
            <GroupCard
              key={group.id}
              group={group}
              onPress={() =>
                router.push({
                  pathname: "/groups/[groupId]",
                  params: { groupId: group.id },
                })
              }
            />
          ))}
          {!groups.isLoading && !groups.error && groups.data?.length === 0 ? (
            <ScreenState
              kind="empty"
              title={t("groups.empty")}
              message={t("groups.emptyHint")}
            />
          ) : null}
          <CreateGroupCard
            label={t("home.groups.create")}
            onPress={() => router.push("/groups/new")}
          />
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}
