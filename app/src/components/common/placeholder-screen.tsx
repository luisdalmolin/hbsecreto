import type { LucideIcon } from 'lucide-react-native';
import { View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { Text } from '@/components/ui';
import { palette } from '@/theme/tokens';

export interface PlaceholderScreenProps {
  icon: LucideIcon;
  title: string;
  subtitle: string;
}

/** Simple centered placeholder used by not-yet-built tab screens. */
export function PlaceholderScreen({ icon: Icon, title, subtitle }: PlaceholderScreenProps) {
  return (
    <View className="flex-1 bg-bg">
      <SafeAreaView edges={['top']} className="flex-1 items-center justify-center px-8">
        <View className="mb-4 h-16 w-16 items-center justify-center rounded-3xl bg-mint-tint">
          <Icon color={palette.mint} size={30} strokeWidth={2} />
        </View>
        <Text variant="section" className="mb-1 text-center">
          {title}
        </Text>
        <Text variant="body" className="text-center text-ink-muted">
          {subtitle}
        </Text>
      </SafeAreaView>
    </View>
  );
}
