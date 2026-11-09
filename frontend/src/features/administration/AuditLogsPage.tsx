import { PageHeader } from '@/components/PageHeader';
import { DataTable, type Column } from '@/components/DataTable';
import { SearchBar } from '@/components/SearchBar';
import { useListParams } from '@/hooks/useListParams';
import { formatDateTime } from '@/lib/format';
import type { AuditLog } from '@/types/models';
import { useAuditLogs } from './api';

export function AuditLogsPage() {
  const { params, setSearch, setPage, setFilter } = useListParams({ per_page: 25 });
  const { data, isLoading, error, refetch } = useAuditLogs(params);

  const columns: Column<AuditLog>[] = [
    { key: 'created_at', header: 'Waktu', render: (l) => <span className="table__muted">{formatDateTime(l.created_at)}</span> },
    { key: 'action', header: 'Aksi', render: (l) => <code>{l.action}</code> },
    {
      key: 'entity',
      header: 'Entitas',
      render: (l) => (l.entity_type ? `${l.entity_type} #${l.entity_id ?? '—'}` : <span className="table__muted">—</span>),
    },
    { key: 'user', header: 'Pengguna', render: (l) => l.user?.name ?? <span className="table__muted">Sistem</span> },
    { key: 'ip', header: 'IP', render: (l) => <span className="table__muted">{l.ip_address ?? '—'}</span> },
  ];

  return (
    <>
      <PageHeader
        eyebrow="Administrasi"
        title="Audit Log"
        description="Jejak audit untuk login, perubahan master, dan tindakan sensitif. Catatan bersifat read-only."
      />
      <section className="card">
        <div className="toolbar">
          <SearchBar value={params.search ?? ''} onChange={setSearch} placeholder="Cari aksi atau entitas" />
          <input
            type="date"
            className="input"
            style={{ width: 170 }}
            aria-label="Dari tanggal"
            value={(params.from as string) ?? ''}
            onChange={(e) => setFilter('from', e.target.value)}
          />
          <input
            type="date"
            className="input"
            style={{ width: 170 }}
            aria-label="Sampai tanggal"
            value={(params.to as string) ?? ''}
            onChange={(e) => setFilter('to', e.target.value)}
          />
        </div>
        <DataTable
          columns={columns}
          rowKey={(l) => l.id}
          data={data}
          isLoading={isLoading}
          error={error}
          onRetry={() => refetch()}
          onPageChange={setPage}
          emptyTitle="Belum ada catatan audit"
        />
      </section>
    </>
  );
}
