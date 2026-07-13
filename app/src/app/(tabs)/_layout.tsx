import { TabList, Tabs, TabSlot, TabTrigger } from "expo-router/ui";
import { House, User, Users } from "lucide-react-native";
import { useTranslation } from "react-i18next";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { TabButton } from "@/components/navigation/tab-bar";
import { tabBarPillStyle } from "@/components/navigation/tab-bar-styles";
import { palette } from "@/theme/tokens";

export default function TabsLayout() {
  const { t } = useTranslation();
  const insets = useSafeAreaInsets();

  return (
    <Tabs style={{ flex: 1, backgroundColor: palette.bg }}>
      <TabSlot />
      <TabList
        style={[
          ...tabBarPillStyle,
          {
            marginHorizontal: 18,
            marginTop: 8,
            marginBottom: Math.max(insets.bottom, 12),
          },
        ]}
      >
        <TabTrigger name="index" href="/" asChild>
          <TabButton icon={House} label={t("home.tabs.home")} fillWhenActive />
        </TabTrigger>
        <TabTrigger name="groups" href="/groups" asChild>
          <TabButton icon={Users} label={t("home.tabs.groups")} />
        </TabTrigger>
        <TabTrigger name="profile" href="/profile" asChild>
          <TabButton icon={User} label={t("home.tabs.profile")} />
        </TabTrigger>
      </TabList>
    </Tabs>
  );
}
