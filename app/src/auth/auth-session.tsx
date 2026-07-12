import * as Device from 'expo-device';
import { createContext, type PropsWithChildren, useContext, useEffect, useMemo, useState } from 'react';
import { Platform } from 'react-native';

import { getCurrentUser, logout } from '@/api/generated/authentication/authentication';
import type { User } from '@/api/generated/models';
import { configureApiClient } from '@/api/http';

import {
  passwordSignInProvider,
  passwordSignUpProvider,
  type AuthenticationProvider,
} from './authentication-provider';
import { sessionStorage } from './session-storage';

type Credentials = { email: string; password: string; name?: string };

interface AuthSessionContextValue {
  isLoading: boolean;
  user: User | null;
  authenticate<TInput>(provider: AuthenticationProvider<TInput>, input: TInput): Promise<void>;
  signIn(credentials: Credentials): Promise<void>;
  signUp(credentials: Required<Credentials>): Promise<void>;
  signOut(): Promise<void>;
}

const AuthSessionContext = createContext<AuthSessionContextValue | null>(null);

export function AuthSessionProvider({ children }: PropsWithChildren) {
  const [isLoading, setIsLoading] = useState(true);
  const [user, setUser] = useState<User | null>(null);

  useEffect(() => {
    void restoreSession();
  }, []);

  async function restoreSession(): Promise<void> {
    const accessToken = await sessionStorage.getAccessToken();

    if (!accessToken) {
      setIsLoading(false);
      return;
    }

    configureApiClient(accessToken);

    try {
      setUser(await getCurrentUser());
    } catch {
      configureApiClient();
      await sessionStorage.clearAccessToken();
    } finally {
      setIsLoading(false);
    }
  }

  async function completeAuthentication(accessToken: string | undefined, authenticatedUser: User | undefined): Promise<void> {
    if (!accessToken || !authenticatedUser) {
      throw new Error('The authentication response was incomplete.');
    }

    configureApiClient(accessToken);
    await sessionStorage.setAccessToken(accessToken);
    setUser(authenticatedUser);
  }

  const value = useMemo<AuthSessionContextValue>(
    () => ({
      isLoading,
      user,
      authenticate: async <TInput,>(provider: AuthenticationProvider<TInput>, input: TInput) => {
        const authentication = await provider.authenticate(input);
        await completeAuthentication(authentication.accessToken, authentication.user);
      },
      signIn: async ({ email, password }) => {
        const authentication = await passwordSignInProvider.authenticate({
          email,
          password,
          deviceName: getDeviceName(),
        });
        await completeAuthentication(authentication.accessToken, authentication.user);
      },
      signUp: async ({ name, email, password }) => {
        const authentication = await passwordSignUpProvider.authenticate({
          name,
          email,
          password,
          deviceName: getDeviceName(),
        });
        await completeAuthentication(authentication.accessToken, authentication.user);
      },
      signOut: async () => {
        try {
          await logout();
        } finally {
          configureApiClient();
          await sessionStorage.clearAccessToken();
          setUser(null);
        }
      },
    }),
    [isLoading, user],
  );

  return <AuthSessionContext.Provider value={value}>{children}</AuthSessionContext.Provider>;
}

export function useAuthSession(): AuthSessionContextValue {
  const context = useContext(AuthSessionContext);

  if (!context) {
    throw new Error('useAuthSession must be used inside AuthSessionProvider.');
  }

  return context;
}

function getDeviceName(): string {
  return Device.modelName ?? `CPX Secreto (${Platform.OS})`;
}
