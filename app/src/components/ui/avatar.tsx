import { LinearGradient } from 'expo-linear-gradient';

import { gradients } from '@/theme/tokens';

import { Text } from './text';

export interface AvatarProps {
  /** Initials to display, e.g. "MR". */
  initials: string;
  size?: number;
}

/** Gradient rounded-square avatar with initials. */
export function Avatar({ initials, size = 46 }: AvatarProps) {
  return (
    <LinearGradient
      colors={gradients.brand}
      start={{ x: 0.15, y: 0 }}
      end={{ x: 0.85, y: 1 }}
      style={{
        width: size,
        height: size,
        borderRadius: 16,
        alignItems: 'center',
        justifyContent: 'center',
      }}
    >
      <Text className="font-display-x text-[17px] text-white">{initials}</Text>
    </LinearGradient>
  );
}
