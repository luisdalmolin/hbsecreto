import type { NotificationPreferences } from "@/api/generated/models";
import { Button, Card, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";
import { useTranslation } from "react-i18next";
import { Switch, View } from "react-native";

export type NotificationPreferenceKey = keyof NotificationPreferences;

interface NotificationPreferencesCardProps {
  preferences: NotificationPreferences | undefined;
  isLoading: boolean;
  isSaving: boolean;
  hasError: boolean;
  onRetry(): void;
  onChange(key: NotificationPreferenceKey, enabled: boolean): void;
}

export function NotificationPreferencesCard({
  preferences,
  isLoading,
  isSaving,
  hasError,
  onRetry,
  onChange,
}: NotificationPreferencesCardProps) {
  const { t } = useTranslation();

  return (
    <Card className="gap-3 border border-hairline p-4" shadow="none">
      <View className="gap-1">
        <Text variant="cardTitle">{t("notifications.preferences.title")}</Text>
        <Text variant="caption">
          {t("notifications.preferences.description")}
        </Text>
      </View>
      {isLoading && !preferences ? (
        <Text variant="caption">{t("notifications.preferences.loading")}</Text>
      ) : null}
      {hasError ? (
        <View className="gap-2">
          <Text variant="caption" className="text-pink">
            {t("notifications.preferences.error")}
          </Text>
          {!preferences ? (
            <Button
              label={t("common.retry")}
              size="sm"
              variant="light"
              onPress={onRetry}
            />
          ) : null}
        </View>
      ) : null}
      {preferences ? (
        <View className="gap-1">
          <PreferenceRow
            label={t("notifications.preferences.conversationMessages")}
            value={preferences.conversationMessages}
            disabled={isSaving}
            onChange={(enabled) => onChange("conversationMessages", enabled)}
          />
          <View className="h-px bg-hairline" />
          <PreferenceRow
            label={t("notifications.preferences.editionUpdates")}
            value={preferences.editionUpdates}
            disabled={isSaving}
            onChange={(enabled) => onChange("editionUpdates", enabled)}
          />
        </View>
      ) : null}
    </Card>
  );
}

interface PreferenceRowProps {
  label: string;
  value: boolean;
  disabled: boolean;
  onChange(enabled: boolean): void;
}

function PreferenceRow({
  label,
  value,
  disabled,
  onChange,
}: PreferenceRowProps) {
  return (
    <View className="min-h-12 flex-row items-center justify-between gap-4 py-2">
      <Text variant="bodyBold" className="flex-1">
        {label}
      </Text>
      <Switch
        accessibilityLabel={label}
        value={value}
        disabled={disabled}
        onValueChange={onChange}
        trackColor={{ false: palette.hairline, true: palette.mintTint }}
        thumbColor={value ? palette.mint : palette.inkMuted}
      />
    </View>
  );
}
