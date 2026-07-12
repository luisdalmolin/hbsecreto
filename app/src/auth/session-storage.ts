import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

const accessTokenKey = 'cpx-secreto-access-token';

export const sessionStorage = Platform.select({
  web: {
    getAccessToken: async (): Promise<string | null> => window.localStorage.getItem(accessTokenKey),
    setAccessToken: async (accessToken: string): Promise<void> => {
      window.localStorage.setItem(accessTokenKey, accessToken);
    },
    clearAccessToken: async (): Promise<void> => {
      window.localStorage.removeItem(accessTokenKey);
    },
  },
  default: {
    getAccessToken: (): Promise<string | null> => SecureStore.getItemAsync(accessTokenKey),
    setAccessToken: (accessToken: string): Promise<void> =>
      SecureStore.setItemAsync(accessTokenKey, accessToken),
    clearAccessToken: (): Promise<void> => SecureStore.deleteItemAsync(accessTokenKey),
  },
});
