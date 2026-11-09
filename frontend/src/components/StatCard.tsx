import type { ReactNode } from 'react';
import { EMPTY_VALUE, formatNumber } from '@/lib/format';

interface StatCardProps {
  label: string;
  value: number | string | null | undefined;
  icon: ReactNode;
  hint?: string;
  accent?: boolean;
  loading?: boolean;
}

export function StatCard({ label, value, icon, hint, accent = false, loading = false }: StatCardProps) {
  const display = loading ? '…' : value === null || value === undefined ? EMPTY_VALUE : formatNumber(value);

  return (
    <article className="card stat-card" aria-busy={loading}>
      <div className="stat-card__top">
        <span className="stat-card__label">{label}</span>
        <span className={`stat-card__icon ${accent ? 'stat-card__icon--accent' : ''}`} aria-hidden="true">
          {icon}
        </span>
      </div>
      <div className="stat-card__value">{display}</div>
      {hint && <div className="stat-card__hint">{hint}</div>}
    </article>
  );
}
