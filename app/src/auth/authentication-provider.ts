import { login, register } from '@/api/generated/authentication/authentication';
import type { Authentication, LoginRequest, RegisterRequest } from '@/api/generated/models';

export interface AuthenticationProvider<TInput> {
  authenticate(input: TInput): Promise<Authentication>;
}

/**
 * Password authentication is represented by two providers. Future Google or
 * Apple providers can implement this same contract with their OAuth payload.
 */
export const passwordSignInProvider: AuthenticationProvider<LoginRequest> = {
  authenticate: login,
};

export const passwordSignUpProvider: AuthenticationProvider<RegisterRequest> = {
  authenticate: register,
};
