import axios, { AxiosError, type AxiosInstance } from 'axios';

/** Consistent API error shape (see agent_docs/08_API_SPECIFICATION.md §4). */
export interface ApiErrorBody {
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

export class ApiError extends Error {
  readonly status: number;
  readonly code: string;
  readonly errors: Record<string, string[]>;

  constructor(status: number, body: Partial<ApiErrorBody>) {
    super(body.message ?? 'Terjadi kesalahan pada server.');
    this.name = 'ApiError';
    this.status = status;
    this.code = body.code ?? 'UNKNOWN_ERROR';
    this.errors = body.errors ?? {};
  }

  get isUnauthenticated(): boolean {
    return this.status === 401;
  }

  get isForbidden(): boolean {
    return this.status === 403;
  }

  get isValidation(): boolean {
    return this.status === 422;
  }

  fieldError(field: string): string | undefined {
    return this.errors[field]?.[0];
  }
}

const TOKEN_STORAGE_KEY = 'erlass_pwms_token';

export const tokenStorage = {
  get: (): string | null => localStorage.getItem(TOKEN_STORAGE_KEY),
  set: (token: string): void => localStorage.setItem(TOKEN_STORAGE_KEY, token),
  clear: (): void => localStorage.removeItem(TOKEN_STORAGE_KEY),
};

type UnauthorizedHandler = () => void;
let onUnauthorized: UnauthorizedHandler | null = null;

export function setUnauthorizedHandler(handler: UnauthorizedHandler | null): void {
  onUnauthorized = handler;
}

export const http: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api/v1',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 30_000,
});

http.interceptors.request.use((config) => {
  const token = tokenStorage.get();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiErrorBody>) => {
    const status = error.response?.status ?? 0;
    const body: Partial<ApiErrorBody> = error.response?.data ?? {
      message: status === 0 ? 'Tidak dapat terhubung ke server.' : error.message,
      code: status === 0 ? 'NETWORK_ERROR' : undefined,
    };

    if (status === 401 && onUnauthorized) {
      onUnauthorized();
    }

    return Promise.reject(new ApiError(status, body));
  },
);

/** Generate a client-side idempotency key for posting endpoints. */
export function newIdempotencyKey(): string {
  return crypto.randomUUID();
}
