import { useState, type ReactNode } from 'react';
import { Activity, Boxes, ClipboardList, Factory, RefreshCw, ScanBarcode, ShieldCheck, Users } from 'lucide-react';
import { Button } from '@/components/Button';
import { PageHeader } from '@/components/PageHeader';
import { StatCard } from '@/components/StatCard';
import { EmptyState, ErrorState, LoadingState } from '@/components/States';
import { useAuth } from '@/features/auth/AuthContext';
import { BarcodeScanModal } from '@/features/catalog/BarcodeScanModal';
import { formatDateTime } from '@/lib/format';
import { useDashboardSummary } from './api';

const METRIC_ICONS: Record<string, ReactNode> = {
  active_users: <Users size={18} />,
  roles: <ShieldCheck size={18} />,
  audit_events_today: <Activity size={18} />,
  products: <Boxes size={18} />,
  production_plans: <ClipboardList size={18} />,
  production_orders: <Factory size={18} />,
};

export function DashboardPage() {
  const { user } = useAuth();
  const { data, isLoading, error, refetch, isFetching } = useDashboardSummary();
  const [isScanOpen, setIsScanOpen] = useState(false);

  return (
    <>
      <PageHeader
        eyebrow="Dashboard"
        title="Ringkasan Operasional"
        description="Pantau kondisi sistem, inventaris, dan produksi Erlass dari satu tempat."
        actions={
          <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
            <Button
              variant="secondary"
              icon={<ScanBarcode size={16} />}
              onClick={() => setIsScanOpen(true)}
              title="Scan barcode untuk langsung mendeteksi produk Finished Good"
            >
              Scan Barcode
            </Button>
            <Button icon={<RefreshCw size={16} />} onClick={() => refetch()} loading={isFetching}>
              Muat ulang
            </Button>
          </div>
        }
      />

      <section className="banner">
        <div>
          <h2>Selamat datang, {user?.name.split(' ')[0]}!</h2>
          <p>
            Fondasi ERLASS PWMS sudah aktif. Modul master data, BOM, inventaris, dan produksi akan
            tersedia bertahap sesuai roadmap implementasi.
          </p>
        </div>
        <div className="banner__art" aria-hidden="true">
          <span />
          <span />
          <span />
        </div>
      </section>

      {isLoading ? (
        <LoadingState />
      ) : error ? (
        <ErrorState error={error} onRetry={() => refetch()} />
      ) : (
        <>
          <div className="grid grid--stats">
            {data?.metrics.map((metric, idx) => (
              <StatCard
                key={metric.key}
                label={metric.label}
                value={metric.value}
                hint={metric.hint ?? undefined}
                icon={METRIC_ICONS[metric.key] ?? <Activity size={18} />}
                accent={idx % 2 === 1}
              />
            ))}
          </div>

          <section className="card">
            <div className="card__header">
              <h2 className="card__title">Aktivitas Terbaru</h2>
              <span className="table__muted">Diperbarui {formatDateTime(data?.generated_at)}</span>
            </div>
            {data && data.recent_activities.length > 0 ? (
              <div className="table-wrap">
                <table className="table">
                  <thead>
                    <tr>
                      <th>Aktivitas</th>
                      <th>Modul</th>
                      <th>Pengguna</th>
                      <th>Waktu</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.recent_activities.map((item) => (
                      <tr key={item.id}>
                        <td>{item.action}</td>
                        <td>
                          <span className="chip">{item.module}</span>
                        </td>
                        <td>{item.user ?? <span className="table__muted">Sistem</span>}</td>
                        <td className="table__muted">{formatDateTime(item.created_at)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <EmptyState title="Belum ada aktivitas" description="Aktivitas yang tercatat di audit log akan tampil di sini." />
            )}
          </section>
        </>
      )}

      {/* Modal Cepat Scan Barcode FG & Unit MLK Kamera Device / HP */}
      <BarcodeScanModal
        isOpen={isScanOpen}
        onClose={() => setIsScanOpen(false)}
      />
    </>
  );
}
