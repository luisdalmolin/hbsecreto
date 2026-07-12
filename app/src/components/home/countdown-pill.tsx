import { View } from 'react-native';

import { Text } from '@/components/ui';
import { shadows } from '@/theme/tokens';

export interface CountdownPillProps {
  label: string;
  emoji?: string;
}

/** Small floating pill counting down to the event. */
export function CountdownPill({ label, emoji = '🎄' }: CountdownPillProps) {
  return (
    <View
      className="flex-row items-center gap-1.5 self-start rounded-full bg-card px-[14px] py-2"
      style={shadows.pill}
    >
      <Text className="text-[13px] leading-[16px]">{emoji}</Text>
      <Text className="font-body-x text-[13px] leading-[16px] text-mint">{label}</Text>
    </View>
  );
}
