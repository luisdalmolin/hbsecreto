import { Users } from 'lucide-react-native';
import { useTranslation } from 'react-i18next';

import { PlaceholderScreen } from '@/components/common/placeholder-screen';

export default function GroupsScreen() {
  const { t } = useTranslation();

  return (
    <PlaceholderScreen
      icon={Users}
      title={t('home.tabs.groups')}
      subtitle={t('home.placeholder.groups')}
    />
  );
}
