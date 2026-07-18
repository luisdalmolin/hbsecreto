import { router } from "expo-router";
import type { TFunction } from "i18next";
import { useTranslation } from "react-i18next";
import { ScrollView, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { getDashboard } from "@/api/generated/dashboard/dashboard";
import type { DashboardEdition, DashboardGroup } from "@/api/generated/models";
import { useAuthSession } from "@/auth/auth-session";
import { ScreenState } from "@/components/common/screen-state";
import {
  ActiveEditionCard,
  activeEditionLabels,
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

function loadDashboard(signal: AbortSignal) {
  return getDashboard({ signal });
}

function homeGroup(group: DashboardGroup, index: number): HomeGroup {
  return {
    id: String(group.id),
    name: group.name,
    memberCount: group.memberCount,
    status:
      group.currentEditionStatus === "drawn" ||
      group.currentEditionStatus === "revealed"
        ? "drawn"
        : "pending",
    accent: index % 2 === 0 ? "mint" : "pink",
  };
}

function featuredCopy(
  edition: DashboardEdition,
  t: TFunction,
): { message: string; actionLabel: string } {
  if (edition.status === "drawn" && edition.assignmentAvailable) {
    return {
      message: t("home.featured.messages.assignmentReady"),
      actionLabel: t("home.featured.actions.revealAssignment"),
    };
  }

  if (edition.status === "drawn") {
    return {
      message: t("home.featured.messages.drawn"),
      actionLabel: t("home.featured.actions.viewEdition"),
    };
  }

  if (edition.status === "revealed") {
    return {
      message: t("home.featured.messages.revealed"),
      actionLabel: t("home.featured.actions.viewResults"),
    };
  }

  if (edition.status === "open" && edition.isAdmin) {
    return {
      message: t("home.featured.messages.openAdmin"),
      actionLabel: t("home.featured.actions.prepareDraw"),
    };
  }

  if (edition.status === "open" && edition.isParticipant) {
    return {
      message: t("home.featured.messages.openParticipant", {
        count: edition.wishCount,
      }),
      actionLabel: t("home.featured.actions.updateWishes"),
    };
  }

  if (edition.isAdmin) {
    return {
      message: t("home.featured.messages.draftAdmin"),
      actionLabel: t("home.featured.actions.continueSetup"),
    };
  }

  return {
    message: t("home.featured.messages.draftParticipant"),
    actionLabel: t("home.featured.actions.viewEdition"),
  };
}

function openFeaturedEdition(edition: DashboardEdition): void {
  const params = {
    groupId: String(edition.groupId),
    editionId: String(edition.editionId),
  };

  if (edition.status === "drawn" && edition.assignmentAvailable) {
    router.push({
      pathname: "/groups/[groupId]/editions/[editionId]/assignment",
      params,
    });
    return;
  }

  if (edition.status === "revealed") {
    router.push({
      pathname: "/groups/[groupId]/editions/[editionId]/assignments",
      params,
    });
    return;
  }

  if (edition.status === "open" && edition.isAdmin) {
    router.push({
      pathname: "/groups/[groupId]/editions/[editionId]/draw",
      params,
    });
    return;
  }

  if (edition.status === "open" && edition.isParticipant) {
    router.push({
      pathname: "/groups/[groupId]/editions/[editionId]/wishes",
      params,
    });
    return;
  }

  router.push({
    pathname: "/groups/[groupId]/editions/[editionId]",
    params,
  });
}

export default function HomeScreen() {
  const { t } = useTranslation();
  const { user } = useAuthSession();
  const greeting = t(`home.greeting.${getGreetingKey()}`);
  const dashboard = useFocusResource(loadDashboard);
  const { unreadCount } = useNotifications();
  const featuredEdition = dashboard.data?.featuredEdition;
  const featuredLabels = featuredEdition
    ? activeEditionLabels(featuredEdition)
    : undefined;
  const featuredContent = featuredEdition
    ? featuredCopy(featuredEdition, t)
    : undefined;

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

          {dashboard.isLoading && !dashboard.data ? (
            <ScreenState kind="loading" title={t("home.loading")} />
          ) : null}
          {dashboard.error && !dashboard.data ? (
            <ScreenState
              kind="error"
              title={t("home.loadError")}
              message={apiErrorMessage(dashboard.error, t)}
              retryLabel={t("common.retry")}
              onRetry={dashboard.refresh}
            />
          ) : null}

          {featuredEdition && featuredLabels && featuredContent ? (
            <ActiveEditionCard
              edition={featuredEdition}
              eyebrow={t("home.featured.eyebrow")}
              groupLabel={t("home.featured.group", {
                group: featuredEdition.groupName,
              })}
              statusLabel={t(`editions.status.${featuredEdition.status}`)}
              participantLabel={t("home.featured.participants", {
                count: featuredEdition.participantCount,
              })}
              eventDateLabel={t("home.featured.eventDate", {
                date: featuredLabels.eventDate,
              })}
              budgetLabel={t("home.featured.budget", {
                budget: featuredLabels.budget,
              })}
              message={featuredContent.message}
              actionLabel={featuredContent.actionLabel}
              onPress={() => openFeaturedEdition(featuredEdition)}
            />
          ) : null}

          {dashboard.data ? (
            <>
              <SectionHeader
                title={t("home.groups.title")}
                actionLabel={t("home.groups.seeAll")}
                onPressAction={() => router.push("/groups")}
              />
              {dashboard.data.groups.map((group, index) => (
                <GroupCard
                  key={group.id}
                  group={homeGroup(group, index)}
                  onPress={() =>
                    router.push({
                      pathname: "/groups/[groupId]",
                      params: { groupId: String(group.id) },
                    })
                  }
                />
              ))}
              {dashboard.data.groups.length === 0 ? (
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
            </>
          ) : null}

          {dashboard.error && dashboard.data ? (
            <ScreenState
              kind="error"
              title={t("home.refreshError")}
              message={apiErrorMessage(dashboard.error, t)}
              retryLabel={t("common.retry")}
              onRetry={dashboard.refresh}
            />
          ) : null}
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}
