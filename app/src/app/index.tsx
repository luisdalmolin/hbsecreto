import { useTranslation } from 'react-i18next';
import { StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';

export default function HomeScreen() {
  const { t } = useTranslation();

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea}>
        <ThemedText type="title" style={styles.title}>{t('home.title')}</ThemedText>
        <ThemedText themeColor="textSecondary" style={styles.description}>
          {t('home.description')}
        </ThemedText>
        <ThemedText type="small" themeColor="textSecondary" style={styles.status}>
          {t('home.apiStatus')}
        </ThemedText>
      </SafeAreaView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
    justifyContent: 'center',
    padding: Spacing.four,
    gap: Spacing.three,
  },
  title: {
    fontSize: 40,
    lineHeight: 48,
  },
  description: {
    fontSize: 18,
    lineHeight: 28,
  },
  status: {
    marginTop: Spacing.three,
  },
});
