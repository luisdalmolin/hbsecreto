let accessToken: string | undefined;

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly payload: unknown,
  ) {
    super(getErrorMessage(payload));
    this.name = 'ApiError';
  }
}

export function configureApiClient(token?: string): void {
  accessToken = token;
}

export async function apiFetch<T>(path: string, options: RequestInit): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');

  if (accessToken) {
    headers.set('Authorization', `Bearer ${accessToken}`);
  }

  const response = await fetch(`${getApiBaseUrl()}${path}`, { ...options, headers });
  const payload = await readPayload(response);

  if (!response.ok) {
    throw new ApiError(response.status, payload);
  }

  return payload as T;
}

function getApiBaseUrl(): string {
  const url = process.env.EXPO_PUBLIC_API_URL;

  if (!url) {
    throw new Error('EXPO_PUBLIC_API_URL must be configured before making API requests.');
  }

  return url.replace(/\/$/, '');
}

async function readPayload(response: Response): Promise<unknown> {
  if (response.status === 204 || response.status === 205) {
    return undefined;
  }

  const contentType = response.headers.get('content-type') ?? '';
  return contentType.includes('application/json') ? response.json() : response.text();
}

function getErrorMessage(payload: unknown): string {
  if (
    typeof payload === 'object' &&
    payload !== null &&
    'message' in payload &&
    typeof payload.message === 'string'
  ) {
    return payload.message;
  }

  return 'The API request failed.';
}
