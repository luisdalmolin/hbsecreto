import { router, useLocalSearchParams } from "expo-router";
import { MailOpen } from "lucide-react-native";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { ActivityIndicator, View } from "react-native";

import { useAuthSession } from "@/auth/auth-session";
import {
  claimInvitation,
  previewInvitation,
} from "@/api/generated/invitations/invitations";
import { AppScreen } from "@/components/common/app-screen";
import { ScreenState } from "@/components/common/screen-state";
import { Button, Card, Text } from "@/components/ui";
import { apiErrorMessage, formatDate } from "@/features/shared/presentation";
import { useFocusResource } from "@/hooks/use-focus-resource";
import { palette } from "@/theme/tokens";

export default function InvitationScreen() {
  const { t } = useTranslation();
  const { user } = useAuthSession();
  const { token: rawToken } = useLocalSearchParams<{ token: string }>();
  const token = Array.isArray(rawToken) ? rawToken[0] : rawToken;
  const [claiming, setClaiming] = useState(false);
  const [claimError, setClaimError] = useState<unknown>();
  const [claimedGroupId, setClaimedGroupId] = useState<number>();
  const load = (signal: AbortSignal) =>
    token
      ? previewInvitation(token, { signal })
      : Promise.reject(new Error(t("invites.invalid")));
  const resource = useFocusResource(load);

  async function claim(): Promise<void> {
    if (!token || claiming) return;
    setClaimError(undefined);
    setClaiming(true);
    try {
      const member = await claimInvitation(token);
      setClaimedGroupId(member.groupId);
    } catch (exception) {
      setClaimError(exception);
      resource.refresh();
    }
    setClaiming(false);
  }

  return (
    <AppScreen title={t("invites.title")}>
      {resource.isLoading && !resource.data ? (
        <ScreenState kind="loading" title={t("invites.loading")} />
      ) : null}
      {!resource.data && resource.error ? (
        <ScreenState
          kind="error"
          title={t("invites.invalid")}
          message={apiErrorMessage(resource.error, t)}
          retryLabel={t("common.retry")}
          onRetry={resource.refresh}
        />
      ) : null}
      {resource.data ? (
        <Card className="items-center gap-4 p-6" shadow="hero">
          <View className="h-16 w-16 items-center justify-center rounded-3xl bg-pink-tint">
            <MailOpen color={palette.pink} size={32} />
          </View>
          <Text variant="section" className="text-center">
            {resource.data.displayName
              ? t("invites.greeting", { name: resource.data.displayName })
              : t("invites.genericGreeting")}
          </Text>
          <Text variant="hero" className="text-center">
            {resource.data.groupName}
          </Text>
          <Text variant="caption" className="text-center">
            {t("invites.expires", {
              date: formatDate(resource.data.expiresAt),
            })}
          </Text>
          {claimedGroupId ? (
            <>
              <Text
                className="text-center text-mint-deep"
                accessibilityLiveRegion="polite"
              >
                {t("invites.claimed")}
              </Text>
              <Button
                label={t("invites.join", { group: resource.data.groupName })}
                onPress={() =>
                  router.replace({
                    pathname: "/groups/[groupId]",
                    params: { groupId: String(claimedGroupId) },
                  })
                }
              />
            </>
          ) : user ? (
            <Button
              label={claiming ? t("invites.claiming") : t("invites.claim")}
              disabled={claiming}
              onPress={() => void claim()}
              rightIcon={
                claiming ? (
                  <ActivityIndicator color={palette.white} />
                ) : undefined
              }
            />
          ) : (
            <Button
              label={t("invites.signIn")}
              onPress={() => router.push("/sign-in")}
            />
          )}
          {claimError ? (
            <Text
              className="text-center text-pink-deep"
              accessibilityRole="alert"
            >
              {apiErrorMessage(claimError, t)}
            </Text>
          ) : null}
        </Card>
      ) : null}
    </AppScreen>
  );
}
