import { Link } from 'expo-router';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { ActivityIndicator, TextInput, View } from 'react-native';

import { useAuthSession } from '@/auth/auth-session';
import { ApiError } from '@/api/http';
import { Button, Card, Text } from '@/components/ui';
import { palette } from '@/theme/tokens';

type Mode = 'signIn' | 'signUp';

export function AuthForm({ mode }: { mode: Mode }) {
  const { t } = useTranslation();
  const { signIn, signUp } = useAuthSession();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string>();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const isSignUp = mode === 'signUp';

  async function submit(): Promise<void> {
    setError(undefined);
    setIsSubmitting(true);

    try {
      if (isSignUp) {
        await signUp({ name, email, password });
      } else {
        await signIn({ email, password });
      }
    } catch (exception) {
      setError(getAuthErrorMessage(exception, t('auth.error.generic')));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <View className="flex-1 justify-center bg-bg px-6">
      <View className="mb-8 items-center">
        <Text variant="hero" className="text-center text-mint-deep">
          {t('auth.brand')}
        </Text>
        <Text variant="body" className="mt-2 text-center text-ink-soft">
          {t(isSignUp ? 'auth.signUp.subtitle' : 'auth.signIn.subtitle')}
        </Text>
      </View>
      <Card className="gap-4 p-6" shadow="hero">
        <Text variant="title">{t(isSignUp ? 'auth.signUp.title' : 'auth.signIn.title')}</Text>
        {isSignUp && (
          <Field
            label={t('auth.fields.name')}
            value={name}
            onChangeText={setName}
            autoCapitalize="words"
            autoComplete="name"
          />
        )}
        <Field
          label={t('auth.fields.email')}
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          autoComplete="email"
          keyboardType="email-address"
        />
        <Field
          label={t('auth.fields.password')}
          value={password}
          onChangeText={setPassword}
          autoComplete={isSignUp ? 'new-password' : 'current-password'}
          secureTextEntry
        />
        {error && <Text className="text-center text-pink-deep">{error}</Text>}
        <Button
          label={t(isSignUp ? 'auth.signUp.submit' : 'auth.signIn.submit')}
          disabled={isSubmitting}
          onPress={() => void submit()}
          rightIcon={isSubmitting ? <ActivityIndicator color={palette.white} /> : undefined}
        />
        <Link href={isSignUp ? '/sign-in' : '/sign-up'} asChild>
          <Button
            label={t(isSignUp ? 'auth.signUp.alternate' : 'auth.signIn.alternate')}
            variant="light"
          />
        </Link>
      </Card>
    </View>
  );
}

function Field(props: React.ComponentProps<typeof TextInput> & { label: string }) {
  const { label, ...inputProps } = props;

  return (
    <View className="gap-1.5">
      <Text variant="bodyBold">{label}</Text>
      <TextInput
        className="rounded-tile border border-outline bg-card px-4 py-3 font-body-reg text-[16px] text-ink"
        placeholderTextColor={palette.inkMuted}
        {...inputProps}
      />
    </View>
  );
}

function getAuthErrorMessage(exception: unknown, fallback: string): string {
  if (exception instanceof ApiError && isValidationPayload(exception.payload)) {
    return Object.values(exception.payload.errors).flat()[0] ?? exception.payload.message;
  }

  return exception instanceof Error ? exception.message : fallback;
}

function isValidationPayload(payload: unknown): payload is { message: string; errors: Record<string, string[]> } {
  return (
    typeof payload === 'object' &&
    payload !== null &&
    'message' in payload &&
    typeof payload.message === 'string' &&
    'errors' in payload &&
    typeof payload.errors === 'object' &&
    payload.errors !== null
  );
}
