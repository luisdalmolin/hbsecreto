import { router } from "expo-router";
import { Globe, LogOut, Mail, Pencil, User } from "lucide-react-native";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { ActivityIndicator, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { useAuthSession } from "@/auth/auth-session";
import { Avatar, Button, Card, Text } from "@/components/ui";
import { initials } from "@/features/shared/presentation";
import { palette } from "@/theme/tokens";

export default function ProfileScreen() {
  const { t } = useTranslation();
  const { user, signOut } = useAuthSession();
  const [isSigningOut, setIsSigningOut] = useState(false);

  async function logout(): Promise<void> {
    setIsSigningOut(true);
    await signOut();
  }

  if (!user) return null;

  return (
    <View className="flex-1 bg-bg">
      <SafeAreaView edges={["top"]} className="flex-1 px-[18px] pt-3">
        <View className="gap-4">
          <Text variant="title">{t("profile.title")}</Text>
          <Card className="items-center gap-3 p-6" shadow="hero">
            <Avatar initials={initials(user.name)} size={70} />
            <Text variant="section" className="text-center">
              {user.name}
            </Text>
            <Text variant="caption">{t("profile.account")}</Text>
          </Card>
          <Card className="gap-4 p-5">
            <ProfileRow
              icon={User}
              label={t("profile.name")}
              value={user.name}
            />
            <ProfileRow
              icon={Mail}
              label={t("profile.email")}
              value={user.email}
            />
            <ProfileRow
              icon={Globe}
              label={t("profile.locale")}
              value={t("profile.localeValue")}
            />
          </Card>
          <Button
            label={t("profile.edit")}
            leftIcon={<Pencil color={palette.white} size={18} />}
            onPress={() => router.push("/profile/edit")}
          />
          <Button
            label={isSigningOut ? t("profile.loggingOut") : t("profile.logout")}
            variant="light"
            leftIcon={
              isSigningOut ? (
                <ActivityIndicator color={palette.mintDeep} />
              ) : (
                <LogOut color={palette.mintDeep} size={18} />
              )
            }
            disabled={isSigningOut}
            onPress={() => void logout()}
          />
        </View>
      </SafeAreaView>
    </View>
  );
}

function ProfileRow({
  icon: Icon,
  label,
  value,
}: {
  icon: typeof User;
  label: string;
  value: string;
}) {
  return (
    <View className="flex-row items-center gap-3">
      <View className="h-10 w-10 items-center justify-center rounded-xl bg-mint-tint">
        <Icon color={palette.mint} size={19} />
      </View>
      <View className="flex-1">
        <Text variant="caption">{label}</Text>
        <Text variant="bodyBold">{value}</Text>
      </View>
    </View>
  );
}
