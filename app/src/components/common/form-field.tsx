import type { ComponentProps } from "react";
import { TextInput, View } from "react-native";

import { Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

interface FormFieldProps extends ComponentProps<typeof TextInput> {
  label: string;
  error?: string;
}

export function FormField({ label, error, ...props }: FormFieldProps) {
  return (
    <View className="gap-1.5">
      <Text variant="bodyBold">{label}</Text>
      <TextInput
        className="min-h-12 rounded-tile border border-outline bg-card px-4 py-3 font-body-reg text-[16px] text-ink"
        placeholderTextColor={palette.inkMuted}
        accessibilityLabel={label}
        {...props}
      />
      {error ? (
        <Text
          variant="caption"
          className="text-pink-deep"
          accessibilityRole="alert"
        >
          {error}
        </Text>
      ) : null}
    </View>
  );
}
