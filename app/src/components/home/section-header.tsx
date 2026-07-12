import { Pressable, View } from 'react-native';

import { Text } from '@/components/ui';

export interface SectionHeaderProps {
  title: string;
  actionLabel?: string;
  onPressAction?: () => void;
}

/** Row with a section title and an optional trailing text action. */
export function SectionHeader({ title, actionLabel, onPressAction }: SectionHeaderProps) {
  return (
    <View className="flex-row items-center justify-between px-0.5">
      <Text variant="section">{title}</Text>
      {actionLabel ? (
        <Pressable className="active:opacity-70" onPress={onPressAction} accessibilityRole="button">
          <Text className="font-body-x text-[14px] leading-[18px] text-mint">{actionLabel}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}
