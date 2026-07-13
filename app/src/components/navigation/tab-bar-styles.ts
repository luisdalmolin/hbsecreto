import { palette, shadows } from "@/theme/tokens";

export const tabBarPillStyle = [
  shadows.floating,
  {
    flexDirection: "row" as const,
    alignItems: "center" as const,
    justifyContent: "space-around" as const,
    backgroundColor: palette.card,
    borderRadius: 22,
    paddingHorizontal: 8,
    paddingTop: 12,
    paddingBottom: 14,
  },
];
