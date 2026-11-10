import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Boxes, Calculator, CheckCircle2, Hammer, Layers, ListOrdered, PackageCheck, Plus, ShoppingCart, Trash2 } from 'lucide-react';
import { http, newIdempotencyKey } from '@/api/client';
import type { ResourceResponse } from '@/api/types';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { StatusBadge } from '@/components/StatusBadge';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import { useLocations, usePartners, useProducts } from '@/features/master-data/api';
import { formatDate, formatNumber, formatRupiah } from '@/lib/format';
import { AssembleStageModal } from '@/features/catalog/AssembleStageModal';
import { PlanStagesModal } from './PlanStagesModal';
import { QualityInspectionModal } from './QualityInspectionModal';

// --- Phase 4: Procurement (PO & GRN) ---
export function ProcurementPage() {
  const { can } = useAuth();
  const canCreate = can('procurement.po.create');
  const qc = useQueryClient();
  const { notify } = useToast();
  const [openNew, setOpenNew] = useState(false);
  const [selectedPoForGrn, setSelectedPoForGrn] = useState<any | null>(null);

  const pos = useQuery({
    queryKey: ['procurement', 'pos'],
    queryFn: async () => (await http.get<ResourceResponse<any[]>>('/purchase-orders')).data.data,
  });

  const updateStatus = useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) => {
      await http.patch(`/purchase-orders/${id}/status`, { status });
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['procurement', 'pos'] });
      notify('Status pembelian berhasil diperbarui.', 'success');
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message || 'Gagal memperbarui status PO.', 'error');
    },
  });

  return (
    <>
      <PageHeader
        eyebrow="Pengadaan"
        title="Purchase Order & Penerimaan Barang (GRN)"
        description="Pencatatan pengadaan komponen dan finished good fisik dengan nomor WhatsApp supplier, serta pencatatan penerimaan fisik barang ke rak gudang (GRN)."
        actions={
          canCreate && (
            <Button variant="primary" icon={<Plus size={16} />} onClick={() => setOpenNew(true)}>
              Buat Purchase Order
            </Button>
          )
        }
      />

      <section className="card">
        {pos.isLoading ? (
          <LoadingState />
        ) : !pos.data?.length ? (
          <EmptyState title="Belum ada Purchase Order" description="Buat PO baru untuk mencatat pengadaan komponen fisik atau finished good." />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Nomor PO</th>
                  <th>Supplier & WhatsApp</th>
                  <th>Tanggal Pesan</th>
                  <th>Item (Dipesan / Diterima)</th>
                  <th>Subtotal</th>
                  <th>Status Pembelian</th>
                  <th style={{ width: '180px', textAlign: 'center' }}>Aksi Status & Penerimaan</th>
                </tr>
              </thead>
              <tbody>
                {pos.data.map((po) => (
                  <tr key={po.id}>
                    <td>
                      <code className="font-bold text-[var(--color-primary-dark,#263f7d)]">{po.po_no}</code>
                    </td>
                    <td>
                      <div className="font-semibold text-[var(--color-primary-dark,#263f7d)]">
                        {po.supplier_name || 'Supplier Umum'}
                      </div>
                      {po.supplier_phone && (
                        <div className="text-xs font-mono text-[var(--color-text-muted)] flex items-center gap-1 mt-0.5">
                          <span className="font-semibold text-emerald-700">WA:</span> {po.supplier_phone}
                        </div>
                      )}
                    </td>
                    <td className="font-mono text-xs">{formatDate(po.order_date)}</td>
                    <td>
                      {po.items?.map((it: any) => (
                        <div key={it.id}>
                          <code>{it.product_code}</code>: {formatNumber(it.received_qty)} / {formatNumber(it.ordered_qty)} @ {formatRupiah(it.unit_price)}
                        </div>
                      ))}
                    </td>
                    <td className="font-mono">{formatRupiah(po.subtotal)}</td>
                    <td>
                      <StatusBadge status={po.status} />
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      <div style={{ display: 'flex', gap: '6px', justifyContent: 'center', flexWrap: 'wrap' }}>
                        {(po.status === 'DIPESAN' || po.status === 'DIKIRIM') && canCreate && (
                          <Button
                            size="sm"
                            variant="secondary"
                            icon={<PackageCheck size={13} />}
                            onClick={() => setSelectedPoForGrn(po)}
                            title="Terima barang dan posting fisik ke rak gudang (GRN)"
                          >
                            Terima Barang (GRN)
                          </Button>
                        )}
                        {po.status === 'DIPESAN' && (
                          <Button
                            size="sm"
                            variant="secondary"
                            loading={updateStatus.isPending && (updateStatus.variables as any)?.id === po.id}
                            onClick={() => updateStatus.mutate({ id: po.id, status: 'DIKIRIM' })}
                          >
                            Set Dikirim
                          </Button>
                        )}
                        {po.status === 'DIKIRIM' && (
                          <Button
                            size="sm"
                            variant="primary"
                            loading={updateStatus.isPending && (updateStatus.variables as any)?.id === po.id}
                            onClick={() => updateStatus.mutate({ id: po.id, status: 'SELESAI DITERIMA' })}
                          >
                            Selesai Terima
                          </Button>
                        )}
                        {(po.status === 'SELESAI DITERIMA' || po.status === 'APPROVED' || po.status === 'PARTIAL') && (
                          <span className="text-xs font-semibold text-emerald-700">Tuntas</span>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {openNew && (
        <NewPoModal
          onClose={() => setOpenNew(false)}
          onCreated={() => {
            qc.invalidateQueries({ queryKey: ['procurement', 'pos'] });
            notify('Purchase Order berhasil dibuat.', 'success');
            setOpenNew(false);
          }}
        />
      )}

      {selectedPoForGrn && (
        <GoodsReceiptModal
          po={selectedPoForGrn}
          onClose={() => setSelectedPoForGrn(null)}
          onReceived={() => {
            qc.invalidateQueries({ queryKey: ['procurement', 'pos'] });
            notify('Penerimaan Barang (GRN) berhasil diposting ke inventaris gudang.', 'success');
            setSelectedPoForGrn(null);
          }}
        />
      )}
    </>
  );
}

function NewPoModal({
  initialProductId,
  initialQty,
  initialPrice,
  onClose,
  onCreated,
}: {
  initialProductId?: number | string;
  initialQty?: number | string;
  initialPrice?: number | string;
  onClose: () => void;
  onCreated: () => void;
}) {
  const products = useProducts({ per_page: 100, product_type: 'PRODUCT' });
  const [form, setForm] = useState({
    supplier_name: '',
    supplier_phone: '',
    status: 'DIPESAN',
    order_date: new Date().toISOString().slice(0, 10),
    product_id: initialProductId ? String(initialProductId) : '',
    ordered_qty: initialQty ? String(initialQty) : '50',
    unit_price: initialPrice ? String(initialPrice) : '18500',
  });

  // Aturan Bisnis: Hanya produk berupa Komponen atau Finished Good dengan kategori fisik
  const eligibleProducts = (products.data?.data ?? []).filter((p) => {
    const isPhysical = p.physical_type === 'PHYSICAL';
    const catCode = p.category?.code?.toUpperCase() ?? '';
    const catName = p.category?.name?.toLowerCase() ?? '';
    const isEligibleCat =
      ['COMP', 'FG', 'WIP-MOD', 'WIP-BOX'].includes(catCode) ||
      catName.includes('komponen') ||
      catName.includes('finished good') ||
      catName.includes('spare part') ||
      (p.product_type === 'PRODUCT' && !['SRV', 'PROG'].includes(catCode));

    return isPhysical && isEligibleCat;
  });

  const save = useMutation({
    mutationFn: async () => {
      const prod = products.data?.data.find((p) => p.id === Number(form.product_id));
      await http.post('/purchase-orders', {
        supplier_name: form.supplier_name,
        supplier_phone: form.supplier_phone || null,
        status: form.status,
        order_date: form.order_date,
        items: [
          {
            product_id: Number(form.product_id),
            ordered_qty: form.ordered_qty,
            unit_price: form.unit_price,
            uom_id: prod?.base_uom_id ?? 1,
          },
        ],
      });
    },
    onSuccess: onCreated,
  });

  return (
    <Modal
      open
      title="Buat Purchase Order Baru"
      description="Hanya produk berupa Komponen atau Finished Good fisik yang dapat dipesan melalui Purchase Order & Penerimaan Barang (GRN)."
      onClose={onClose}
      footer={
        <>
          <Button onClick={onClose}>Batal</Button>
          <Button variant="primary" type="submit" form="po-form" loading={save.isPending}>
            Simpan PO
          </Button>
        </>
      }
    >
      <form
        id="po-form"
        onSubmit={(e: FormEvent) => {
          e.preventDefault();
          save.mutate();
        }}
      >
        <FormField label="Nama Supplier / Vendor" htmlFor="po-sup-name" required>
          <input
            id="po-sup-name"
            type="text"
            className="input"
            placeholder="Contoh: PT Elektronika Jaya / Toko Komponen Mandiri"
            value={form.supplier_name}
            onChange={(e) => setForm({ ...form, supplier_name: e.target.value })}
            required
          />
        </FormField>

        <div className="grid grid--2">
          <FormField label="Nomor WhatsApp Supplier (Opsional)" htmlFor="po-sup-phone">
            <input
              id="po-sup-phone"
              type="text"
              className="input"
              placeholder="Contoh: 081234567890"
              value={form.supplier_phone}
              onChange={(e) => setForm({ ...form, supplier_phone: e.target.value })}
            />
          </FormField>

          <FormField label="Status Pembelian" htmlFor="po-status" required>
            <select
              id="po-status"
              className="select"
              value={form.status}
              onChange={(e) => setForm({ ...form, status: e.target.value })}
            >
              <option value="DIPESAN">Dipesan</option>
              <option value="DIKIRIM">Dikirim</option>
              <option value="SELESAI DITERIMA">Selesai Diterima</option>
            </select>
          </FormField>
        </div>

        <FormField label="Tanggal Pemesanan" htmlFor="po-date" required>
          <input
            id="po-date"
            type="date"
            className="input"
            value={form.order_date}
            onChange={(e) => setForm({ ...form, order_date: e.target.value })}
            required
          />
        </FormField>

        <FormField
          label="Komponen / Finished Good Fisik"
          htmlFor="po-prod"
          required
          hint="Hanya produk berupa komponen atau finished good dengan kategori fisik"
        >
          <select
            id="po-prod"
            className="select"
            value={form.product_id}
            onChange={(e) => {
              const val = e.target.value;
              const prod = products.data?.data.find((p) => String(p.id) === val);
              setForm((prev) => ({
                ...prev,
                product_id: val,
                unit_price: prod?.reference_purchase_price ? String(prod.reference_purchase_price) : prev.unit_price,
              }));
            }}
            required
          >
            <option value="">Pilih komponen / finished good fisik</option>
            {eligibleProducts.map((p) => (
              <option key={p.id} value={p.id}>
                [{p.category?.name ?? 'Fisik'}] {p.code} — {p.name}
              </option>
            ))}
          </select>
        </FormField>

        <div className="grid grid--2">
          <FormField label="Kuantitas Pesan" htmlFor="po-qty" required>
            <input
              id="po-qty"
              type="number"
              step="any"
              className="input"
              value={form.ordered_qty}
              onChange={(e) => setForm({ ...form, ordered_qty: e.target.value })}
              required
            />
          </FormField>
          <FormField label="Harga Satuan (IDR)" htmlFor="po-price" required>
            <input
              id="po-price"
              type="number"
              step="any"
              className="input"
              value={form.unit_price}
              onChange={(e) => setForm({ ...form, unit_price: e.target.value })}
              required
            />
          </FormField>
        </div>
      </form>
    </Modal>
  );
}

function GoodsReceiptModal({ po, onClose, onReceived }: { po: any; onClose: () => void; onReceived: () => void }) {
  const locations = useLocations();
  const { notify } = useToast();

  const [receivingLocationId, setReceivingLocationId] = useState(
    locations.data?.[0]?.id ? String(locations.data[0].id) : ''
  );
  const [items, setItems] = useState<Array<{ po_item_id: number; product_code: string; product_name: string; remaining_qty: number; received_qty: string; accepted_qty: string; rejected_qty: string }>>(() => {
    return (po.items ?? []).map((it: any) => {
      const remaining = Math.max(0, Number(it.ordered_qty) - Number(it.received_qty));
      return {
        po_item_id: it.id,
        product_code: it.product_code,
        product_name: it.product_name,
        remaining_qty: remaining,
        received_qty: String(remaining > 0 ? remaining : Number(it.ordered_qty)),
        accepted_qty: String(remaining > 0 ? remaining : Number(it.ordered_qty)),
        rejected_qty: '0',
      };
    });
  });

  const receiveMutation = useMutation({
    mutationFn: async () => {
      if (!receivingLocationId) {
        throw new Error('Pilih lokasi rak gudang tempat penyimpanan barang.');
      }
      await http.post(
        `/purchase-orders/${po.id}/goods-receipts`,
        {
          receiving_location_id: Number(receivingLocationId),
          items: items.map((it) => ({
            purchase_order_item_id: it.po_item_id,
            received_qty: it.received_qty,
            accepted_qty: it.accepted_qty,
            rejected_qty: it.rejected_qty,
          })),
        },
        {
          headers: {
            'X-Idempotency-Key': newIdempotencyKey(),
          },
        }
      );
    },
    onSuccess: onReceived,
    onError: (err: any) => {
      notify(err?.response?.data?.message || err.message || 'Gagal memproses Penerimaan Barang (GRN).', 'error');
    },
  });

  return (
    <Modal
      open
      title={`Penerimaan Barang (GRN) — ${po.po_no}`}
      description="Penerimaan fisik barang komponen/finished good ke rak gudang dan pencatatan ledger inventaris."
      size="md"
      onClose={onClose}
      footer={
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, width: '100%' }}>
          <Button variant="secondary" onClick={onClose} disabled={receiveMutation.isPending}>
            Batal
          </Button>
          <Button
            variant="primary"
            loading={receiveMutation.isPending}
            onClick={() => receiveMutation.mutate()}
          >
            Posting Penerimaan (GRN)
          </Button>
        </div>
      }
    >
      <form onSubmit={(e) => { e.preventDefault(); receiveMutation.mutate(); }} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
        <FormField label="Lokasi Rak / Gudang Penerimaan" htmlFor="grn-loc" required hint="Lokasi fisik tempat komponen atau finished good disimpan">
          <select
            id="grn-loc"
            className="select"
            value={receivingLocationId}
            onChange={(e) => setReceivingLocationId(e.target.value)}
            required
          >
            <option value="">Pilih lokasi rak gudang penerimaan</option>
            {locations.data?.map((loc) => (
              <option key={loc.id} value={loc.id}>
                {loc.warehouse?.name ?? 'Gudang'} — {loc.code} ({loc.name})
              </option>
            ))}
          </select>
        </FormField>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
          <div style={{ fontWeight: 600, fontSize: '0.85rem', color: 'var(--color-primary-dark)' }}>
            Daftar Produk Fisik yang Diterima
          </div>
          {items.map((it, idx) => (
            <div
              key={it.po_item_id}
              style={{
                background: 'var(--color-surface-muted, #f8fafc)',
                border: '1px solid var(--color-border, #e2e8f0)',
                borderRadius: 8,
                padding: 12,
                display: 'flex',
                flexDirection: 'column',
                gap: 8,
              }}
            >
              <div>
                <div style={{ fontWeight: 700, fontSize: '0.9rem', color: 'var(--color-text-main)' }}>
                  <code>{it.product_code}</code> — {it.product_name}
                </div>
                <div style={{ fontSize: '0.75rem', color: 'var(--color-text-secondary)' }}>
                  Sisa Pesanan Belum Diterima: <strong>{it.remaining_qty} unit</strong>
                </div>
              </div>

              <div className="grid grid--2" style={{ gap: 8 }}>
                <FormField label="Kuantitas Diterima & Lolos QC" htmlFor={`grn-accepted-${idx}`} required>
                  <input
                    id={`grn-accepted-${idx}`}
                    type="number"
                    step="any"
                    min="0"
                    className="input font-mono font-bold"
                    value={it.accepted_qty}
                    onChange={(e) => {
                      const val = e.target.value;
                      setItems((prev) => {
                        const next = [...prev];
                        next[idx] = { ...next[idx], accepted_qty: val, received_qty: String(Number(val) + Number(next[idx].rejected_qty)) };
                        return next;
                      });
                    }}
                    required
                  />
                </FormField>

                <FormField label="Kuantitas Ditolak (QC Gagal)" htmlFor={`grn-rejected-${idx}`}>
                  <input
                    id={`grn-rejected-${idx}`}
                    type="number"
                    step="any"
                    min="0"
                    className="input font-mono"
                    value={it.rejected_qty}
                    onChange={(e) => {
                      const val = e.target.value;
                      setItems((prev) => {
                        const next = [...prev];
                        next[idx] = { ...next[idx], rejected_qty: val, received_qty: String(Number(next[idx].accepted_qty) + Number(val)) };
                        return next;
                      });
                    }}
                  />
                </FormField>
              </div>
            </div>
          ))}
        </div>
      </form>
    </Modal>
  );
}

// Modal Rincian Kebutuhan Komponen & Estimasi Biaya Pengadaan
function PlanRequirementsModal({
  plan,
  onClose,
  onOpenProcurement,
}: {
  plan: any;
  onClose: () => void;
  onOpenProcurement?: (item?: any) => void;
}) {
  const requirements = plan.required_components ?? [];
  const totalBudget = Number(plan.total_estimated_budget ?? 0);
  const targetQty = Number(plan.target_qty ?? 0);

  return (
    <Modal
      open
      title={`Kebutuhan Komponen & Estimasi Biaya — ${plan.plan_no}`}
      description={`Kebutuhan komponen dan estimasi biaya material untuk memproduksi target total ${formatNumber(targetQty)} unit produk.`}
      size="lg"
      onClose={onClose}
      footer={
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%', flexWrap: 'wrap', gap: 8 }}>
          <div style={{ fontSize: '0.85rem' }}>
            Total Estimasi Biaya Pengadaan: <strong style={{ color: 'var(--color-primary-dark, #263f7d)', fontSize: '1.05rem' }}>{formatRupiah(totalBudget)}</strong>
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <Button variant="secondary" onClick={onClose}>
              Tutup
            </Button>
            {onOpenProcurement && (
              <Button
                variant="primary"
                icon={<ShoppingCart size={14} />}
                onClick={() => {
                  onClose();
                  const shortageItem = requirements.find((r: any) => Number(r.shortage_qty) > 0) || requirements[0];
                  onOpenProcurement(shortageItem);
                }}
              >
                Lanjutkan ke Pengadaan (Buat PO)
              </Button>
            )}
          </div>
        </div>
      }
    >
      <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
        <div
          style={{
            padding: '12px 16px',
            background: 'var(--color-surface-muted, #f8fafc)',
            border: '1px solid var(--color-border, #e2e8f0)',
            borderRadius: 8,
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            flexWrap: 'wrap',
            gap: 10,
          }}
        >
          <div>
            <div style={{ fontSize: '0.75rem', color: 'var(--color-text-muted)', textTransform: 'uppercase', fontWeight: 600 }}>
              Target Produk Rencana Produksi
            </div>
            <div style={{ fontSize: '1.05rem', fontWeight: 700, color: 'var(--color-primary-dark, #263f7d)' }}>
              {plan.title} ({formatNumber(targetQty)} Unit)
            </div>
            <div style={{ fontSize: '0.8rem', color: 'var(--color-text-muted)', marginTop: 2 }}>
              Tanggal Rencana: {formatDate(plan.plan_date)} &bull; Jatuh Tempo: {formatDate(plan.due_date)}
            </div>
          </div>
          <div style={{ textAlign: 'right' }}>
            <div style={{ fontSize: '0.75rem', color: 'var(--color-text-muted)', textTransform: 'uppercase', fontWeight: 600 }}>
              Total Anggaran Komponen
            </div>
            <div style={{ fontSize: '1.2rem', fontWeight: 800, color: 'var(--color-primary, #3859ac)' }}>
              {formatRupiah(totalBudget)}
            </div>
            <div style={{ fontSize: '0.8rem', color: 'var(--color-text-muted)' }}>
              {requirements.length} jenis komponen dibutuhkan
            </div>
          </div>
        </div>

        {requirements.length === 0 ? (
          <div style={{ padding: 24, textAlign: 'center', color: 'var(--color-text-muted)' }}>
            Belum ada resep BOM aktif untuk produk pada rencana produksi ini, atau produk belum memiliki struktur komponen BOM.
          </div>
        ) : (
          <div className="table-wrap">
            <table className="table" style={{ fontSize: '0.825rem' }}>
              <thead>
                <tr>
                  <th>Kode & Jenis Komponen</th>
                  <th style={{ width: '90px' }}>Tipe</th>
                  <th style={{ width: '100px', textAlign: 'right' }}>Dibutuhkan</th>
                  <th style={{ width: '100px', textAlign: 'right' }}>Stok Gudang</th>
                  <th style={{ width: '100px', textAlign: 'right' }}>Kekurangan</th>
                  <th style={{ width: '110px', textAlign: 'right' }}>Harga Satuan</th>
                  <th style={{ width: '120px', textAlign: 'right' }}>Estimasi Biaya</th>
                  <th style={{ width: '80px', textAlign: 'center' }}>Pengadaan</th>
                </tr>
              </thead>
              <tbody>
                {requirements.map((comp: any) => {
                  const gross = Number(comp.gross_qty || 0);
                  const avail = Number(comp.available_qty || 0);
                  const short = Number(comp.shortage_qty || 0);
                  const isShort = short > 0;

                  return (
                    <tr key={comp.product_id} style={{ backgroundColor: isShort ? '#fffbeb' : undefined }}>
                      <td>
                        <div style={{ fontWeight: 600, color: 'var(--color-text-main)' }}>
                          <code>{comp.product_code}</code> &bull; {comp.product_name}
                        </div>
                      </td>
                      <td>
                        <span className="chip" style={{ fontSize: '0.7rem' }}>
                          {comp.item_type || 'KOMPONEN'}
                        </span>
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 700 }}>
                        {formatNumber(gross)} {comp.uom_code}
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace' }}>
                        <span style={{ color: avail >= gross ? '#15803d' : '#b45309', fontWeight: 600 }}>
                          {formatNumber(avail)} {comp.uom_code}
                        </span>
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace' }}>
                        {isShort ? (
                          <span style={{ color: '#b91c1c', fontWeight: 700 }}>
                            {formatNumber(short)} {comp.uom_code}
                          </span>
                        ) : (
                          <span style={{ color: '#15803d', fontSize: '0.75rem', fontWeight: 600 }}>Cukup</span>
                        )}
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace' }}>
                        {formatRupiah(comp.estimated_unit_cost || 0)}
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 700, color: 'var(--color-primary-dark, #263f7d)' }}>
                        {formatRupiah(comp.estimated_total_cost || 0)}
                      </td>
                      <td style={{ textAlign: 'center' }}>
                        {onOpenProcurement && (
                          <Button
                            size="sm"
                            variant={isShort ? 'primary' : 'secondary'}
                            onClick={() => {
                              onClose();
                              onOpenProcurement(comp);
                            }}
                            title="Buat PO pengadaan untuk komponen ini"
                          >
                            Beli
                          </Button>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </Modal>
  );
}

// --- Phase 5: Production Planning & MRP ---
export function ProductionPlanningPage() {
  const { can } = useAuth();
  const canCreate = can('production.plan.create');
  const qc = useQueryClient();
  const { notify } = useToast();
  const products = useProducts({ per_page: 100, product_type: 'PRODUCT' });
  const [mrpResult, setMrpResult] = useState<any | null>(null);
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [selectedPlanForStages, setSelectedPlanForStages] = useState<any | null>(null);
  const [selectedPlanForRequirements, setSelectedPlanForRequirements] = useState<any | null>(null);
  const [poInitialData, setPoInitialData] = useState<{ productId?: number | string; qty?: number | string; price?: number | string } | null>(null);
  const [assembleModalConfig, setAssembleModalConfig] = useState<{ isOpen: boolean; stageTitle: string; previousStageTitle: string; products: any[] } | null>(null);

  // Manual production plan form state
  const [planForm, setPlanForm] = useState({
    title: `Rencana Produksi — ${new Date().toISOString().slice(0, 10)}`,
    plan_date: new Date().toISOString().slice(0, 10),
    due_date: new Date(Date.now() + 14 * 86400_000).toISOString().slice(0, 10),
    priority: 'HIGH',
    project_name: '',
    items: [
      { product_id: '', target_qty: '50.0000', uom_id: '' },
    ],
  });

  const plans = useQuery({
    queryKey: ['production-plans'],
    queryFn: async () => (await http.get<ResourceResponse<any[]>>('/production-plans')).data.data,
  });

  const createPlanMutation = useMutation({
    mutationFn: async (payload: Record<string, unknown>) =>
      (await http.post<ResourceResponse<any>>('/production-plans', payload)).data.data,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['production-plans'] });
      notify('Rencana produksi berhasil dibuat dengan target kuantitas manual.', 'success');
      setIsCreateOpen(false);
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message ?? 'Gagal membuat rencana produksi.', 'error');
    },
  });

  const handleAddPlanItem = () => {
    const firstProd = products.data?.data?.[0];
    setPlanForm((prev) => ({
      ...prev,
      items: [
        ...prev.items,
        {
          product_id: firstProd ? String(firstProd.id) : '',
          target_qty: '25.0000',
          uom_id: firstProd ? String(firstProd.base_uom_id) : '',
        },
      ],
    }));
  };

  const handleRemovePlanItem = (idx: number) => {
    setPlanForm((prev) => ({
      ...prev,
      items: prev.items.filter((_, i) => i !== idx),
    }));
  };

  const handlePlanItemChange = (idx: number, field: string, value: string) => {
    setPlanForm((prev) => {
      const items = [...prev.items];
      items[idx] = { ...items[idx], [field]: value };
      if (field === 'product_id') {
        const prod = products.data?.data.find((p) => String(p.id) === value);
        if (prod) {
          items[idx].uom_id = String(prod.base_uom_id);
        }
      }
      return { ...prev, items };
    });
  };

  const handleSubmitPlan = (e: FormEvent) => {
    e.preventDefault();
    if (!planForm.title || planForm.items.length === 0) {
      notify('Judul dan minimal 1 produk wajib diisi.', 'error');
      return;
    }
    for (const it of planForm.items) {
      if (!it.product_id || Number(it.target_qty) <= 0) {
        notify('Pastikan semua baris produk dan target kuantitas diisi dengan benar.', 'error');
        return;
      }
    }

    createPlanMutation.mutate({
      title: planForm.title,
      plan_date: planForm.plan_date,
      due_date: planForm.due_date,
      priority: planForm.priority,
      project_name: planForm.project_name || null,
      items: planForm.items.map((it) => ({
        product_id: Number(it.product_id),
        target_qty: it.target_qty,
        uom_id: Number(it.uom_id),
      })),
    });
  };

  const runMrp = useMutation({
    mutationFn: async (planId: number) => (await http.post<ResourceResponse<any>>(`/production-plans/${planId}/mrp-runs`)).data.data,
    onSuccess: (res) => {
      setMrpResult(res);
      notify(`Kalkulasi MRP Run #${res.run_no} selesai.`, 'success');
    },
  });

  return (
    <>
      <PageHeader
        eyebrow="Rencana Produksi & MRP"
        title="Perencanaan Kebutuhan Material (MRP)"
        description="Atur target kuantitas produk secara manual untuk perencanaan produksi. Kalkulasi kebutuhan bersih = max(0, Kebutuhan Kotor + Safety Stock − Stok Tersedia − Incoming PO Terkonfirmasi)."
        actions={
          canCreate && (
            <Button
              variant="primary"
              icon={<Plus size={16} />}
              onClick={() => {
                const mlk = products.data?.data.find((p) => p.code === '1001') ?? products.data?.data?.[0];
                setPlanForm({
                  title: `Rencana Produksi — ${new Date().toISOString().slice(0, 10)}`,
                  plan_date: new Date().toISOString().slice(0, 10),
                  due_date: new Date(Date.now() + 14 * 86400_000).toISOString().slice(0, 10),
                  priority: 'HIGH',
                  project_name: '',
                  items: [
                    {
                      product_id: mlk ? String(mlk.id) : '',
                      target_qty: '50.0000',
                      uom_id: mlk ? String(mlk.base_uom_id) : '1',
                    },
                  ],
                });
                setIsCreateOpen(true);
              }}
            >
              Buat Rencana Produksi
            </Button>
          )
        }
      />

      <section className="card" style={{ marginBottom: 24 }}>
        {plans.isLoading ? (
          <LoadingState />
        ) : !plans.data?.length ? (
          <EmptyState
            title="Belum ada rencana produksi"
            description="Klik tombol 'Buat Rencana Produksi' di atas untuk mengatur produk dan target kuantitas secara manual."
          />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Judul Rencana & Kode</th>
                  <th>Target Produk (Kuantitas Unit)</th>
                  <th>Kebutuhan Komponen & Biaya</th>
                  <th>Tahapan Progress</th>
                  <th>Jatuh Tempo</th>
                  <th style={{ width: '170px', textAlign: 'center' }}>Aksi Pengadaan & MRP</th>
                </tr>
              </thead>
              <tbody>
                {plans.data.map((plan) => (
                  <tr key={plan.id}>
                    <td>
                      <div className="font-medium">{plan.title}</div>
                      <code className="text-xs">{plan.plan_no}</code>
                      {plan.project_name && (
                        <div className="text-xs text-[var(--color-text-muted)] mt-0.5">Project: {plan.project_name}</div>
                      )}
                    </td>
                    <td>
                      <div className="space-y-1">
                        {plan.items?.map((it: any) => (
                          <div key={it.id} className="text-xs py-0.5 border-b border-[var(--color-border-subtle)] last:border-0">
                            <code>{it.product_code}</code> · {it.product_name}
                            <strong className="ml-1 text-[var(--color-text-main)] font-mono">
                              ({formatNumber(it.target_qty)} unit)
                            </strong>
                          </div>
                        ))}
                      </div>

                      {/* Evaluasi Output Tahapan terhadap Target Produksi */}
                      {Number(plan.target_qty ?? 0) > 0 && (
                        <div className="mt-1.5 flex items-center gap-1.5 flex-wrap">
                          {plan.output_category === 'KURANG' && (
                            <span className="chip text-xs" style={{ background: '#fef3c7', color: '#b45309', fontWeight: 600 }} title="Hasil output tahapan kurang dari target rencana produksi">
                              Kurang ({formatNumber(plan.output_variance ?? 0)} unit)
                            </span>
                          )}
                          {plan.output_category === 'LEBIH' && (
                            <span className="chip text-xs" style={{ background: '#e0e7ff', color: '#4338ca', fontWeight: 600 }} title="Hasil output tahapan melebihi target rencana produksi">
                              Lebih (+{formatNumber(plan.output_variance ?? 0)} unit)
                            </span>
                          )}
                          {plan.output_category === 'SESUAI' && (
                            <span className="chip text-xs" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 600 }} title="Hasil output tahapan sesuai tepat target rencana produksi">
                              Sesuai Target ({formatNumber(plan.produced_qty ?? 0)} unit)
                            </span>
                          )}
                          {!plan.output_category && (
                            <span className="text-[11px] text-[var(--color-text-muted)]">
                              {Number(plan.produced_qty ?? 0) > 0 ? `${formatNumber(plan.produced_qty)} unit dibuat` : 'Output tahapan belum diisi'}
                            </span>
                          )}
                        </div>
                      )}
                    </td>
                    <td>
                      <div className="text-xs flex flex-col gap-1">
                        <div>
                          <span className="text-[var(--color-text-muted)]">Komponen: </span>
                          <strong className="text-[var(--color-text-main)]">{plan.required_components?.length ?? 0} jenis</strong>
                        </div>
                        <div>
                          <span className="text-[var(--color-text-muted)]">Estimasi Biaya: </span>
                          <strong className="text-[var(--color-primary-dark, #263f7d)] font-mono">
                            {formatRupiah(plan.total_estimated_budget ?? 0)}
                          </strong>
                        </div>
                        <div className="pt-0.5">
                          <Button
                            size="sm"
                            variant="secondary"
                            icon={<Boxes size={13} />}
                            onClick={() => setSelectedPlanForRequirements(plan)}
                            title="Lihat rincian jenis komponen, jumlah kebutuhan, dan estimasi biaya"
                          >
                            Rincian Komponen
                          </Button>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div className="flex flex-col gap-1.5" style={{ minWidth: 150 }}>
                        <div className="flex items-center justify-between text-xs">
                          <span className="font-semibold text-[var(--color-text-main)]">
                            {Number(plan.progress_percentage ?? 0).toFixed(0)}%
                          </span>
                          <span className="text-[var(--color-text-muted)]">
                            {plan.stages?.filter((s: any) => s.status === 'COMPLETED').length ?? 0}/{plan.stages?.length ?? 0} tahap
                          </span>
                        </div>
                        <div style={{ background: 'var(--color-border)', borderRadius: 9999, height: 6, overflow: 'hidden' }}>
                          <div
                            style={{
                              background: 'var(--color-primary)',
                              width: `${Math.min(100, Math.max(0, Number(plan.progress_percentage ?? 0)))}%`,
                              height: '100%',
                              borderRadius: 9999,
                              transition: 'width 0.3s ease',
                            }}
                          />
                        </div>
                        <div className="pt-1">
                          <Button
                            size="sm"
                            variant="secondary"
                            icon={<ListOrdered size={13} />}
                            onClick={() => setSelectedPlanForStages(plan)}
                          >
                            Kelola Tahapan
                          </Button>
                        </div>
                      </div>
                    </td>
                    <td className="text-xs font-mono">{formatDate(plan.due_date)}</td>
                    <td style={{ textAlign: 'center' }}>
                      <div style={{ display: 'flex', flexDirection: 'column', gap: 6, alignItems: 'center' }}>
                        {canCreate && (
                          <Button
                            variant="secondary"
                            size="sm"
                            icon={<ShoppingCart size={13} />}
                            onClick={() => setSelectedPlanForRequirements(plan)}
                            title="Lanjutkan ke pengadaan komponen dari rencana produksi ini"
                          >
                            Pengadaan PO
                          </Button>
                        )}
                        {canCreate && (
                          <Button
                            variant="primary"
                            size="sm"
                            icon={<Calculator size={13} />}
                            onClick={() => runMrp.mutate(plan.id)}
                            loading={runMrp.isPending}
                          >
                            Hitung MRP
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {/* Alur Perakitan Fisik Bertahap: Komponen -> Modul -> Box -> Finished Good */}
      <section className="card" style={{ marginBottom: 24 }}>
        <div className="card__header" style={{ marginBottom: 16 }}>
          <div>
            <h3 className="card__title" style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <Hammer size={18} className="text-[var(--color-primary,#3859ac)]" />
              Alur Perakitan Bertahap: Stok Komponen → WIP Modul → WIP Box → Finished Good (FG)
            </h3>
            <p className="card__subtitle">
              Setelah pengadaan komponen diterima di gudang (GRN), proses pengerjaan bertahap mengubah komponen menjadi modul, box, hingga unit Finished Good yang siap disimpan dan disewakan ke sekolah.
            </p>
          </div>
        </div>

        <div className="grid grid--4" style={{ gap: 14 }}>
          {/* Step 1: Stok Komponen */}
          <div
            style={{
              padding: 14,
              borderRadius: 8,
              border: '1px solid var(--color-border)',
              background: '#f8fafc',
              display: 'flex',
              flexDirection: 'column',
              gap: 8,
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <span className="chip" style={{ background: '#e2e8f0', color: '#334155', fontWeight: 700, fontSize: '0.75rem' }}>
                1. Stok Komponen
              </span>
              <PackageCheck size={16} className="text-emerald-600" />
            </div>
            <div style={{ fontWeight: 700, fontSize: '0.9rem', color: 'var(--color-text-main)' }}>
              Komponen Fisik Gudang
            </div>
            <p style={{ fontSize: '0.75rem', color: 'var(--color-text-muted)', margin: 0 }}>
              Diterima dari PO supplier via GRN dan tersimpan di rak gudang bahan baku.
            </p>
            <div style={{ marginTop: 'auto', paddingTop: 6 }}>
              <span className="text-xs text-emerald-700 font-semibold flex items-center gap-1">
                <CheckCircle2 size={13} /> Siap dirakit
              </span>
            </div>
          </div>

          {/* Step 2: WIP Modul */}
          <div
            style={{
              padding: 14,
              borderRadius: 8,
              border: '1px solid var(--color-border)',
              background: '#ffffff',
              display: 'flex',
              flexDirection: 'column',
              gap: 8,
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <span className="chip" style={{ background: '#e0f2fe', color: '#0369a1', fontWeight: 700, fontSize: '0.75rem' }}>
                2. Sub-Assembly Modul
              </span>
              <Layers size={16} className="text-sky-600" />
            </div>
            <div style={{ fontWeight: 700, fontSize: '0.9rem', color: 'var(--color-text-main)' }}>
              Perakitan Modul (WIP)
            </div>
            <p style={{ fontSize: '0.75rem', color: 'var(--color-text-muted)', margin: 0 }}>
              Merakit komponen sensor, resistor, dan IC menjadi Modul M01 / M02.
            </p>
            <div style={{ marginTop: 'auto', paddingTop: 6 }}>
              {canCreate && (
                <Button
                  size="sm"
                  variant="secondary"
                  icon={<Hammer size={13} />}
                  onClick={() => {
                    const modProds = (products.data?.data ?? []).filter((p) => p.category?.code === 'WIP-MOD');
                    setAssembleModalConfig({
                      isOpen: true,
                      stageTitle: 'Perakitan WIP Modul (Tahap 1)',
                      previousStageTitle: 'Stok Komponen Fisik',
                      products: modProds.length > 0 ? modProds : (products.data?.data ?? []),
                    });
                  }}
                >
                  Rakit Modul
                </Button>
              )}
            </div>
          </div>

          {/* Step 3: WIP Box */}
          <div
            style={{
              padding: 14,
              borderRadius: 8,
              border: '1px solid var(--color-border)',
              background: '#ffffff',
              display: 'flex',
              flexDirection: 'column',
              gap: 8,
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <span className="chip" style={{ background: '#fef3c7', color: '#b45309', fontWeight: 700, fontSize: '0.75rem' }}>
                3. Assembly Box
              </span>
              <Boxes size={16} className="text-amber-600" />
            </div>
            <div style={{ fontWeight: 700, fontSize: '0.9rem', color: 'var(--color-text-main)' }}>
              Perakitan Box (WIP)
            </div>
            <p style={{ fontSize: '0.75rem', color: 'var(--color-text-muted)', margin: 0 }}>
              Menggabungkan Modul M01/M02 dan komponen mekanik ke dalam Enclosure Box B01.
            </p>
            <div style={{ marginTop: 'auto', paddingTop: 6 }}>
              {canCreate && (
                <Button
                  size="sm"
                  variant="secondary"
                  icon={<Hammer size={13} />}
                  onClick={() => {
                    const boxProds = (products.data?.data ?? []).filter((p) => p.category?.code === 'WIP-BOX');
                    setAssembleModalConfig({
                      isOpen: true,
                      stageTitle: 'Perakitan WIP Box (Tahap 2)',
                      previousStageTitle: 'WIP Modul M01/M02',
                      products: boxProds.length > 0 ? boxProds : (products.data?.data ?? []),
                    });
                  }}
                >
                  Rakit Box
                </Button>
              )}
            </div>
          </div>

          {/* Step 4: Finished Good */}
          <div
            style={{
              padding: 14,
              borderRadius: 8,
              border: '2px solid var(--color-primary-dark, #263f7d)',
              background: '#f8fafc',
              display: 'flex',
              flexDirection: 'column',
              gap: 8,
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <span className="chip" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 700, fontSize: '0.75rem' }}>
                4. Finished Good (FG)
              </span>
              <CheckCircle2 size={16} className="text-emerald-600" />
            </div>
            <div style={{ fontWeight: 700, fontSize: '0.9rem', color: 'var(--color-primary-dark, #263f7d)' }}>
              Finalisasi Finished Good MLK
            </div>
            <p style={{ fontSize: '0.75rem', color: 'var(--color-text-muted)', margin: 0 }}>
              Penyelesaian akhir Box menjadi Finished Good MLK (1001) yang tersimpan di gudang & siap disewa.
            </p>
            <div style={{ marginTop: 'auto', paddingTop: 6 }}>
              {canCreate && (
                <Button
                  size="sm"
                  variant="primary"
                  icon={<Hammer size={13} />}
                  onClick={() => {
                    const fgProds = (products.data?.data ?? []).filter((p) => p.category?.code === 'FG' || p.code === '1001');
                    setAssembleModalConfig({
                      isOpen: true,
                      stageTitle: 'Perakitan Finished Good MLK (Tahap Akhir)',
                      previousStageTitle: 'WIP Box B01',
                      products: fgProds.length > 0 ? fgProds : (products.data?.data ?? []),
                    });
                  }}
                >
                  Rakit Finished Good
                </Button>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* Modal Buat Rencana Produksi dengan Kuantitas Manual */}
      <Modal
        isOpen={isCreateOpen}
        onClose={() => setIsCreateOpen(false)}
        title="Buat Rencana Produksi (Atur Kuantitas Manual)"
        description="Tentukan produk yang akan diproduksi dan tentukan kuantitas target secara manual untuk setiap item."
        size="lg"
        footer={
          <div className="flex gap-2 justify-end w-full">
            <Button variant="secondary" onClick={() => setIsCreateOpen(false)}>
              Batal
            </Button>
            <Button
              variant="primary"
              onClick={handleSubmitPlan}
              loading={createPlanMutation.isPending}
            >
              Simpan Rencana Produksi
            </Button>
          </div>
        }
      >
        <form onSubmit={handleSubmitPlan} className="space-y-4">
          <FormField label="Judul Rencana Produksi" htmlFor="plan-title" required>
            <input
              id="plan-title"
              type="text"
              className="input"
              value={planForm.title}
              onChange={(e) => setPlanForm({ ...planForm, title: e.target.value })}
              required
            />
          </FormField>

          <div className="grid grid--3">
            <FormField label="Tanggal Rencana" htmlFor="plan-date" required>
              <input
                id="plan-date"
                type="date"
                className="input"
                value={planForm.plan_date}
                onChange={(e) => setPlanForm({ ...planForm, plan_date: e.target.value })}
                required
              />
            </FormField>

            <FormField label="Tanggal Jatuh Tempo" htmlFor="plan-due" required>
              <input
                id="plan-due"
                type="date"
                className="input"
                value={planForm.due_date}
                onChange={(e) => setPlanForm({ ...planForm, due_date: e.target.value })}
                required
              />
            </FormField>

            <FormField label="Prioritas" htmlFor="plan-prio">
              <select
                id="plan-prio"
                className="select"
                value={planForm.priority}
                onChange={(e) => setPlanForm({ ...planForm, priority: e.target.value })}
              >
                <option value="LOW">LOW</option>
                <option value="NORMAL">NORMAL</option>
                <option value="HIGH">HIGH</option>
                <option value="URGENT">URGENT</option>
              </select>
            </FormField>
          </div>

          <FormField label="Nama Proyek / Catatan Tambahan (Opsional)" htmlFor="plan-proj">
            <input
              id="plan-proj"
              type="text"
              className="input"
              placeholder="Mis. Batch Sekolah Binaan Semester Ganjil"
              value={planForm.project_name}
              onChange={(e) => setPlanForm({ ...planForm, project_name: e.target.value })}
            />
          </FormField>

          <div>
            <div className="flex justify-between items-center mb-2">
              <label className="font-semibold text-sm text-[var(--color-text-main)]">
                Daftar Produk & Kuantitas Target Manual:
              </label>
              <Button
                type="button"
                variant="secondary"
                size="sm"
                icon={<Plus size={14} />}
                onClick={handleAddPlanItem}
              >
                Tambah Produk Target
              </Button>
            </div>

            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th>Produk Target</th>
                    <th style={{ width: '160px' }}>Target Kuantitas (Manual)</th>
                    <th style={{ width: '50px' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {planForm.items.map((row, idx) => (
                    <tr key={idx}>
                      <td>
                        <select
                          className="select select--sm w-full"
                          value={row.product_id}
                          onChange={(e) => handlePlanItemChange(idx, 'product_id', e.target.value)}
                        >
                          <option value="">Pilih produk target...</option>
                          {products.data?.data
                            .filter((p) => p.is_manufactured || p.category?.code === 'FG' || p.category?.code === 'WIP-BOX' || p.category?.code === 'WIP-MOD')
                            .map((p) => (
                              <option key={p.id} value={p.id}>
                                [{p.code}] {p.name} ({p.category?.name ?? 'Produk'})
                              </option>
                            ))}
                        </select>
                      </td>
                      <td>
                        <input
                          type="number"
                          step="1"
                          min="1"
                          className="input input--sm w-full font-mono font-bold"
                          placeholder="Jumlah"
                          value={row.target_qty}
                          onChange={(e) => handlePlanItemChange(idx, 'target_qty', e.target.value)}
                        />
                      </td>
                      <td>
                        {planForm.items.length > 1 && (
                          <Button
                            variant="ghost"
                            size="sm"
                            icon={<Trash2 size={14} className="text-red-500" />}
                            onClick={() => handleRemovePlanItem(idx)}
                          />
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </form>
      </Modal>

      {mrpResult && (
        <section className="card">
          <div className="card__header">
            <h2 className="card__title">Hasil Perhitungan Kebutuhan Kotor Material (MRP Run #{mrpResult.run_no})</h2>
            <span>
              Estimasi Anggaran Material: <strong>{formatRupiah(mrpResult.estimated_material_budget)}</strong>
            </span>
          </div>
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Komponen / Material Bahan</th>
                  <th style={{ width: '220px', textAlign: 'right' }}>Kebutuhan Kotor (Gross Requirement)</th>
                </tr>
              </thead>
              <tbody>
                {mrpResult.items.map((row: any) => (
                  <tr key={row.product_id}>
                    <td>
                      <code>{row.product_code}</code> · <strong>{row.product_name}</strong>
                    </td>
                    <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 800, fontSize: '1rem', color: 'var(--color-primary, #3859ac)' }}>
                      {formatNumber(row.gross_requirement)} unit
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      )}

      {selectedPlanForStages && (
        <PlanStagesModal
          plan={selectedPlanForStages}
          isOpen={Boolean(selectedPlanForStages)}
          onClose={() => {
            setSelectedPlanForStages(null);
            qc.invalidateQueries({ queryKey: ['production-plans'] });
          }}
          canManage={canCreate}
        />
      )}

      {selectedPlanForRequirements && (
        <PlanRequirementsModal
          plan={selectedPlanForRequirements}
          onClose={() => setSelectedPlanForRequirements(null)}
          onOpenProcurement={(item) => {
            setPoInitialData({
              productId: item?.product_id,
              qty: item?.shortage_qty && Number(item.shortage_qty) > 0 ? String(item.shortage_qty) : String(item?.gross_qty || 50),
              price: item?.estimated_unit_cost ? String(item.estimated_unit_cost) : undefined,
            });
          }}
        />
      )}

      {poInitialData && (
        <NewPoModal
          initialProductId={poInitialData.productId}
          initialQty={poInitialData.qty}
          initialPrice={poInitialData.price}
          onClose={() => setPoInitialData(null)}
          onCreated={() => {
            setPoInitialData(null);
            qc.invalidateQueries({ queryKey: ['procurement', 'pos'] });
            qc.invalidateQueries({ queryKey: ['production-plans'] });
            notify('Purchase order pengadaan komponen berhasil dibuat.', 'success');
          }}
        />
      )}

      {assembleModalConfig?.isOpen && (
        <AssembleStageModal
          isOpen={assembleModalConfig.isOpen}
          onClose={() => setAssembleModalConfig(null)}
          products={assembleModalConfig.products}
          stageTitle={assembleModalConfig.stageTitle}
          previousStageTitle={assembleModalConfig.previousStageTitle}
          onSuccessAssemble={() => {
            setAssembleModalConfig(null);
            qc.invalidateQueries({ queryKey: ['production-plans'] });
            qc.invalidateQueries({ queryKey: ['inventory'] });
            notify('Perakitan tahap berhasil diposting. Stok bertambah di inventaris!', 'success');
          }}
        />
      )}
    </>
  );
}

// --- Phase 6: Production Execution (WIP & Work Orders) ---
export function ProductionExecutionPage() {
  const orders = useQuery({
    queryKey: ['production-orders'],
    queryFn: async () => (await http.get<ResourceResponse<any[]>>('/production-orders')).data.data,
  });

  return (
    <>
      <PageHeader
        eyebrow="Produksi WIP"
        title="Eksekusi Production Order & Tahap Work Order"
        description="Setiap Production Order menyimpan snapshot BOM yang tidak berubah saat BOM master diperbarui, dan melacak tahap WIP Modul → WIP Box → Finished Good."
      />
      <section className="card">
        {orders.isLoading ? (
          <LoadingState />
        ) : !orders.data?.length ? (
          <EmptyState title="Belum ada Production Order" description="Rilis Production Order dari halaman Rencana Produksi." />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Kode Eksekusi</th>
                  <th>Produk</th>
                  <th>Target vs Good Output</th>
                  <th>Tahap Work Order</th>
                  <th>Biaya Material Aktual</th>
                  <th>Biaya / Unit Good</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {orders.data.map((o) => (
                  <tr key={o.id}>
                    <td>
                      <code>{o.order_no}</code>
                    </td>
                    <td>
                      <code>{o.product_code}</code> · {o.product_name}
                    </td>
                    <td>
                      Good: <strong>{formatNumber(o.total_good_qty ?? 0)}</strong> / Target: {formatNumber(o.target_qty)} (Reject:{' '}
                      {formatNumber(o.total_reject_qty ?? 0)})
                    </td>
                    <td>
                      {o.work_orders?.map((wo: any) => (
                        <span key={wo.id} className="chip">
                          #{wo.sequence} {wo.stage_code} ({wo.status})
                        </span>
                      ))}
                    </td>
                    <td>{formatRupiah(o.total_material_cost)}</td>
                    <td>{formatRupiah(o.material_cost_per_good_unit)}</td>
                    <td>
                      <StatusBadge status={o.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </>
  );
}

// --- Phase 7: Vendor / GAP & Quality Control ---
export function VendorManagementPage() {
  const { can } = useAuth();
  const canManage = can('vendor.manage');
  const qc = useQueryClient();
  const { notify } = useToast();
  const vendors = usePartners('vendors');
  const locations = useLocations();
  const products = useProducts({ per_page: 100, product_type: 'PRODUCT' });

  const orders = useQuery({
    queryKey: ['vendor-orders'],
    queryFn: async () => (await http.get<ResourceResponse<any[]>>('/vendor-orders')).data.data,
  });

  const dispatchSample = useMutation({
    mutationFn: async () => {
      const vendor = vendors.data?.[0];
      const rackA1 = locations.data?.find((l) => l.code === 'POP.L3R3A1');
      const gapLoc = locations.data?.find((l) => l.is_external);
      const comp = products.data?.data.find((p) => p.code === 'SP.PART.ER.016');
      if (!vendor || !rackA1 || !gapLoc || !comp) return;
      await http.post(
        '/vendor-orders',
        {
          vendor_id: vendor.id,
          warehouse_id: rackA1.warehouse_id,
          source_location_id: rackA1.id,
          vendor_location_id: gapLoc.id,
          items: [{ product_id: comp.id, sent_quantity: '10', uom_id: comp.base_uom_id }],
        },
        { headers: { 'Idempotency-Key': newIdempotencyKey() } },
      );
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['vendor-orders'] });
      qc.invalidateQueries({ queryKey: ['inventory'] });
      notify('Pengiriman ke vendor GAP diposting; stok berpindah ke lokasi eksternal AT_VENDOR.', 'success');
    },
  });

  return (
    <>
      <PageHeader
        eyebrow="Pengadaan / GAP"
        title="Pemrosesan Vendor GAP & Rekonsiliasi Kuantitas"
        description="Barang yang dikirim ke GAP tetap tercatat sebagai persediaan milik Erlass di lokasi eksternal (AT_VENDOR) dan direkonsiliasi: Dikirim = Selesai + Outstanding + Rusak."
        actions={
          canManage && (
            <Button variant="primary" icon={<Plus size={16} />} onClick={() => dispatchSample.mutate()} loading={dispatchSample.isPending}>
              Kirim 10 Unit ke Vendor GAP
            </Button>
          )
        }
      />

      <section className="card">
        {orders.isLoading ? (
          <LoadingState />
        ) : !orders.data?.length ? (
          <EmptyState title="Belum ada pengiriman vendor GAP" />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>No. Dokumen GAP</th>
                  <th>Vendor</th>
                  <th>Barang</th>
                  <th>Dikirim</th>
                  <th>Diterima Baik</th>
                  <th>Outstanding di Vendor</th>
                  <th>Rusak</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {orders.data.map((vo) =>
                  vo.items?.map((it: any) => (
                    <tr key={it.id}>
                      <td>
                        <code>{vo.vendor_order_no}</code>
                      </td>
                      <td>{vo.vendor_name}</td>
                      <td>
                        <code>{it.product_code}</code> · {it.product_name}
                      </td>
                      <td>{formatNumber(it.sent_quantity)}</td>
                      <td>{formatNumber(it.completed_quantity)}</td>
                      <td>
                        <strong>{formatNumber(it.outstanding_quantity)}</strong>
                      </td>
                      <td>{formatNumber(it.damaged_quantity)}</td>
                      <td>
                        <StatusBadge status={vo.status} />
                      </td>
                    </tr>
                  )),
                )}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </>
  );
}

export function QualityControlPage() {
  const { can } = useAuth();
  const canPerform = can('qc.perform');
  const [isModalOpen, setIsModalOpen] = useState(false);

  const inspections = useQuery({
    queryKey: ['qc-inspections'],
    queryFn: async () => (await http.get<ResourceResponse<any[]>>('/quality-inspections')).data.data,
  });

  return (
    <>
      <PageHeader
        eyebrow="Quality Control"
        title="Inspeksi Kualitas & Selisih Kelengkapan"
        description="Pengecekan kelengkapan Finished Good MLK (Box, Modul, Komponen), baik dalam bentuk satuan (pengiriman cacat/kurang) maupun pengembalian sewa batch sekolah beserta perhitungan otomatis klaim ganti rugi."
        actions={
          canPerform && (
            <Button variant="primary" icon={<Plus size={16} />} onClick={() => setIsModalOpen(true)}>
              + Mulai Inspeksi QC
            </Button>
          )
        }
      />
      <section className="card">
        {inspections.isLoading ? (
          <LoadingState />
        ) : !inspections.data?.length ? (
          <EmptyState
            title="Belum ada catatan inspeksi QC"
            description="Klik tombol '+ Mulai Inspeksi QC' untuk melakukan checklist kelengkapan produk Finished Good atau penerimaan kembali sewa sekolah."
          />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th style={{ width: '130px' }}>No. Inspeksi</th>
                  <th style={{ width: '130px' }}>Tipe QC</th>
                  <th>Produk Target</th>
                  <th>Sekolah / Customer</th>
                  <th>Inspektor</th>
                  <th>Temuan Selisih Komponen</th>
                  <th style={{ width: '130px', textAlign: 'right' }}>Klaim Ganti Rugi</th>
                  <th style={{ width: '110px' }}>Status</th>
                </tr>
              </thead>
              <tbody>
                {inspections.data.map((qi) => (
                  <tr key={qi.id}>
                    <td>
                      <code>{qi.inspection_no}</code>
                    </td>
                    <td>
                      <span
                        className="chip"
                        style={{
                          background: qi.inspection_type === 'RENTAL_RETURN_SCHOOL' ? '#e0f2fe' : '#fef3c7',
                          color: qi.inspection_type === 'RENTAL_RETURN_SCHOOL' ? '#0369a1' : '#92400e',
                          fontWeight: 600,
                          fontSize: '0.75rem',
                        }}
                      >
                        {qi.inspection_type === 'RENTAL_RETURN_SCHOOL' ? 'Sewa Sekolah' : 'Satuan Cacat'}
                      </span>
                    </td>
                    <td>{qi.product_code ? `${qi.product_code} · ${qi.product_name}` : '—'}</td>
                    <td>
                      {qi.customer_name ? (
                        <strong>{qi.customer_name}</strong>
                      ) : (
                        <span className="table__muted">—</span>
                      )}
                    </td>
                    <td>{qi.inspector_name}</td>
                    <td>
                      {qi.discrepancies?.length ? (
                        <div className="space-y-1 text-xs">
                          {qi.discrepancies.map((d: any) => (
                            <div key={d.id} className="flex items-center gap-1.5">
                              <span style={{ color: '#b91c1c', fontWeight: 700 }}>
                                -{formatNumber(d.quantity)}
                              </span>
                              <code>{d.product_code}</code> · {d.product_name}
                              <span className="table__muted">
                                ({d.is_chargeable ? 'Denda' : 'Bebas'} {formatRupiah(d.estimated_claim_amount)})
                              </span>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <span className="chip" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 600 }}>
                          Lengkap 100%
                        </span>
                      )}
                    </td>
                    <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 700, color: Number(qi.total_claim_amount) > 0 ? '#b91c1c' : '#15803d' }}>
                      {formatRupiah(qi.total_claim_amount ?? 0)}
                    </td>
                    <td>
                      <StatusBadge status={qi.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {isModalOpen && (
        <QualityInspectionModal
          isOpen={isModalOpen}
          onClose={() => setIsModalOpen(false)}
          onSuccess={() => inspections.refetch()}
        />
      )}
    </>
  );
}

// --- Phase 8: Rental & Distribution ---
export function RentalPage() {
  const rentals = useQuery({
    queryKey: ['rental-orders'],
    queryFn: async () => (await http.get<ResourceResponse<any[]>>('/rental-orders')).data.data,
  });

  return (
    <>
      <PageHeader
        eyebrow="Rental & Peminjaman"
        title="Pesanan Sewa / Pinjam Unit Serialized & Kelengkapan Kit"
        description="Melacak unit/serial box MLK, mencegah pemesanan ganda pada tanggal bertumpukan, dan menahan barang kembali di status QUARANTINE hingga lulus QC."
      />
      <section className="card">
        {rentals.isLoading ? (
          <LoadingState />
        ) : !rentals.data?.length ? (
          <EmptyState title="Belum ada pesanan rental / peminjaman" />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Kode Rental</th>
                  <th>Jenis</th>
                  <th>Customer / Sekolah</th>
                  <th>Unit & Nomor Serial</th>
                  <th>Periode</th>
                  <th>PIC / GL</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {rentals.data.map((r) => (
                  <tr key={r.id}>
                    <td>
                      <code>{r.rental_no}</code>
                    </td>
                    <td>
                      <span className="chip">{r.transaction_kind}</span>
                    </td>
                    <td>{r.school_name ?? r.customer_name}</td>
                    <td>
                      {r.items?.map((it: any) => (
                        <div key={it.id}>
                          <code>{it.product_code}</code> · SN: <code>{it.serial_number}</code>
                        </div>
                      ))}
                    </td>
                    <td>
                      {formatDate(r.start_date)} – {formatDate(r.expected_return_date)}
                    </td>
                    <td>{r.group_leader ?? r.sales_pic ?? '—'}</td>
                    <td>
                      <StatusBadge status={r.status} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </>
  );
}

export function DistributionPage() {
  const qc = useQueryClient();
  const { notify } = useToast();
  const { can } = useAuth();
  const canDispatch = can('distribution.dispatch');

  const shipments = useQuery({
    queryKey: ['shipments'],
    queryFn: async () => (await http.get<ResourceResponse<any[]>>('/shipments')).data.data,
  });

  const updateStatus = useMutation({
    mutationFn: async ({ id, status }: { id: number; status: string }) => {
      await http.patch(`/shipments/${id}/status`, { status });
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['shipments'] });
      notify('Status pengiriman berhasil diubah menjadi Selesai.', 'success');
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message || 'Gagal mengubah status pengiriman.', 'error');
    },
  });

  return (
    <>
      <PageHeader
        eyebrow="Serah Terima & Distribusi"
        title="Delivery Order (DO) & Pengiriman"
        description="Pencatatan pengiriman ke sekolah/customer, proyek sewa MLK, atau internal dengan pelacakan status pengiriman hingga selesai."
      />
      <section className="card">
        {shipments.isLoading ? (
          <LoadingState />
        ) : !shipments.data?.length ? (
          <EmptyState title="Belum ada dokumen pengiriman (DO)" />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Nomor DO</th>
                  <th>Tipe Pengiriman</th>
                  <th>Tujuan / Penerima (Sekolah)</th>
                  <th>Waktu Dispatch</th>
                  <th>No. Referensi</th>
                  <th>Item Dikirim</th>
                  <th>Status</th>
                  <th style={{ width: '140px', textAlign: 'center' }}>Aksi Status</th>
                </tr>
              </thead>
              <tbody>
                {shipments.data.map((s) => (
                  <tr key={s.id}>
                    <td>
                      <code className="font-bold text-[var(--color-primary-dark,#263f7d)]">{s.shipment_no}</code>
                    </td>
                    <td>
                      <span className="chip" style={{ fontSize: '0.75rem', fontWeight: 700 }}>
                        {s.shipment_type === 'RENTAL' ? 'SEWA MLK' : s.shipment_type}
                      </span>
                    </td>
                    <td>
                      <div className="font-semibold text-[var(--color-primary-dark,#263f7d)]">
                        {s.recipient_name || '—'}
                      </div>
                      {s.notes && <div className="text-xs text-[var(--color-text-muted)] mt-0.5">{s.notes}</div>}
                    </td>
                    <td className="font-mono text-xs">{formatDate(s.dispatched_at)}</td>
                    <td>
                      <code className="text-xs font-semibold">{s.tracking_reference ?? '—'}</code>
                    </td>
                    <td>
                      {s.items?.map((it: any) => (
                        <div key={it.id} className="text-xs">
                          <code className="font-semibold">{it.product_code || '1001'}</code>: {formatNumber(it.quantity)} Unit
                        </div>
                      )) ?? '—'}
                    </td>
                    <td>
                      <StatusBadge status={s.status} />
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      {s.status === 'DELIVERED' || s.status === 'SELESAI' ? (
                        <span className="text-xs font-semibold text-emerald-700 flex items-center justify-center gap-1">
                          <CheckCircle2 size={15} /> Selesai
                        </span>
                      ) : canDispatch ? (
                        <Button
                          size="sm"
                          variant="primary"
                          loading={updateStatus.isPending && (updateStatus.variables as any)?.id === s.id}
                          onClick={() => updateStatus.mutate({ id: s.id, status: 'DELIVERED' })}
                        >
                          Tandai Selesai
                        </Button>
                      ) : (
                        <span className="text-xs text-[var(--color-text-muted)]">—</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </>
  );
}

// --- Phase 9: Material Cost & Reports ---
export function MaterialCostPage() {
  const report = useQuery({
    queryKey: ['reports', 'material-cost'],
    queryFn: async () => (await http.get<ResourceResponse<any>>('/reports/material-cost')).data.data,
  });

  return (
    <>
      <PageHeader
        eyebrow="Biaya Material Produksi"
        title="Perhitungan Biaya Material Aktual per Production Order"
        description="Hanya menghitung biaya komponen/material aktual saat issue (dikurangi retur sisa material). Tidak memasukkan tenaga kerja, mesin, listrik, overhead, depresiasi, atau jasa GAP."
      />
      <section className="card">
        {report.isLoading ? (
          <LoadingState />
        ) : !report.data?.orders?.length ? (
          <EmptyState title="Belum ada realisasi biaya produksi" description="Biaya akan terhitung otomatis saat material dikeluarkan untuk Work Order." />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th>Production Order</th>
                  <th>Produk</th>
                  <th>Target</th>
                  <th>Good Output</th>
                  <th>Reject / Scrap</th>
                  <th>Total Biaya Material Bersih</th>
                  <th>Biaya Material per Good Unit</th>
                </tr>
              </thead>
              <tbody>
                {report.data.orders.map((row: any) => (
                  <tr key={row.id}>
                    <td>
                      <code>{row.order_no}</code>
                    </td>
                    <td>
                      <code>{row.product_code}</code> · {row.product_name}
                    </td>
                    <td>{formatNumber(row.target_qty)}</td>
                    <td>{formatNumber(row.total_good_qty)}</td>
                    <td>{formatNumber(row.total_reject_qty)}</td>
                    <td>
                      <strong>{formatRupiah(row.total_material_cost)}</strong>
                    </td>
                    <td>{formatRupiah(row.material_cost_per_good_unit)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </>
  );
}

export function ReportsPage() {
  const overview = useQuery({
    queryKey: ['reports', 'overview'],
    queryFn: async () => (await http.get<ResourceResponse<any>>('/reports/overview')).data.data,
  });

  return (
    <>
      <PageHeader
        eyebrow="Laporan Operasional"
        title="Rekapitulasi Persediaan, Vendor GAP & Riwayat Harga Beli"
        description="Seluruh angka ditarik langsung dari ledger inventaris, transaksi penerimaan, dan rekonsiliasi vendor."
      />
      {overview.isLoading ? (
        <LoadingState />
      ) : (
        <div className="grid grid--2">
          <section className="card">
            <div className="card__header">
              <h2 className="card__title">Rekap Saldo per Status Stok</h2>
            </div>
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th>Status</th>
                    <th>Total On-Hand</th>
                    <th>Total Reserved</th>
                  </tr>
                </thead>
                <tbody>
                  {overview.data?.inventory_by_status?.map((row: any) => (
                    <tr key={row.stock_status}>
                      <td>
                        <StatusBadge status={row.stock_status} />
                      </td>
                      <td>{formatNumber(row.on_hand)}</td>
                      <td>{formatNumber(row.reserved)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>

          <section className="card">
            <div className="card__header">
              <h2 className="card__title">Riwayat Harga Pembelian (GRN)</h2>
            </div>
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th>Komponen</th>
                    <th>Supplier</th>
                    <th>Harga Aktual</th>
                    <th>Tanggal</th>
                  </tr>
                </thead>
                <tbody>
                  {overview.data?.purchase_price_history?.length ? (
                    overview.data.purchase_price_history.map((h: any) => (
                      <tr key={h.id}>
                        <td>
                          <code>{h.product_code}</code> · {h.product_name}
                        </td>
                        <td>{h.supplier_name ?? '—'}</td>
                        <td>{formatRupiah(h.unit_price)}</td>
                        <td>{formatDate(h.effective_at)}</td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan={4} className="table__muted">
                        Belum ada penerimaan PO (GRN).
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </section>
        </div>
      )}
    </>
  );
}
