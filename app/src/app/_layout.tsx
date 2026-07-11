import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';

import '@/i18n';

export default function RootLayout() {
  return (
    <>
      <Stack screenOptions={{ headerShown: false }} />
      <StatusBar style="auto" />
    </>
  );
}
