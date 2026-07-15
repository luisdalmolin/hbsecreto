import { createContext, useContext } from "react";

export type PushRegistrationStatus =
  | "checking"
  | "unsupported"
  | "missingProjectId"
  | "undetermined"
  | "denied"
  | "granted"
  | "error";

export interface NotificationContextValue {
  unreadCount: number;
  pushStatus: PushRegistrationStatus;
  isRegistering: boolean;
  enablePushNotifications(): Promise<void>;
  refreshUnreadCount(): Promise<void>;
  setUnreadCount(count: number): void;
}

export const NotificationContext =
  createContext<NotificationContextValue | null>(null);

export function useNotifications(): NotificationContextValue {
  const context = useContext(NotificationContext);

  if (!context) {
    throw new Error(
      "useNotifications must be used inside NotificationProvider.",
    );
  }

  return context;
}
