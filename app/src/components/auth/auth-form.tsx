import { Link } from "expo-router";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { ActivityIndicator, View } from "react-native";

import { useAuthSession } from "@/auth/auth-session";
import { normalizeApiError } from "@/api/errors";
import { FormField } from "@/components/common/form-field";
import { Button, Card, Text } from "@/components/ui";
import { palette } from "@/theme/tokens";

type Mode = "signIn" | "signUp";

export function AuthForm({ mode }: { mode: Mode }) {
  const { t } = useTranslation();
  const { signIn, signUp } = useAuthSession();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string>();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const isSignUp = mode === "signUp";

  async function submit(): Promise<void> {
    setError(undefined);
    setIsSubmitting(true);

    try {
      if (isSignUp) {
        await signUp({ name, email, password });
      } else {
        await signIn({ email, password });
      }
      return;
    } catch (exception) {
      setError(getAuthErrorMessage(exception, t("auth.error.generic")));
    }
    setIsSubmitting(false);
  }

  return (
    <View className="flex-1 justify-center bg-bg px-6">
      <View className="mb-8 items-center">
        <Text variant="hero" className="text-center text-mint-deep">
          {t("auth.brand")}
        </Text>
        <Text variant="body" className="mt-2 text-center text-ink-soft">
          {t(isSignUp ? "auth.signUp.subtitle" : "auth.signIn.subtitle")}
        </Text>
      </View>
      <Card className="gap-4 p-6" shadow="hero">
        <Text variant="title">
          {t(isSignUp ? "auth.signUp.title" : "auth.signIn.title")}
        </Text>
        {isSignUp && (
          <FormField
            label={t("auth.fields.name")}
            value={name}
            onChangeText={setName}
            autoCapitalize="words"
            autoComplete="name"
          />
        )}
        <FormField
          label={t("auth.fields.email")}
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          autoComplete="email"
          keyboardType="email-address"
        />
        <FormField
          label={t("auth.fields.password")}
          value={password}
          onChangeText={setPassword}
          autoComplete={isSignUp ? "new-password" : "current-password"}
          secureTextEntry
        />
        {error && <Text className="text-center text-pink-deep">{error}</Text>}
        <Button
          label={t(isSignUp ? "auth.signUp.submit" : "auth.signIn.submit")}
          disabled={isSubmitting}
          onPress={() => void submit()}
          rightIcon={
            isSubmitting ? (
              <ActivityIndicator color={palette.white} />
            ) : undefined
          }
        />
        <Link href={isSignUp ? "/sign-in" : "/sign-up"} asChild>
          <Button
            label={t(
              isSignUp ? "auth.signUp.alternate" : "auth.signIn.alternate",
            )}
            variant="light"
          />
        </Link>
      </Card>
    </View>
  );
}

function getAuthErrorMessage(exception: unknown, fallback: string): string {
  const normalized = normalizeApiError(exception);
  return (
    Object.values(normalized.fields ?? {})[0] || normalized.message || fallback
  );
}
