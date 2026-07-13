import * as Device from "expo-device";
import {
  createContext,
  type PropsWithChildren,
  useContext,
  useEffect,
  useState,
} from "react";
import { Platform } from "react-native";

import {
  getCurrentUser,
  logout,
  updateCurrentUser,
} from "@/api/generated/authentication/authentication";
import type { UpdateUserRequest, User } from "@/api/generated/models";
import { configureApiClient, configureUnauthorizedHandler } from "@/api/http";

import {
  passwordSignInProvider,
  passwordSignUpProvider,
  type AuthenticationProvider,
} from "./authentication-provider";
import { sessionStorage } from "./session-storage";

type Credentials = { email: string; password: string; name?: string };

interface AuthSessionContextValue {
  isLoading: boolean;
  user: User | null;
  authenticate<TInput>(
    provider: AuthenticationProvider<TInput>,
    input: TInput,
  ): Promise<void>;
  signIn(credentials: Credentials): Promise<void>;
  signUp(credentials: Required<Credentials>): Promise<void>;
  refreshUser(): Promise<void>;
  updateProfile(input: UpdateUserRequest): Promise<void>;
  signOut(): Promise<void>;
}

const AuthSessionContext = createContext<AuthSessionContextValue | null>(null);

export function AuthSessionProvider({ children }: PropsWithChildren) {
  const [isLoading, setIsLoading] = useState(true);
  const [user, setUser] = useState<User | null>(null);

  useEffect(() => {
    let active = true;
    configureUnauthorizedHandler(async () => {
      configureApiClient();
      await sessionStorage.clearAccessToken().catch(() => undefined);
      if (active) setUser(null);
    });
    void sessionStorage
      .getAccessToken()
      .then(async (accessToken) => {
        if (!accessToken) return;
        configureApiClient(accessToken);
        await getCurrentUser()
          .then((authenticatedUser) => {
            if (active) setUser(authenticatedUser);
          })
          .catch(async () => {
            configureApiClient();
            await sessionStorage.clearAccessToken();
          });
      })
      .catch(() => {
        configureApiClient();
      })
      .then(() => {
        if (active) setIsLoading(false);
      });

    return () => {
      active = false;
      configureUnauthorizedHandler();
    };
  }, []);

  async function completeAuthentication(
    accessToken: string | undefined,
    authenticatedUser: User | undefined,
  ): Promise<void> {
    if (!accessToken || !authenticatedUser) {
      throw new Error();
    }

    configureApiClient(accessToken);
    await sessionStorage.setAccessToken(accessToken);
    setUser(authenticatedUser);
  }

  const value: AuthSessionContextValue = {
    isLoading,
    user,
    authenticate: async <TInput,>(
      provider: AuthenticationProvider<TInput>,
      input: TInput,
    ) => {
      const authentication = await provider.authenticate(input);
      await completeAuthentication(
        authentication.accessToken,
        authentication.user,
      );
    },
    signIn: async ({ email, password }) => {
      const authentication = await passwordSignInProvider.authenticate({
        email,
        password,
        deviceName: getDeviceName(),
      });
      await completeAuthentication(
        authentication.accessToken,
        authentication.user,
      );
    },
    signUp: async ({ name, email, password }) => {
      const authentication = await passwordSignUpProvider.authenticate({
        name,
        email,
        password,
        deviceName: getDeviceName(),
      });
      await completeAuthentication(
        authentication.accessToken,
        authentication.user,
      );
    },
    refreshUser: async () => {
      setUser(await getCurrentUser());
    },
    updateProfile: async (input) => {
      setUser(await updateCurrentUser(input));
    },
    signOut: async () => {
      await logout().catch(() => undefined);
      configureApiClient();
      await sessionStorage.clearAccessToken().catch(() => undefined);
      setUser(null);
    },
  };

  return (
    <AuthSessionContext.Provider value={value}>
      {children}
    </AuthSessionContext.Provider>
  );
}

export function useAuthSession(): AuthSessionContextValue {
  const context = useContext(AuthSessionContext);

  if (!context) {
    throw new Error("useAuthSession must be used inside AuthSessionProvider.");
  }

  return context;
}

function getDeviceName(): string {
  return Device.modelName ?? `CPX Secreto (${Platform.OS})`;
}
