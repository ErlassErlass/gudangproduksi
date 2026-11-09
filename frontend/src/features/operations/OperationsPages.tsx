import { useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Calculator, CheckCircle2, ListOrdered, Plus, Trash2 } from 'lucide-react';
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
import { PlanStagesModal } from './PlanStagesModal';
import { QualityInspectionModal } from './QualityInspectionModal';

// --- Phase 4: Procurement (PO & GRN) ---
export function ProcurementPage() {
  const { can } = useAuth();
  const canCreate = can('procurement.po.create');
  const qc = useQueryClient();
  const { notify } = useToast();
  const [openNew, setOpenNew] = useState(false);

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
        description="Pencatatan pengadaan komponen mandiri dengan input teks supplier, nomor WhatsApp, serta status pembelian (Dipesan, Dikirim, Selesai Diterima)."
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
          <EmptyState title="Belum ada Purchase Order" description="Buat PO baru untuk mencatat pengadaan komponen." />
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
                  <th style={{ width: '130px', textAlign: 'center' }}>Aksi Status</th>
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
                      <div style={{ display: 'flex', gap: '4px', justifyContent: 'center' }}>
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
    </>
  );
}

function NewPoModal({ onClose, onCreated }: { onClose: () => void; onCreated: () => void }) {
  const products = useProducts({ per_page: 100, product_type: 'PRODUCT' });
  const [form, setForm] = useState({
    supplier_name: '',
    supplier_phone: '',
    status: 'DIPESAN',
    order_date: new Date().toISOString().slice(0, 10),
    product_id: '',
    ordered_qty: '50',
    unit_price: '18500',
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

        <FormField label="Komponen / Produk" htmlFor="po-prod" required>
          <select
            id="po-prod"
            className="select"
            value={form.product_id}
            onChange={(e) => setForm({ ...form, product_id: e.target.value })}
            required
          >
            <option value="">Pilih komponen / produk</option>
            {products.data?.data.map((p) => (
              <option key={p.id} value={p.id}>
                {p.code} — {p.name}
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
                  <th>Tahapan Progress</th>
                  <th>Jatuh Tempo</th>
                  <th style={{ width: '130px', textAlign: 'center' }}>Hitung MRP</th>
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
                    <td>{formatDate(plan.due_date)}</td>
                    <td style={{ textAlign: 'center' }}>
                      {canCreate && (
                        <Button
                          variant="primary"
                          size="sm"
                          icon={<Calculator size={14} />}
                          onClick={() => runMrp.mutate(plan.id)}
                          loading={runMrp.isPending}
                        >
                          Hitung MRP
                        </Button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
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
