import { BlurView } from 'expo-blur';
import { LinearGradient } from 'expo-linear-gradient';
import { Eye, Gift } from 'lucide-react-native';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Pressable, StyleSheet, View } from 'react-native';
import Animated, { useAnimatedStyle, withTiming } from 'react-native-reanimated';

import { Button, Card, Chip, Text } from '@/components/ui';
import type { DrawResult } from '@/data/home';
import { gradients, palette, shadows } from '@/theme/tokens';

export interface DrawResultCardProps {
  draw: DrawResult;
  initiallyRevealed?: boolean;
}

/**
 * Hero card showing who the user drew. The recipient and wishlist stay blurred
 * behind a "tap to reveal" overlay until the card is tapped.
 */
export function DrawResultCard({ draw, initiallyRevealed = false }: DrawResultCardProps) {
  const { t } = useTranslation();
  const [revealed, setRevealed] = useState(initiallyRevealed);

  const overlayStyle = useAnimatedStyle(
    () => ({ opacity: withTiming(revealed ? 0 : 1, { duration: 350 }) }),
    [revealed],
  );

  return (
    <Card shadow="hero" className="overflow-hidden rounded-hero border border-hairline p-[22px]">
      <LinearGradient
        colors={gradients.brand}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={styles.topBar}
      />

      <View className="mt-1 flex-row items-center justify-between">
        <Text variant="eyebrow" className="text-mint">
          {t('home.draw.eyebrow')}
        </Text>
        <View className="h-[38px] w-[38px] items-center justify-center rounded-xl bg-mint-tint">
          <Gift color={palette.mint} size={22} strokeWidth={2} />
        </View>
      </View>

      <Pressable
        className="relative mt-2"
        onPress={() => setRevealed(true)}
        disabled={revealed}
        accessibilityRole="button"
        accessibilityLabel={t('home.draw.reveal')}
        accessibilityState={{ expanded: revealed }}
      >
        <View>
          <Text variant="hero">{draw.recipientName}</Text>
          <Text className="mb-4 mt-1 font-body-bold text-[13px] leading-[18px] text-ink-muted">
            {t('home.draw.inGroup', { group: draw.groupName })}
          </Text>
          <Text variant="label" className="mb-2 text-ink-muted">
            {t('home.draw.wishlistLabel')}
          </Text>
          <View className="flex-row flex-wrap gap-[7px]">
            {draw.wishlist.map((item) => (
              <Chip key={item} label={item} />
            ))}
          </View>
        </View>

        <Animated.View style={[StyleSheet.absoluteFill, styles.overlay, overlayStyle]}>
          <BlurView intensity={20} tint="light" style={StyleSheet.absoluteFill} />
          <View
            className="flex-row items-center gap-2 rounded-full bg-mint px-[18px] py-[11px]"
            style={shadows.pill}
          >
            <Eye color={palette.white} size={18} strokeWidth={2} />
            <Text className="font-display-x text-[14px] leading-[18px] text-white">
              {t('home.draw.reveal')}
            </Text>
          </View>
        </Animated.View>
      </Pressable>

      <View className="mt-[18px] flex-row items-center justify-between">
        <Text className="font-body-x text-[14px] leading-[18px] text-ink-soft">
          {t('home.draw.budget', { budget: draw.budget })}
        </Text>
        <Button
          variant="primary"
          size="md"
          label={t('home.draw.seeWishes')}
          rightIcon={<Text className="font-display-x text-[14px] leading-[18px] text-white">→</Text>}
        />
      </View>
    </Card>
  );
}

const styles = StyleSheet.create({
  topBar: { position: 'absolute', top: 0, left: 0, right: 0, height: 6 },
  overlay: {
    margin: -6,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 16,
    overflow: 'hidden',
    pointerEvents: 'none',
  },
});
