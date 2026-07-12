import { User } from 'lucide-react-native';
import { useTranslation } from 'react-i18next';

import { PlaceholderScreen } from '@/components/common/placeholder-screen';

export default function ProfileScreen() {
  const { t } = useTranslation();

  return (
    <PlaceholderScreen
      icon={User}
      title={t('home.tabs.profile')}
      subtitle={t('home.placeholder.profile')}
    />
  );
}
