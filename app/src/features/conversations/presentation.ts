import type { TFunction } from "i18next";

import type { Conversation } from "@/api/generated/models";

export function conversationTitle(
  conversation: Conversation,
  t: TFunction,
): string {
  if (conversation.type === "edition") {
    return t("chat.groupConversation");
  }

  if (!conversation.counterpart) {
    return t("chat.unknownParticipant");
  }

  if (conversation.counterpart.anonymous) {
    return t("chat.secretSanta");
  }

  return conversation.counterpart.displayName ?? t("chat.unknownParticipant");
}

export function formatMessageTime(value: string, locale: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.valueOf())) return "";

  return new Intl.DateTimeFormat(locale, {
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

export function formatConversationActivity(
  value: string | null,
  locale: string,
): string | undefined {
  if (!value) return undefined;
  const date = new Date(value);
  if (Number.isNaN(date.valueOf())) return undefined;

  return new Intl.DateTimeFormat(locale, {
    day: "2-digit",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}
