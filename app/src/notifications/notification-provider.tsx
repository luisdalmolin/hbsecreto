import { type PropsWithChildren, useEffect, useState } from "react";
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
  setApplicationBadge,
  subscribeToPushNotifications,
} from "./push-service";

export function NotificationProvider({ children }: PropsWithChildren) {
  const { user } = useAuthSession();
  const { t } = useTranslation();
  const [unreadCount, setUnreadCountState] = useState(0);
  const [pushStatus, setPushStatus] =
    useState<PushRegistrationStatus>("checking");
  const [isRegistering, setIsRegistering] = useState(false);

  const setUnreadCount = (count: number) => {
    const normalizedCount = Math.max(0, count);
    setUnreadCountState(normalizedCount);
    void setApplicationBadge(normalizedCount).catch(() => undefined);
  };

  const refreshUnreadCount = async () => {
    if (!user) {
      setUnreadCount(0);
      return;
    }

    const inbox = await listNotifications();
    setUnreadCount(inbox.unreadCount);
  };

  const registerForPush = (requestPermission: boolean): Promise<void> => {
    if (!user) return Promise.resolve();
    setIsRegistering(true);
    return registerForPushNotifications(
      requestPermission,
      t("notifications.push.channelName"),
    )
      .then(setPushStatus)
      .catch(() => setPushStatus("error"))
      .finally(() => setIsRegistering(false));
  };

  const enablePushNotifications = () => registerForPush(true);

  useEffect(() => {
    if (!user) {
      void setApplicationBadge(0).catch(() => undefined);
      return;
    }

    let active = true;
    const updateCount = (count: number) => {
      if (!active) return;
      const normalizedCount = Math.max(0, count);
      setUnreadCountState(normalizedCount);
      void setApplicationBadge(normalizedCount).catch(() => undefined);
    };
    const refreshCount = () =>
      listNotifications()
        .then((inbox) => updateCount(inbox.unreadCount))
        .catch(() => undefined);

    void listNotifications()
      .then((inbox) => updateCount(inbox.unreadCount))
      .catch(() => undefined);
    void registerForPushNotifications(
      false,
      t("notifications.push.channelName"),
    )
      .then((status) => {
        if (active) setPushStatus(status);
      })
      .catch(() => {
        if (active) setPushStatus("error");
      });

    const unsubscribeFromPush = subscribeToPushNotifications({
      onReceived: () => {
        void refreshCount();
      },
      onResponse: (data) => {
        void handleNotificationResponse(data, refreshCount);
      },
      onTokenChanged: () => {
        void registerForPushNotifications(
          false,
          t("notifications.push.channelName"),
        )
          .then((status) => {
            if (active) setPushStatus(status);
          })
          .catch(() => {
            if (active) setPushStatus("error");
          });
      },
    });
    const appStateSubscription = AppState.addEventListener(
      "change",
      (state) => {
        if (state === "active") {
          void refreshCount();
        }
      },
    );

    return () => {
      active = false;
      unsubscribeFromPush();
      appStateSubscription.remove();
    };
  }, [t, user]);

  const value: NotificationContextValue = {
    unreadCount: user ? unreadCount : 0,
    pushStatus: user ? pushStatus : "checking",
    isRegistering,
    enablePushNotifications,
    refreshUnreadCount,
    setUnreadCount,
  };

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
