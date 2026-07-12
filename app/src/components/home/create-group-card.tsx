import { Plus } from 'lucide-react-native';
import { Pressable, View } from 'react-native';

import { Text } from '@/components/ui';
import { palette } from '@/theme/tokens';

export interface CreateGroupCardProps {
  label: string;
  onPress?: () => void;
}

/** Dashed-outline call-to-action for creating a new group. */
export function CreateGroupCard({ label, onPress }: CreateGroupCardProps) {
  return (
    <Pressable
      className="flex-row items-center justify-center gap-[9px] rounded-card border-2 border-dashed border-outline p-4 active:opacity-70"
      onPress={onPress}
      accessibilityRole="button"
    >
      <View className="h-[26px] w-[26px] items-center justify-center rounded-full bg-mint">
        <Plus color={palette.white} size={18} strokeWidth={2.5} />
      </View>
      <Text className="font-display-x text-[15px] leading-[20px] text-mint-deep">{label}</Text>
    </Pressable>
  );
}
