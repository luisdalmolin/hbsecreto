import type {
  Notification as AppNotification,
  NotificationCollection,
  NotificationPreferences,
} from "@/api/generated/models";
import {
  getNotificationPreferences,
  updateNotificationPreferences,
} from "@/api/generated/notification-preferences/notification-preferences";
import {
  listNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from "@/api/generated/notifications/notifications";
import { AppScreen } from "@/components/common/app-screen";
import { ScreenState } from "@/components/common/screen-state";
import {
  NotificationListItem,
  NotificationPreferencesCard,
  type NotificationPreferenceKey,
} from "@/components/notifications";
import { Button, Card, Text } from "@/components/ui";
import { apiErrorMessage } from "@/features/shared/presentation";
import { useFocusResource } from "@/hooks/use-focus-resource";
import { openNotificationUrl } from "@/notifications/navigation";
import { useNotifications } from "@/notifications/notification-context";
import * as Linking from "expo-linking";
import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { View } from "react-native";

const loadInbox = (signal: AbortSignal) =>
  listNotifications(undefined, { signal });
const loadPreferences = (signal: AbortSignal) =>
  getNotificationPreferences({ signal });

export default function NotificationsScreen() {
  const { i18n, t } = useTranslation();
  const inbox = useFocusResource(loadInbox);
  const preferences = useFocusResource(loadPreferences);
  const { pushStatus, isRegistering, enablePushNotifications, setUnreadCount } =
    useNotifications();
  const [isMarkingAll, setIsMarkingAll] = useState(false);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [isSavingPreference, setIsSavingPreference] = useState(false);
  const [preferenceSaveError, setPreferenceSaveError] = useState(false);
  const [loadMoreError, setLoadMoreError] = useState(false);

  useEffect(() => {
    if (inbox.data) setUnreadCount(inbox.data.unreadCount);
  }, [inbox.data, setUnreadCount]);

  async function openNotification(
    notification: AppNotification,
  ): Promise<void> {
    if (!notification.readAt) {
      await markNotificationRead(notification.id).catch(() => undefined);
      updateReadState(
        inbox.data,
        notification.id,
        inbox.setData,
        setUnreadCount,
      );
    }

    openNotificationUrl(notification.url);
  }

  function markAllRead(): Promise<void> {
    setIsMarkingAll(true);
    return markAllNotificationsRead()
      .then(() => {
        inbox.setData((current) =>
          current
            ? {
                ...current,
                unreadCount: 0,
                data: current.data.map((notification) => ({
                  ...notification,
                  readAt: notification.readAt ?? new Date().toISOString(),
                })),
              }
            : current,
        );
        setUnreadCount(0);
      })
      .finally(() => setIsMarkingAll(false));
  }

  function loadMore(): Promise<void> {
    const current = inbox.data;
    if (!current || current.meta.currentPage >= current.meta.lastPage) {
      return Promise.resolve();
    }

    setIsLoadingMore(true);
    setLoadMoreError(false);
    return listNotifications({ page: current.meta.currentPage + 1 })
      .then((nextPage) => {
        inbox.setData((latest) =>
          latest
            ? {
                ...nextPage,
                data: [...latest.data, ...nextPage.data],
              }
            : nextPage,
        );
        setUnreadCount(nextPage.unreadCount);
      })
      .catch(() => setLoadMoreError(true))
      .finally(() => setIsLoadingMore(false));
  }

  function changePreference(
    key: NotificationPreferenceKey,
    enabled: boolean,
  ): Promise<void> {
    const current = preferences.data;
    if (!current) return Promise.resolve();

    const updated: NotificationPreferences = { ...current, [key]: enabled };
    setPreferenceSaveError(false);
    setIsSavingPreference(true);
    preferences.setData(updated);

    return updateNotificationPreferences(updated)
      .then(preferences.setData)
      .catch(() => {
        preferences.setData(current);
        setPreferenceSaveError(true);
      })
      .finally(() => setIsSavingPreference(false));
  }

  return (
    <AppScreen
      title={t("notifications.title")}
      subtitle={t("notifications.subtitle")}
      back
      refreshing={inbox.isRefreshing || preferences.isRefreshing}
      onRefresh={() => {
        inbox.refresh();
        preferences.refresh();
      }}
      action={
        inbox.data && inbox.data.unreadCount > 0 ? (
          <Button
            label={t("notifications.markAllRead")}
            size="sm"
            variant="light"
            disabled={isMarkingAll}
            onPress={() => void markAllRead()}
          />
        ) : null
      }
    >
      <PushPermissionCard
        status={pushStatus}
        isRegistering={isRegistering}
        onEnable={() => void enablePushNotifications()}
        onOpenSettings={() => void Linking.openSettings()}
      />
      <NotificationPreferencesCard
        preferences={preferences.data}
        isLoading={preferences.isLoading}
        isSaving={isSavingPreference}
        hasError={Boolean(preferences.error) || preferenceSaveError}
        onRetry={preferences.refresh}
        onChange={(key, enabled) => void changePreference(key, enabled)}
      />
      {inbox.isLoading && !inbox.data ? (
        <ScreenState kind="loading" title={t("notifications.loading")} />
      ) : null}
      {inbox.error && !inbox.data ? (
        <ScreenState
          kind="error"
          title={t("notifications.loadError")}
          message={apiErrorMessage(inbox.error, t)}
          retryLabel={t("common.retry")}
          onRetry={inbox.refresh}
        />
      ) : null}
      {!inbox.isLoading && inbox.data?.data.length === 0 ? (
        <ScreenState
          kind="empty"
          title={t("notifications.empty")}
          message={t("notifications.emptyHint")}
        />
      ) : null}
      <View className="gap-3">
        {inbox.data?.data.map((notification) => (
          <NotificationListItem
            key={notification.id}
            notification={notification}
            locale={i18n.language}
            onPress={() => void openNotification(notification)}
          />
        ))}
      </View>
      {loadMoreError ? (
        <Text variant="caption" className="text-center text-pink">
          {t("notifications.loadMoreError")}
        </Text>
      ) : null}
      {inbox.data && inbox.data.meta.currentPage < inbox.data.meta.lastPage ? (
        <Button
          label={
            isLoadingMore
              ? t("notifications.loadingMore")
              : t("notifications.loadMore")
          }
          variant="light"
          disabled={isLoadingMore}
          onPress={() => void loadMore()}
        />
      ) : null}
    </AppScreen>
  );
}

interface PushPermissionCardProps {
  status: ReturnType<typeof useNotifications>["pushStatus"];
  isRegistering: boolean;
  onEnable(): void;
  onOpenSettings(): void;
}

function PushPermissionCard({
  status,
  isRegistering,
  onEnable,
  onOpenSettings,
}: PushPermissionCardProps) {
  const { t } = useTranslation();

  if (status === "checking" || status === "granted") return null;

  const canEnable = status === "undetermined" || status === "error";
  const canOpenSettings = status === "denied";

  return (
    <Card className="gap-3 border border-hairline p-4" shadow="none">
      <View className="gap-1">
        <Text variant="cardTitle">{t("notifications.push.title")}</Text>
        <Text variant="caption">{t(`notifications.push.${status}`)}</Text>
      </View>
      {canEnable ? (
        <Button
          label={
            isRegistering
              ? t("notifications.push.enabling")
              : t("notifications.push.enable")
          }
          disabled={isRegistering}
          onPress={onEnable}
        />
      ) : null}
      {canOpenSettings ? (
        <Button
          label={t("notifications.push.openSettings")}
          variant="light"
          onPress={onOpenSettings}
        />
      ) : null}
    </Card>
  );
}

function updateReadState(
  current: NotificationCollection | undefined,
  notificationId: string,
  setData: React.Dispatch<
    React.SetStateAction<NotificationCollection | undefined>
  >,
  setUnreadCount: (count: number) => void,
): void {
  if (!current) return;

  const unreadCount = Math.max(0, current.unreadCount - 1);
  setData({
    ...current,
    unreadCount,
    data: current.data.map((notification) =>
      notification.id === notificationId
        ? { ...notification, readAt: new Date().toISOString() }
        : notification,
    ),
  });
  setUnreadCount(unreadCount);
}
