export type BadgeTone = 'success' | 'warning' | 'danger' | 'info' | 'neutral';

/**
 * Central status → tone/label mapping so every module renders statuses consistently
 * (design brief §5.3). Extend as new document lifecycles are implemented.
 */
const STATUS_MAP: Record<string, { tone: BadgeTone; label: string }> = {
  ACTIVE: { tone: 'success', label: 'Aktif' },
  INACTIVE: { tone: 'neutral', label: 'Nonaktif' },
  DRAFT: { tone: 'neutral', label: 'Draft' },
  SUBMITTED: { tone: 'warning', label: 'Menunggu Persetujuan' },
  PENDING: { tone: 'warning', label: 'Menunggu' },
  APPROVED: { tone: 'success', label: 'Disetujui' },
  REJECTED: { tone: 'danger', label: 'Ditolak' },
  IN_PROGRESS: { tone: 'info', label: 'Dalam Proses' },
  COMPLETED: { tone: 'success', label: 'Selesai' },
  POSTED: { tone: 'success', label: 'Posted' },
  REVERSED: { tone: 'neutral', label: 'Reversed' },
  CANCELLED: { tone: 'neutral', label: 'Dibatalkan' },
  DIPESAN: { tone: 'info', label: 'Dipesan' },
  DIKIRIM: { tone: 'warning', label: 'Dikirim' },
  'SELESAI DITERIMA': { tone: 'success', label: 'Selesai Diterima' },
  DELIVERED: { tone: 'success', label: 'Selesai' },
  SELESAI: { tone: 'success', label: 'Selesai' },
  IN_TRANSIT: { tone: 'info', label: 'Dalam Pengiriman' },
  DISPATCHED: { tone: 'info', label: 'Dikirim' },
  LOW_STOCK: { tone: 'warning', label: 'Stok Rendah' },
  OUT_OF_STOCK: { tone: 'danger', label: 'Stok Habis' },
  SAFE: { tone: 'success', label: 'Aman' },
};

interface StatusBadgeProps {
  status: string;
  label?: string;
  tone?: BadgeTone;
}

export function StatusBadge({ status, label, tone }: StatusBadgeProps) {
  const mapped = STATUS_MAP[status] ?? { tone: 'neutral' as BadgeTone, label: status };
  return <span className={`badge badge--${tone ?? mapped.tone}`}>{label ?? mapped.label}</span>;
}
