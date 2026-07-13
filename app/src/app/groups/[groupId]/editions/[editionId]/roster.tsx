import { router, useLocalSearchParams } from "expo-router";
import { UserMinus, UserPlus } from "lucide-react-native";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { FlatList, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { getEdition } from "@/api/generated/editions/editions";
import {
  addEditionParticipant,
  listEditionParticipants,
  removeEditionParticipant,
} from "@/api/generated/edition-participants/edition-participants";
import { listGroupMembers } from "@/api/generated/group-members/group-members";
import type { EditionParticipant, GroupMember } from "@/api/generated/models";
import { AppScreen } from "@/components/common/app-screen";
import { ScreenState } from "@/components/common/screen-state";
import { Button, Card, Text } from "@/components/ui";
import { apiErrorMessage, parseRouteId } from "@/features/shared/presentation";
import { useFocusResource } from "@/hooks/use-focus-resource";
import { useMountedRef } from "@/hooks/use-mounted-ref";
import { palette } from "@/theme/tokens";

interface RosterData {
  members: GroupMember[];
  participants: EditionParticipant[];
  status: string;
}

export default function EditionRosterScreen() {
  const { t } = useTranslation();
  const params = useLocalSearchParams<{ groupId: string; editionId: string }>();
  const groupId = parseRouteId(params.groupId);
  const editionId = parseRouteId(params.editionId);
  const [mutationId, setMutationId] = useState<number>();
  const [error, setError] = useState<unknown>();
  const mounted = useMountedRef();
  const load = async (signal: AbortSignal): Promise<RosterData> => {
    if (!groupId || !editionId) throw new Error(t("common.errors.notFound"));
    const [edition, members, participants] = await Promise.all([
      getEdition(groupId, editionId, { signal }),
      listGroupMembers(groupId, { signal }),
      listEditionParticipants(groupId, editionId, { signal }),
    ]);
    return {
      status: edition.status,
      members: members.data.filter((member) => member.status !== "inactive"),
      participants: participants.data,
    };
  };
  const resource = useFocusResource(load);

  async function toggle(
    member: GroupMember,
    participant?: EditionParticipant,
  ): Promise<void> {
    if (!groupId || !editionId || mutationId) return;
    setMutationId(member.id);
    setError(undefined);
    try {
      if (participant)
        await removeEditionParticipant(groupId, editionId, participant.id);
      else
        await addEditionParticipant(groupId, editionId, {
          groupMemberId: member.id,
        });
      if (!mounted.current) return;
      resource.refresh();
    } catch (exception) {
      if (!mounted.current) return;
      setError(exception);
    }
    if (mounted.current) setMutationId(undefined);
  }

  if (!resource.data) {
    return (
      <AppScreen title={t("editions.rosterTitle")} back>
        <ScreenState
          kind={resource.isLoading ? "loading" : "error"}
          title={
            resource.isLoading
              ? t("common.loading")
              : t("common.errors.generic")
          }
          message={
            resource.error ? apiErrorMessage(resource.error, t) : undefined
          }
          retryLabel={t("common.retry")}
          onRetry={resource.refresh}
        />
      </AppScreen>
    );
  }

  const locked =
    resource.data.status !== "draft" && resource.data.status !== "open";
  return (
    <View className="flex-1 bg-bg">
      <SafeAreaView edges={["top"]} className="flex-1">
        <FlatList
          data={resource.data.members}
          keyExtractor={(member) => String(member.id)}
          contentContainerStyle={{
            gap: 12,
            paddingHorizontal: 18,
            paddingBottom: 36,
            flexGrow: 1,
          }}
          refreshing={resource.isRefreshing}
          onRefresh={resource.refresh}
          ListHeaderComponent={
            <View className="mb-1 gap-3 pt-3">
              <RosterHeader />
              {locked ? (
                <Text variant="caption">{t("editions.rosterLocked")}</Text>
              ) : null}
              {error ? (
                <Text className="text-pink-deep" accessibilityRole="alert">
                  {apiErrorMessage(error, t)}
                </Text>
              ) : null}
            </View>
          }
          renderItem={({ item }) => {
            const participant = resource.data?.participants.find(
              (candidate) => candidate.groupMember.id === item.id,
            );
            return (
              <Card className="flex-row items-center gap-3 p-4">
                <View className="flex-1">
                  <Text variant="cardTitle">
                    {item.displayName || t("common.notProvided")}
                  </Text>
                  <Text variant="caption">
                    {t(`groups.status.${item.status}`)}
                  </Text>
                </View>
                <Button
                  size="sm"
                  variant={participant ? "light" : "primary"}
                  label={participant ? t("editions.remove") : t("editions.add")}
                  leftIcon={
                    participant ? (
                      <UserMinus color={palette.mintDeep} size={16} />
                    ) : (
                      <UserPlus color={palette.white} size={16} />
                    )
                  }
                  disabled={locked || Boolean(mutationId)}
                  onPress={() => void toggle(item, participant)}
                />
              </Card>
            );
          }}
          ListEmptyComponent={
            <ScreenState kind="empty" title={t("groups.noMembers")} />
          }
        />
      </SafeAreaView>
    </View>
  );
}

function RosterHeader() {
  const { t } = useTranslation();
  return (
    <View className="flex-row items-center gap-3">
      <Button
        label={t("common.back")}
        variant="light"
        onPress={() => router.back()}
      />
      <View className="flex-1">
        <Text variant="title">{t("editions.rosterTitle")}</Text>
        <Text variant="caption">{t("editions.rosterSubtitle")}</Text>
      </View>
    </View>
  );
}
