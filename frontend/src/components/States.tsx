import type { ReactNode } from 'react';
import { AlertTriangle, Inbox, Lock } from 'lucide-react';
import { ApiError } from '@/api/client';
import { Button } from './Button';

export function LoadingState({ label, message }: { label?: string; message?: string }) {
  const text = message ?? label ?? 'Memuat data…';
  return (
    <div className="state" role="status" aria-live="polite">
      <span className="spinner" aria-hidden="true" style={{ width: 28, height: 28, color: 'var(--color-primary)' }} />
      <span>{text}</span>
    </div>
  );
}

interface EmptyStateProps {
  title?: string;
  description?: string;
  message?: string;
  icon?: ReactNode;
  action?: ReactNode;
}

export function EmptyState({
  title = 'Belum ada data',
  description,
  message,
  icon = <Inbox size={26} />,
  action,
}: EmptyStateProps) {
  const text = description ?? message ?? 'Data akan tampil di sini setelah tersedia.';
  return (
    <div className="state">
      <span className="state__icon" aria-hidden="true">
        {icon}
      </span>
      <span className="state__title">{title}</span>
      <span>{text}</span>
      {action}
    </div>
  );
}

interface ErrorStateProps {
  error: unknown;
  onRetry?: () => void;
}

export function ErrorState({ error, onRetry }: ErrorStateProps) {
  const apiError = error instanceof ApiError ? error : null;

  if (apiError?.isForbidden) {
    return <PermissionDenied />;
  }

  return (
    <div className="state" role="alert">
      <span className="state__icon state__icon--danger" aria-hidden="true">
        <AlertTriangle size={26} />
      </span>
      <span className="state__title">Gagal memuat data</span>
      <span>{apiError?.message ?? 'Terjadi kesalahan yang tidak terduga.'}</span>
      {onRetry && (
        <Button variant="secondary" onClick={onRetry}>
          Coba lagi
        </Button>
      )}
    </div>
  );
}

export function PermissionDenied() {
  return (
    <div className="state" role="alert">
      <span className="state__icon state__icon--danger" aria-hidden="true">
        <Lock size={26} />
      </span>
      <span className="state__title">Akses ditolak</span>
      <span>Anda tidak memiliki izin untuk membuka halaman atau melakukan tindakan ini.</span>
    </div>
  );
}
