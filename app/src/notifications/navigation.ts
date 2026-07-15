import type { Href } from "expo-router";
import { router } from "expo-router";

export function openNotificationUrl(url: unknown): boolean {
  if (typeof url !== "string" || !url.startsWith("/") || url.startsWith("//")) {
    return false;
  }

  router.push(url as Href);
  return true;
}
