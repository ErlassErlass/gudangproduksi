import type { ReactNode } from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { LoadingState, PermissionDenied } from '@/components/States';
import { useAuth } from '@/features/auth/AuthContext';

export function RequireAuth() {
  const { status } = useAuth();
  const location = useLocation();

  if (status === 'loading') {
    return (
      <div className="full-screen-center">
        <LoadingState label="Memeriksa sesi…" />
      </div>
    );
  }

  if (status === 'guest') {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  return <Outlet />;
}

export function RequirePermission({ permission, children }: { permission?: string | string[]; children: ReactNode }) {
  const { can } = useAuth();
  return can(permission) ? <>{children}</> : <section className="card"><PermissionDenied /></section>;
}
