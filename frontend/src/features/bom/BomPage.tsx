import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Calculator, Edit2, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { http } from '@/api/client';
import type { ResourceResponse } from '@/api/types';
import { Button } from '@/components/Button';
import { ConfirmationDialog } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { StatusBadge } from '@/components/StatusBadge';
import { EmptyState, ErrorState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import { formatNumber, formatRupiah } from '@/lib/format';
import { BomFormModal } from './BomFormModal';

interface BomSummary {
  id: number;
  code: string;
  name: string;
  product_id: number;
  bom_type: 'STANDARD_MLK' | 'FLEXIBLE';
  status: string;
  product: { id: number; code: string; name: string };
  stage_template: { id: number; code: string; name: string } | null;
  versions: {
    id: number;
    version_no: number;
    status: string;
    effective_from: string | null;
    items_count: number;
  }[];
}

interface ExplosionResponse {
  bom_version_id: number;
  target_qty: string;
  estimated_material_budget: string;
  requirements: {
    product_id: number;
    product_code: string;
    product_name: string;
    item_type: string;
    uom_code: string;
    gross_qty: string;
    estimated_unit_cost: string | null;
    estimated_total_cost: string | null;
    level: number;
  }[];
}

export function BomPage() {
  const { can } = useAuth();
  const canManage = can('bom.manage');
  const { notify } = useToast();
  const qc = useQueryClient();

  const [selectedVersionId, setSelectedVersionId] = useState<number | null>(null);
  const [targetQty, setTargetQty] = useState('5');

  // Modals state
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingBom, setEditingBom] = useState<BomSummary | null>(null);
  const [deletingBom, setDeletingBom] = useState<BomSummary | null>(null);

  const boms = useQuery({
    queryKey: ['boms'],
    queryFn: async () => (await http.get<ResourceResponse<BomSummary[]>>('/boms')).data.data,
  });

  const explosion = useQuery({
    queryKey: ['boms', 'explosion', selectedVersionId, targetQty],
    enabled: selectedVersionId !== null && Number(targetQty) > 0,
    queryFn: async () =>
      (
        await http.get<ResourceResponse<ExplosionResponse>>(`/bom-versions/${selectedVersionId}/explosion`, {
          params: { quantity: targetQty },
        })
      ).data.data,
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => (await http.delete(`/boms/${id}`)).data,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['boms'] });
      notify('Resep BOM berhasil dihapus.', 'success');
      setDeletingBom(null);
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message ?? err.message ?? 'Gagal menghapus resep BOM.', 'error');
    },
  });

  return (
    <>
      <PageHeader
        eyebrow="Master Data — BOM"
        title="Bill of Materials (Standard MLK & Fleksibel)"
        description="Kelola resep struktur komponen bertingkat (tambah, edit, hapus BOM) serta kalkulasi simulasi kebutuhan material (BOM explosion)."
        actions={
          <div style={{ display: 'flex', gap: 8 }}>
            <Button icon={<RefreshCw size={15} />} onClick={() => boms.refetch()} loading={boms.isFetching}>
              Muat Ulang
            </Button>
            {canManage && (
              <Button
                variant="primary"
                icon={<Plus size={15} />}
                onClick={() => {
                  setEditingBom(null);
                  setIsFormOpen(true);
                }}
              >
                Tambah Resep BOM
              </Button>
            )}
          </div>
        }
      />

      {boms.isLoading ? (
        <LoadingState label="Memuat daftar resep BOM..." />
      ) : boms.error ? (
        <ErrorState error={boms.error} onRetry={() => boms.refetch()} />
      ) : !boms.data || boms.data.length === 0 ? (
        <section className="card">
          <EmptyState
            title="Belum ada resep BOM"
            description="Belum ada resep BOM terdaftar. Klik Tambah Resep BOM untuk membuat formula perakitan pertama."
            action={
              canManage ? (
                <Button variant="primary" icon={<Plus size={16} />} onClick={() => setIsFormOpen(true)}>
                  Tambah Resep BOM
                </Button>
              ) : undefined
            }
          />
        </section>
      ) : (
        <div className="grid grid--2">
          <section className="card">
            <div className="card__header" style={{ marginBottom: 12 }}>
              <h2 className="card__title">Daftar Resep BOM Terdaftar</h2>
            </div>
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th>Kode & Nama BOM</th>
                    <th>Produk Output</th>
                    <th style={{ width: '100px' }}>Versi</th>
                    <th style={{ width: '120px', textAlign: 'center' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {boms.data.map((bom) => {
                    const active = bom.versions.find((v) => v.status === 'ACTIVE') ?? bom.versions[0];
                    return (
                      <tr key={bom.id}>
                        <td>
                          <code style={{ fontWeight: 700 }}>{bom.code}</code>
                          <div style={{ fontSize: '0.85rem', color: '#475569', marginTop: 2 }}>{bom.name}</div>
                        </td>
                        <td>
                          <code>{bom.product.code}</code> &bull; {bom.product.name}
                        </td>
                        <td>
                          {active ? (
                            <div style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
                              <span style={{ fontWeight: 600 }}>v{active.version_no}</span>
                              <StatusBadge status={active.status} />
                            </div>
                          ) : (
                            '—'
                          )}
                        </td>
                        <td style={{ textAlign: 'center' }}>
                          <div style={{ display: 'inline-flex', gap: 4, alignItems: 'center' }}>
                            {active && (
                              <Button
                                size="sm"
                                variant={selectedVersionId === active.id ? 'primary' : 'ghost'}
                                iconOnly
                                icon={<Calculator size={15} />}
                                onClick={() => setSelectedVersionId(active.id)}
                                aria-label="Simulasi explosion"
                              />
                            )}
                            {canManage && (
                              <>
                                <Button
                                  size="sm"
                                  variant="ghost"
                                  iconOnly
                                  icon={<Edit2 size={15} />}
                                  onClick={() => {
                                    setEditingBom(bom);
                                    setIsFormOpen(true);
                                  }}
                                  aria-label="Edit resep BOM"
                                />
                                <Button
                                  size="sm"
                                  variant="ghost"
                                  iconOnly
                                  icon={<Trash2 size={15} color="var(--color-danger, #d96a36)" />}
                                  onClick={() => setDeletingBom(bom)}
                                  aria-label="Hapus resep BOM"
                                />
                              </>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </section>

          <section className="card">
            <div className="card__header" style={{ marginBottom: 12, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <h2 className="card__title">Simulasi Multi-Level BOM Explosion</h2>
              <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <label htmlFor="target-qty" className="table__muted" style={{ fontSize: '0.85rem' }}>
                  Target Qty:
                </label>
                <input
                  id="target-qty"
                  type="number"
                  min="1"
                  className="input font-mono font-bold"
                  style={{ width: 80, minHeight: 32, padding: '4px 8px' }}
                  value={targetQty}
                  onChange={(e) => setTargetQty(e.target.value)}
                />
              </div>
            </div>
            {!selectedVersionId ? (
              <EmptyState
                title="Pilih BOM di sebelah kiri"
                description="Klik ikon kalkulator (Explosion) pada baris resep BOM untuk menghitung kebutuhan komponen dan estimasi biaya material."
              />
            ) : explosion.isLoading ? (
              <LoadingState label="Menghitung simulasi explosion..." />
            ) : explosion.error ? (
              <ErrorState error={explosion.error} />
            ) : (
              <>
                <div className="table-wrap">
                  <table className="table" style={{ fontSize: '0.825rem' }}>
                    <thead>
                      <tr>
                        <th>Komponen Masukan</th>
                        <th style={{ width: '60px' }}>Level</th>
                        <th>Kebutuhan Kotor</th>
                        <th>Harga Satuan</th>
                        <th>Estimasi Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      {explosion.data?.requirements.map((req) => (
                        <tr key={req.product_id}>
                          <td>
                            <code>{req.product_code}</code>
                            <div className="table__muted">{req.product_name}</div>
                          </td>
                          <td>
                            <span className="chip" style={{ fontSize: '0.75rem' }}>L{req.level}</span>
                          </td>
                          <td>
                            <span style={{ fontFamily: 'monospace', fontWeight: 600 }}>
                              {formatNumber(req.gross_qty)} {req.uom_code}
                            </span>
                          </td>
                          <td style={{ fontFamily: 'monospace' }}>{formatRupiah(req.estimated_unit_cost)}</td>
                          <td style={{ fontFamily: 'monospace', fontWeight: 700, color: 'var(--color-primary, #3859ac)' }}>
                            {formatRupiah(req.estimated_total_cost)}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 12, paddingTop: 10, borderTop: '1px solid #e2e8f0' }}>
                  <span style={{ fontSize: '0.85rem', color: '#475569' }}>Estimasi Total Biaya Material:</span>
                  <strong style={{ color: 'var(--color-primary-dark, #263f7d)', fontSize: '1.1rem', fontFamily: 'monospace' }}>
                    {formatRupiah(explosion.data?.estimated_material_budget)}
                  </strong>
                </div>
              </>
            )}
          </section>
        </div>
      )}

      {/* Modal Tambah / Edit BOM */}
      {isFormOpen && (
        <BomFormModal
          initialBom={editingBom}
          isOpen={isFormOpen}
          onClose={() => {
            setIsFormOpen(false);
            setEditingBom(null);
          }}
          onSuccess={() => boms.refetch()}
        />
      )}

      {/* Dialog Konfirmasi Hapus BOM */}
      <ConfirmationDialog
        open={Boolean(deletingBom)}
        title="Hapus Resep BOM"
        message={`Apakah Anda yakin ingin menghapus resep [${deletingBom?.code}] ${deletingBom?.name}? Resep BOM yang sudah digunakan dalam perintah produksi tidak dapat dihapus.`}
        confirmLabel="Hapus Resep BOM"
        danger
        loading={deleteMutation.isPending}
        onConfirm={() => deletingBom && deleteMutation.mutate(deletingBom.id)}
        onCancel={() => setDeletingBom(null)}
      />
    </>
  );
}
