import { useTranslation } from 'react-i18next';
import { ScrollView, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import {
  CountdownPill,
  CreateGroupCard,
  DrawResultCard,
  GroupCard,
  HomeHeader,
  SectionHeader,
} from '@/components/home';
import { currentUser, daysUntilChristmas, drawResult, groups } from '@/data/home';
import { getGreetingKey } from '@/lib/greeting';

export default function HomeScreen() {
  const { t } = useTranslation();
  const greeting = t(`home.greeting.${getGreetingKey()}`);

  return (
    <View className="flex-1 bg-bg">
      <SafeAreaView edges={['top']} className="flex-1">
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={{ gap: 18, paddingHorizontal: 18, paddingTop: 8, paddingBottom: 24 }}
        >
          <HomeHeader
            greeting={greeting}
            name={currentUser.name}
            initials={currentUser.initials}
            notificationsLabel={t('home.notifications')}
          />
          <CountdownPill label={t('home.countdown', { value: daysUntilChristmas })} />
          <DrawResultCard draw={drawResult} />
          <SectionHeader title={t('home.groups.title')} actionLabel={t('home.groups.seeAll')} />
          {groups.map((group) => (
            <GroupCard key={group.id} group={group} />
          ))}
          <CreateGroupCard label={t('home.groups.create')} />
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}
