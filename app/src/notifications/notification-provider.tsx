import {
  type PropsWithChildren,
  useCallback,
  useEffect,
  useMemo,
  useState,
} from "react";
import { useTranslation } from "react-i18next";
import { AppState } from "react-native";

import {
  listNotifications,
  markNotificationRead,
} from "@/api/generated/notifications/notifications";
import { useAuthSession } from "@/auth/auth-session";

import { openNotificationUrl } from "./navigation";
import {
  NotificationContext,
  type NotificationContextValue,
  type PushRegistrationStatus,
} from "./notification-context";
import {
  type PushResponseData,
  registerForPushNotifications,
  subscribeToPushNotifications,
} from "./push-service";

export function NotificationProvider({ children }: PropsWithChildren) {
  const { user } = useAuthSession();
  const { t } = useTranslation();
  const [unreadCount, setUnreadCountState] = useState(0);
  const [pushStatus, setPushStatus] =
    useState<PushRegistrationStatus>("checking");
  const [isRegistering, setIsRegistering] = useState(false);

  const setUnreadCount = useCallback((count: number) => {
    setUnreadCountState(Math.max(0, count));
  }, []);

  const refreshUnreadCount = useCallback(async () => {
    if (!user) {
      setUnreadCountState(0);
      return;
    }

    const inbox = await listNotifications();
    setUnreadCountState(inbox.unreadCount);
  }, [user]);

  const registerForPush = useCallback(
    (requestPermission: boolean): Promise<void> => {
      if (!user) return Promise.resolve();
      setIsRegistering(true);
      return registerForPushNotifications(
        requestPermission,
        t("notifications.push.channelName"),
      )
        .then(setPushStatus)
        .catch(() => setPushStatus("error"))
        .finally(() => setIsRegistering(false));
    },
    [t, user],
  );

  const enablePushNotifications = useCallback(
    () => registerForPush(true),
    [registerForPush],
  );

  useEffect(() => {
    if (!user) return;

    void refreshUnreadCount().catch(() => undefined);
    void registerForPush(false);
  }, [refreshUnreadCount, registerForPush, user]);

  useEffect(() => {
    if (!user) return;

    const unsubscribeFromPush = subscribeToPushNotifications({
      onReceived: () => {
        void refreshUnreadCount().catch(() => undefined);
      },
      onResponse: (data) => {
        void handleNotificationResponse(data, refreshUnreadCount);
      },
      onTokenChanged: () => {
        void registerForPush(false);
      },
    });
    const appStateSubscription = AppState.addEventListener(
      "change",
      (state) => {
        if (state === "active") {
          void refreshUnreadCount().catch(() => undefined);
        }
      },
    );

    return () => {
      unsubscribeFromPush();
      appStateSubscription.remove();
    };
  }, [refreshUnreadCount, registerForPush, user]);

  const value = useMemo<NotificationContextValue>(
    () => ({
      unreadCount: user ? unreadCount : 0,
      pushStatus: user ? pushStatus : "checking",
      isRegistering,
      enablePushNotifications,
      refreshUnreadCount,
      setUnreadCount,
    }),
    [
      enablePushNotifications,
      isRegistering,
      pushStatus,
      refreshUnreadCount,
      setUnreadCount,
      unreadCount,
      user,
    ],
  );

  return (
    <NotificationContext.Provider value={value}>
      {children}
    </NotificationContext.Provider>
  );
}

async function handleNotificationResponse(
  data: PushResponseData,
  refreshUnreadCount: () => Promise<void>,
): Promise<void> {
  const notificationId = data.notificationId;

  if (typeof notificationId === "string") {
    await markNotificationRead(notificationId).catch(() => undefined);
  }

  await refreshUnreadCount().catch(() => undefined);
  openNotificationUrl(data.url);
}
