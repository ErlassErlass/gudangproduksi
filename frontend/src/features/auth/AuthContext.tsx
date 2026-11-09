import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { setUnauthorizedHandler, tokenStorage } from '@/api/client';
import type { AuthUser } from '@/types/models';
import { authApi, type LoginPayload } from './api';

type AuthStatus = 'loading' | 'authenticated' | 'guest';

interface AuthContextValue {
  status: AuthStatus;
  user: AuthUser | null;
  login: (payload: LoginPayload) => Promise<void>;
  logout: () => Promise<void>;
  updateCurrentUser: (user: AuthUser) => void;
  /** UI-only permission check. The backend remains the authoritative enforcement point. */
  can: (permission: string | string[] | undefined) => boolean;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const [user, setUser] = useState<AuthUser | null>(null);
  const [status, setStatus] = useState<AuthStatus>(() => (tokenStorage.get() ? 'loading' : 'guest'));

  const resetSession = useCallback(() => {
    tokenStorage.clear();
    setUser(null);
    setStatus('guest');
    queryClient.clear();
  }, [queryClient]);

  useEffect(() => {
    setUnauthorizedHandler(resetSession);
    return () => setUnauthorizedHandler(null);
  }, [resetSession]);

  useEffect(() => {
    if (!tokenStorage.get()) return;
    let cancelled = false;
    authApi
      .me()
      .then((me) => {
        if (cancelled) return;
        setUser(me);
        setStatus('authenticated');
      })
      .catch(() => {
        if (!cancelled) resetSession();
      });
    return () => {
      cancelled = true;
    };
  }, [resetSession]);

  const login = useCallback(async (payload: LoginPayload) => {
    const result = await authApi.login(payload);
    tokenStorage.set(result.token);
    setUser(result.user);
    setStatus('authenticated');
  }, []);

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } finally {
      resetSession();
    }
  }, [resetSession]);

  const updateCurrentUser = useCallback((updated: AuthUser) => {
    setUser(updated);
  }, []);

  const can = useCallback(
    (permission: string | string[] | undefined) => {
      if (!permission) return true;
      if (!user) return false;
      const required = Array.isArray(permission) ? permission : [permission];
      return required.some((code) => user.permissions.includes(code));
    },
    [user],
  );

  const value = useMemo<AuthContextValue>(
    () => ({ status, user, login, logout, updateCurrentUser, can }),
    [status, user, login, logout, updateCurrentUser, can],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside <AuthProvider>.');
  return ctx;
}
