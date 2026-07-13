import * as Linking from "expo-linking";
import { router, useLocalSearchParams } from "expo-router";
import { useRef, useState } from "react";
import { useTranslation } from "react-i18next";
import { ActivityIndicator, Share, View } from "react-native";

import {
  createGroupMember,
  issueGroupInvitation,
} from "@/api/generated/group-members/group-members";
import { getGroup } from "@/api/generated/groups/groups";
import type {
  Group,
  GroupMember,
  IssuedInvitation,
} from "@/api/generated/models";
import { normalizeApiError } from "@/api/errors";
import { AppScreen } from "@/components/common/app-screen";
import { FormField } from "@/components/common/form-field";
import { Button, Card, Text } from "@/components/ui";
import {
  apiErrorMessage,
  formatDate,
  parseRouteId,
} from "@/features/shared/presentation";
import { palette } from "@/theme/tokens";

export default function NewMemberScreen() {
  const { t } = useTranslation();
  const { groupId: rawGroupId } = useLocalSearchParams<{ groupId: string }>();
  const groupId = parseRouteId(rawGroupId);
  const [displayName, setDisplayName] = useState("");
  const [email, setEmail] = useState("");
  const [role, setRole] = useState<"member" | "admin">("member");
  const [createdMember, setCreatedMember] = useState<GroupMember>();
  const group = useRef<Group | undefined>(undefined);
  const [invitation, setInvitation] = useState<IssuedInvitation>();
  const [error, setError] = useState<unknown>();
  const [submitting, setSubmitting] = useState(false);
  const fields = normalizeApiError(error).fields;

  async function create(): Promise<void> {
    if (!groupId) return;
    setError(undefined);
    setSubmitting(true);
    try {
      const [member, currentGroup] = await Promise.all([
        createGroupMember(groupId, {
          displayName: displayName.trim(),
          email: email.trim() || null,
          role,
        }),
        getGroup(groupId),
      ]);
      setCreatedMember(member);
      group.current = currentGroup;
      setInvitation(await issueGroupInvitation(groupId, member.id));
    } catch (exception) {
      setError(exception);
    }
    setSubmitting(false);
  }

  async function rotate(): Promise<void> {
    if (!groupId || !createdMember) return;
    setError(undefined);
    setSubmitting(true);
    try {
      setInvitation(await issueGroupInvitation(groupId, createdMember.id));
    } catch (exception) {
      setError(exception);
    }
    setSubmitting(false);
  }

  async function share(): Promise<void> {
    if (!invitation || !group.current) return;
    try {
      const link = Linking.createURL(`/invites/${invitation.inviteToken}`);
      await Share.share({
        message: t("members.shareMessage", {
          name:
            invitation.member.displayName ||
            createdMember?.displayName ||
            t("common.notProvided"),
          group: group.current.name,
          link,
        }),
      });
    } catch (exception) {
      setError(exception);
    }
  }

  if (createdMember) {
    const link = invitation
      ? Linking.createURL(`/invites/${invitation.inviteToken}`)
      : undefined;
    return (
      <AppScreen
        title={t("members.inviteFor", { name: createdMember.displayName })}
        subtitle={t("members.inviteHint")}
        back
      >
        <Card className="gap-4 p-5">
          <Text variant="cardTitle">
            {t("members.inviteFor", { name: createdMember.displayName })}
          </Text>
          {link ? (
            <Text selectable variant="caption">
              {link}
            </Text>
          ) : null}
          {invitation ? (
            <Text variant="caption">
              {t("members.expires", { date: formatDate(invitation.expiresAt) })}
            </Text>
          ) : null}
          {error ? (
            <Text className="text-pink-deep" accessibilityRole="alert">
              {apiErrorMessage(error, t)}
            </Text>
          ) : null}
          <Button
            label={t("members.share")}
            onPress={() => void share()}
            disabled={!invitation}
          />
          <Button
            label={t("members.rotateInvite")}
            variant="light"
            onPress={() => void rotate()}
            disabled={submitting}
          />
          <Button
            label={t("common.confirm")}
            variant="light"
            onPress={() => router.back()}
          />
        </Card>
      </AppScreen>
    );
  }

  return (
    <AppScreen
      title={t("members.newTitle")}
      subtitle={t("members.newSubtitle")}
      back
    >
      <Card className="gap-4 p-5">
        <FormField
          label={t("members.displayName")}
          value={displayName}
          onChangeText={setDisplayName}
          autoCapitalize="words"
          error={fields?.displayName}
        />
        <FormField
          label={t("members.email")}
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          error={fields?.email}
        />
        <View className="gap-2">
          <Text variant="bodyBold">{t("members.role")}</Text>
          <View className="flex-row gap-2">
            <Button
              label={t("members.roleMember")}
              variant={role === "member" ? "primary" : "light"}
              accessibilityState={{ selected: role === "member" }}
              onPress={() => setRole("member")}
            />
            <Button
              label={t("members.roleAdmin")}
              variant={role === "admin" ? "primary" : "light"}
              accessibilityState={{ selected: role === "admin" }}
              onPress={() => setRole("admin")}
            />
          </View>
        </View>
        {error ? (
          <Text className="text-pink-deep" accessibilityRole="alert">
            {apiErrorMessage(error, t)}
          </Text>
        ) : null}
        <Button
          label={t("members.create")}
          disabled={submitting || !displayName.trim() || !groupId}
          onPress={() => void create()}
          rightIcon={
            submitting ? <ActivityIndicator color={palette.white} /> : undefined
          }
        />
      </Card>
    </AppScreen>
  );
}
