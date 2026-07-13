import { router } from "expo-router";
import { ArrowLeft } from "lucide-react-native";
import type { PropsWithChildren, ReactNode } from "react";
import { RefreshControl, ScrollView, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { useTranslation } from "react-i18next";

import { IconButton, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

interface AppScreenProps extends PropsWithChildren {
  title: string;
  subtitle?: string;
  back?: boolean;
  action?: ReactNode;
  scroll?: boolean;
  refreshing?: boolean;
  onRefresh?: () => void;
}

export function AppScreen({
  children,
  title,
  subtitle,
  back = false,
  action,
  scroll = true,
  refreshing,
  onRefresh,
}: AppScreenProps) {
  const { t } = useTranslation();
  const content = (
    <View className="gap-4 px-[18px] pb-10 pt-3">
      <View className="flex-row items-center gap-3">
        {back ? (
          <IconButton
            accessibilityLabel={t("common.back")}
            onPress={() => router.back()}
          >
            <ArrowLeft color={palette.mintDeep} size={21} />
          </IconButton>
        ) : null}
        <View className="flex-1">
          <Text variant="title">{title}</Text>
          {subtitle ? <Text variant="caption">{subtitle}</Text> : null}
        </View>
        {action}
      </View>
      {children}
    </View>
  );

  return (
    <View className="flex-1 bg-bg">
      <SafeAreaView edges={["top"]} className="flex-1">
        {scroll ? (
          <ScrollView
            keyboardShouldPersistTaps="handled"
            contentContainerStyle={{ flexGrow: 1 }}
            showsVerticalScrollIndicator={false}
            refreshControl={
              onRefresh ? (
                <RefreshControl
                  refreshing={Boolean(refreshing)}
                  onRefresh={onRefresh}
                />
              ) : undefined
            }
          >
            {content}
          </ScrollView>
        ) : (
          content
        )}
      </SafeAreaView>
    </View>
  );
}
