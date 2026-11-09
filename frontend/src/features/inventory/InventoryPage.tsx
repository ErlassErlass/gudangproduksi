import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { ArrowLeftRight, Boxes, Lock, ShieldAlert, Warehouse } from 'lucide-react';
import { ApiError, http, newIdempotencyKey } from '@/api/client';
import type { PaginatedResponse, ResourceResponse } from '@/api/types';
import { Button } from '@/components/Button';
import { DataTable, type Column } from '@/components/DataTable';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { SearchBar } from '@/components/SearchBar';
import { StatCard } from '@/components/StatCard';
import { StatusBadge } from '@/components/StatusBadge';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import { useLocations, useProducts, useWarehouses } from '@/features/master-data/api';
import { useListParams } from '@/hooks/useListParams';
import { formatNumber, formatRupiah } from '@/lib/format';

interface BalanceRow {
  id: number;
  product: { id: number; code: string; name: string; uom: string; tracking_method: string };
  warehouse: { id: number; code: string; name: string } | null;
  location: { id: number; code: string; name: string; is_external: boolean } | null;
  batch_number: string | null;
  serial_number: string | null;
  stock_status: string;
  on_hand_qty: string;
  reserved_qty: string;
  available_qty: string;
  avg_unit_cost: string;
}

interface BalancesEnvelope extends PaginatedResponse<BalanceRow> {
  summary: {
    on_hand_available_bucket: string;
    reserved: string;
    available: string;
    quarantine: string;
    damaged: string;
    at_vendor: string;
    in_transit: string;
  };
}

export function InventoryPage() {
  const { can } = useAuth();
  const canPost = can('inventory.transaction.post');
  const { params, setSearch, setPage, setFilter } = useListParams({
    per_page: 20,
    category_code: 'FG',
    physical_type: 'PHYSICAL',
  });
  const warehouses = useWarehouses();
  const [transferOpen, setTransferOpen] = useState(false);

  const balances = useQuery({
    queryKey: ['inventory', 'balances', params],
    queryFn: async () => (await http.get<BalancesEnvelope>('/inventory/balances', { params })).data,
  });

  const columns: Column<BalanceRow>[] = [
    {
      key: 'product',
      header: 'Finished Good Fisik',
      render: (b) => (
        <div>
          <code>{b.product.code}</code> · <strong>{b.product.name}</strong>
          {b.serial_number && <div className="table__muted">SN: {b.serial_number}</div>}
          {b.batch_number && <div className="table__muted">Batch: {b.batch_number}</div>}
        </div>
      ),
    },
    {
      key: 'location',
      header: 'Lokasi Penyimpanan',
      render: (b) => (
        <div>
          <div>{b.location?.code ?? '—'}</div>
          <div className="table__muted">{b.warehouse?.name}</div>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'Status Stok',
      render: (b) => (
        <StatusBadge
          status={b.stock_status}
          tone={b.stock_status === 'AVAILABLE' ? 'success' : b.stock_status === 'AT_VENDOR' ? 'info' : 'warning'}
        />
      ),
    },
    {
      key: 'available',
      header: 'Stok Tersedia (Pickable)',
      render: (b) => (
        <strong style={{ color: 'var(--color-primary, #3859ac)', fontFamily: 'monospace', fontSize: '0.95rem' }}>
          {formatNumber(b.available_qty)} {b.product.uom ?? 'Unit'}
        </strong>
      ),
    },
    { key: 'reserved', header: 'Reserved (Sewa/WO)', render: (b) => formatNumber(b.reserved_qty) },
    { key: 'cost', header: 'Unit Cost Rata-Rata', render: (b) => formatRupiah(b.avg_unit_cost) },
  ];

  const summary = balances.data?.summary;

  return (
    <>
      <PageHeader
        eyebrow="Inventaris & Ledger"
        title="Persediaan Finished Good Fisik"
        description="Pemantauan persediaan stok produk Finished Good fisik (Mobile Learning Kit & produk jadi terakit) di seluruh lokasi penyimpanan."
        actions={
          canPost && (
            <Button variant="primary" icon={<ArrowLeftRight size={16} />} onClick={() => setTransferOpen(true)}>
              Transfer Antar Lokasi
            </Button>
          )
        }
      />

      <div className="grid grid--3">
        <StatCard label="Finished Good Siap Pakai" value={summary?.available} icon={<Boxes size={18} />} hint="Stok tersedia di gudang" />
        <StatCard label="Reserved (Sewa/Projek)" value={summary?.reserved} icon={<Lock size={18} />} accent hint="Terkunci untuk sewa sekolah / sales" />
        <StatCard label="Karantina / Menunggu QC" value={summary ? Number(summary.quarantine) + Number(summary.damaged) : null} icon={<ShieldAlert size={18} />} accent hint="Menunggu inspeksi kelengkapan" />
      </div>

      <section className="card">
        <div className="toolbar">
          <SearchBar value={params.search ?? ''} onChange={setSearch} placeholder="Cari kode atau nama Finished Good..." />
          <select
            className="select"
            style={{ width: 220 }}
            aria-label="Filter lokasi gudang"
            value={(params.warehouse_id as string) ?? ''}
            onChange={(e) => setFilter('warehouse_id', e.target.value)}
          >
            <option value="">Semua Lokasi Gudang</option>
            {warehouses.data?.map((w) => (
              <option key={w.id} value={w.id}>
                {w.code} — {w.name}
              </option>
            ))}
          </select>
        </div>

        <DataTable
          columns={columns}
          rowKey={(b) => b.id}
          data={balances.data}
          isLoading={balances.isLoading}
          error={balances.error}
          onRetry={() => balances.refetch()}
          onPageChange={setPage}
        />
      </section>

      {transferOpen && <TransferModal onClose={() => setTransferOpen(false)} />}
    </>
  );
}

function TransferModal({ onClose }: { onClose: () => void }) {
  const { notify } = useToast();
  const qc = useQueryClient();
  const locations = useLocations();
  const products = useProducts({ per_page: 100, product_type: 'PRODUCT' });
  const [idempotencyKey] = useState(() => newIdempotencyKey());
  const [form, setForm] = useState({
    source_location_id: '',
    destination_location_id: '',
    product_id: '',
    quantity: '1',
    notes: '',
  });

  const transfer = useMutation({
    mutationFn: async () =>
      (
        await http.post<ResourceResponse<{ document_no: string }>>(
          '/inventory/transfers',
          {
            source_location_id: Number(form.source_location_id),
            destination_location_id: Number(form.destination_location_id),
            notes: form.notes || null,
            items: [{ product_id: Number(form.product_id), quantity: form.quantity }],
          },
          { headers: { 'Idempotency-Key': idempotencyKey } },
        )
      ).data.data,
    onSuccess: (res) => {
      qc.invalidateQueries({ queryKey: ['inventory'] });
      notify(`Transfer ${res.document_no} berhasil diposting ke ledger.`, 'success');
      onClose();
    },
  });

  const apiError = transfer.error instanceof ApiError ? transfer.error : null;

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    transfer.mutate();
  };

  return (
    <Modal
      open
      title="Posting Transfer Stok Antar Lokasi"
      onClose={onClose}
      footer={
        <>
          <Button onClick={onClose}>Batal</Button>
          <Button variant="primary" type="submit" form="transfer-form" loading={transfer.isPending}>
            Posting Transfer
          </Button>
        </>
      }
    >
      <form id="transfer-form" onSubmit={handleSubmit} noValidate>
        <p className="field__hint" style={{ marginBottom: 16 }}>
          Tindakan ini akan mengunci saldo lokasi asal, memvalidasi kuantitas tersedia, dan membuat jurnal
          mutasi yang bersifat immutable.
        </p>
        {apiError && !apiError.isValidation && <div className="alert alert--danger">{apiError.message}</div>}

        <div className="grid grid--2">
          <FormField label="Lokasi Asal" htmlFor="trf-from" required error={apiError?.fieldError('source_location_id')}>
            <select
              id="trf-from"
              className="select"
              value={form.source_location_id}
              onChange={(e) => setForm({ ...form, source_location_id: e.target.value })}
            >
              <option value="">Pilih lokasi asal</option>
              {locations.data?.map((l) => (
                <option key={l.id} value={l.id}>
                  {l.code} — {l.name}
                </option>
              ))}
            </select>
          </FormField>
          <FormField label="Lokasi Tujuan" htmlFor="trf-to" required error={apiError?.fieldError('destination_location_id')}>
            <select
              id="trf-to"
              className="select"
              value={form.destination_location_id}
              onChange={(e) => setForm({ ...form, destination_location_id: e.target.value })}
            >
              <option value="">Pilih lokasi tujuan</option>
              {locations.data?.map((l) => (
                <option key={l.id} value={l.id}>
                  {l.code} — {l.name}
                </option>
              ))}
            </select>
          </FormField>
        </div>

        <div className="grid grid--2">
          <FormField label="Barang" htmlFor="trf-prod" required error={apiError?.fieldError('items.0.product_id')}>
            <select
              id="trf-prod"
              className="select"
              value={form.product_id}
              onChange={(e) => setForm({ ...form, product_id: e.target.value })}
            >
              <option value="">Pilih barang</option>
              {products.data?.data
                .filter((p) => p.tracking_method === 'QUANTITY')
                .map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.code} — {p.name}
                  </option>
                ))}
            </select>
          </FormField>
          <FormField label="Kuantitas" htmlFor="trf-qty" required error={apiError?.fieldError('items.0.quantity')}>
            <input
              id="trf-qty"
              type="number"
              step="0.0001"
              min="0.0001"
              className="input"
              value={form.quantity}
              onChange={(e) => setForm({ ...form, quantity: e.target.value })}
            />
          </FormField>
        </div>

        <FormField label="Catatan / Referensi" htmlFor="trf-notes">
          <input id="trf-notes" className="input" value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
        </FormField>
      </form>
    </Modal>
  );
}

export function WarehousePage() {
  const warehouses = useWarehouses();
  const locations = useLocations();

  return (
    <>
      <PageHeader
        eyebrow="Gudang & Lokasi"
        title="Hierarki Lokasi Penyimpanan"
        description="Struktur bertingkat (Gedung → Lantai → Ruangan → Rak → Bin) serta lokasi eksternal vendor GAP."
      />
      <div className="grid grid--stats">
        {warehouses.data?.map((wh) => (
          <StatCard
            key={wh.id}
            label={`${wh.code} · ${wh.warehouse_type}`}
            value={wh.locations_count ?? 0}
            icon={<Warehouse size={18} />}
            hint={wh.name}
          />
        ))}
      </div>
      <section className="card">
        <div className="card__header">
          <h2 className="card__title">Daftar Lokasi & Barcode Rak</h2>
        </div>
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr>
                <th>Kode Lokasi</th>
                <th>Barcode</th>
                <th>Nama Lokasi</th>
                <th>Tipe</th>
                <th>Gudang</th>
                <th>Sifat</th>
              </tr>
            </thead>
            <tbody>
              {locations.data?.map((loc) => (
                <tr key={loc.id}>
                  <td>
                    <code>{loc.code}</code>
                  </td>
                  <td>{loc.barcode ? <code>{loc.barcode}</code> : '—'}</td>
                  <td>{loc.name}</td>
                  <td>
                    <span className="chip">{loc.location_type}</span>
                  </td>
                  <td>{loc.warehouse?.name}</td>
                  <td>
                    <StatusBadge
                      status={loc.is_external ? 'AT_VENDOR' : 'ACTIVE'}
                      label={loc.is_external ? 'Eksternal (GAP)' : 'Internal Erlass'}
                      tone={loc.is_external ? 'info' : 'success'}
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </>
  );
}
