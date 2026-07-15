import Constants from "expo-constants";
import * as Device from "expo-device";
import * as Notifications from "expo-notifications";
import { Platform } from "react-native";

import { registerPushDevice } from "@/api/generated/push-devices/push-devices";

import type { PushRegistrationStatus } from "./notification-context";

const GENERAL_CHANNEL_ID = "general";

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldPlaySound: true,
    shouldSetBadge: false,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

export interface PushResponseData {
  notificationId?: unknown;
  url?: unknown;
}

interface PushSubscriptionCallbacks {
  onReceived(): void;
  onResponse(data: PushResponseData): void;
  onTokenChanged(): void;
}

export async function registerForPushNotifications(
  requestPermission: boolean,
  channelName: string,
): Promise<PushRegistrationStatus> {
  if (
    (Platform.OS !== "ios" && Platform.OS !== "android") ||
    !Device.isDevice ||
    Constants.expoGoConfig !== null
  ) {
    return "unsupported";
  }

  const projectId = getProjectId();
  if (!projectId) return "missingProjectId";

  if (Platform.OS === "android") {
    await Notifications.setNotificationChannelAsync(GENERAL_CHANNEL_ID, {
      name: channelName,
      importance: Notifications.AndroidImportance.HIGH,
      vibrationPattern: [0, 250, 250, 250],
    });
  }

  let permission = await Notifications.getPermissionsAsync();
  if (
    permission.status !== Notifications.PermissionStatus.GRANTED &&
    requestPermission
  ) {
    permission = await Notifications.requestPermissionsAsync();
  }

  if (permission.status === Notifications.PermissionStatus.DENIED) {
    return "denied";
  }

  if (permission.status !== Notifications.PermissionStatus.GRANTED) {
    return "undetermined";
  }

  const expoPushToken = await Notifications.getExpoPushTokenAsync({
    projectId,
  });
  await registerPushDevice({
    expoPushToken: expoPushToken.data,
    platform: Platform.OS,
    deviceName: Device.modelName,
  });

  return "granted";
}

export function subscribeToPushNotifications({
  onReceived,
  onResponse,
  onTokenChanged,
}: PushSubscriptionCallbacks): () => void {
  const receivedSubscription =
    Notifications.addNotificationReceivedListener(onReceived);
  const responseSubscription =
    Notifications.addNotificationResponseReceivedListener((response) => {
      onResponse(response.notification.request.content.data ?? {});
    });
  const tokenSubscription = Notifications.addPushTokenListener(onTokenChanged);
  const lastResponse = Notifications.getLastNotificationResponse();

  if (lastResponse) {
    onResponse(lastResponse.notification.request.content.data ?? {});
    Notifications.clearLastNotificationResponse();
  }

  return () => {
    receivedSubscription.remove();
    responseSubscription.remove();
    tokenSubscription.remove();
  };
}

function getProjectId(): string | null {
  const environmentProjectId = process.env.EXPO_PUBLIC_EAS_PROJECT_ID;
  if (environmentProjectId) return environmentProjectId;

  const easProjectId = Constants.easConfig?.projectId;
  if (easProjectId) return easProjectId;

  const eas = Constants.expoConfig?.extra?.eas;
  if (typeof eas === "object" && eas !== null && "projectId" in eas) {
    const projectId = eas.projectId;
    return typeof projectId === "string" ? projectId : null;
  }

  return null;
}
