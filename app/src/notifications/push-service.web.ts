import type { PushRegistrationStatus } from "./notification-context";

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
  _requestPermission: boolean,
  _channelName: string,
): Promise<PushRegistrationStatus> {
  return "unsupported";
}

export function subscribeToPushNotifications(
  _callbacks: PushSubscriptionCallbacks,
): () => void {
  return () => undefined;
}
