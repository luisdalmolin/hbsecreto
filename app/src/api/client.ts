import { client } from './generated/client.gen';

const apiUrl = process.env.EXPO_PUBLIC_API_URL;

export function configureApiClient(accessToken?: string): void {
  if (!apiUrl) {
    throw new Error('EXPO_PUBLIC_API_URL must be configured before making API requests.');
  }

  client.setConfig({
    baseUrl: apiUrl.replace(/\/$/, ''),
    auth: accessToken,
  });
}
