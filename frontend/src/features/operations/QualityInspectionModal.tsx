import { useState, useEffect, useMemo, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  AlertTriangle,
  CheckCircle2,
  Package,
  Layers,
  Cpu,
  School,
} from 'lucide-react';
import { http } from '@/api/client';
import type { ResourceResponse } from '@/api/types';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useCustomers, useProducts } from '@/features/master-data/api';
import { formatNumber, formatRupiah } from '@/lib/format';

interface ChecklistItem {
  product_id: number;
  code: string;
  name: string;
  tier: 'BOX' | 'MODULE' | 'COMPONENT' | string;
  category_name: string;
  std_qty_per_unit: number;
  uom: string;
  reference_purchase_price: number;
}

interface ChecklistRowState extends ChecklistItem {
  expected_qty: number;
  actual_qty: number;
  missing_qty: number;
  is_chargeable: boolean;
  notes: string;
}

interface QualityInspectionModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export function QualityInspectionModal({ isOpen, onClose, onSuccess }: QualityInspectionModalProps) {
  const { notify } = useToast();
  const qc = useQueryClient();

  // Mode: UNIT_DEFECT (Satuan/Pengiriman cacat) vs RENTAL_RETURN_SCHOOL (Batch Per Sekolah)
  const [inspectionType, setInspectionType] = useState<'UNIT_DEFECT' | 'RENTAL_RETURN_SCHOOL'>('RENTAL_RETURN_SCHOOL');
  const [selectedRentalId, setSelectedRentalId] = useState<number | ''>('');
  const [selectedProductId, setSelectedProductId] = useState<number | ''>('');
  const [customerName, setCustomerName] = useState('');
  const [batchNo, setBatchNo] = useState('');
  const [unitCount, setUnitCount] = useState('1');
  const [generalNotes, setGeneralNotes] = useState('');

  // Fetch list rental orders (khusus sewa sekolah), customers & finished goods
  const rentalOrdersQuery = useQuery({
    queryKey: ['rental-orders', 'schools'],
    queryFn: async () => (await http.get<ResourceResponse<any[]>>('/rental-orders')).data.data,
  });
  const customers = useCustomers();
  const products = useProducts({ per_page: 50, item_group: 'finished_good' });

  // Handle pilih order sewa sekolah
  const handleRentalSelect = (rentalId: number | '') => {
    setSelectedRentalId(rentalId);
    if (!rentalId) return;

    const order = (rentalOrdersQuery.data ?? []).find((r) => r.id === rentalId);
    if (order) {
      setCustomerName(order.customer_name || '');
      setBatchNo(order.rental_no || '');
      setUnitCount(String(order.total_units || 1));
      if (order.product_id) {
        setSelectedProductId(order.product_id);
      }
    }
  };

  // Default select first rental order if in RENTAL_RETURN_SCHOOL mode
  useEffect(() => {
    if (
      inspectionType === 'RENTAL_RETURN_SCHOOL' &&
      rentalOrdersQuery.data &&
      rentalOrdersQuery.data.length > 0 &&
      !selectedRentalId
    ) {
      handleRentalSelect(rentalOrdersQuery.data[0].id);
    }
  }, [inspectionType, rentalOrdersQuery.data, selectedRentalId]);

  // Default select first FG (e.g. MLK Standar)
  useEffect(() => {
    if (products.data?.data && products.data.data.length > 0 && !selectedProductId) {
      const mlk = products.data.data.find((p) => p.code === '1001') ?? products.data.data[0];
      setSelectedProductId(mlk.id);
    }
  }, [products.data, selectedProductId]);

  // Query checklist template for selected FG
  const { data: templateData, isLoading: isTemplateLoading } = useQuery({
    queryKey: ['qc', 'checklist-template', selectedProductId],
    enabled: isOpen && Boolean(selectedProductId),
    queryFn: async () => {
      const res = await http.get<ResourceResponse<{ product: any; items: ChecklistItem[] }>>(
        `/quality-inspections/checklist-template/${selectedProductId}`
      );
      return res.data.data;
    },
  });

  const [checklistRows, setChecklistRows] = useState<ChecklistRowState[]>([]);

  // Initialize checklist rows when template or unit count changes
  useEffect(() => {
    if (templateData?.items) {
      const multiplier = Math.max(1, Number(unitCount) || 1);
      setChecklistRows(
        templateData.items.map((item) => {
          const target = Math.round(item.std_qty_per_unit * multiplier * 100) / 100;
          return {
            ...item,
            expected_qty: target,
            actual_qty: target, // default match
            missing_qty: 0,
            is_chargeable: true,
            notes: '',
          };
        })
      );
    }
  }, [templateData, unitCount]);

  const handleActualQtyChange = (idx: number, val: string) => {
    const act = Math.max(0, Number(val) || 0);
    setChecklistRows((prev) => {
      const next = [...prev];
      const target = next[idx].expected_qty;
      const missing = target > act ? target - act : 0;
      next[idx] = {
        ...next[idx],
        actual_qty: act,
        missing_qty: missing,
      };
      return next;
    });
  };

  const handleChargeableToggle = (idx: number, val: boolean) => {
    setChecklistRows((prev) => {
      const next = [...prev];
      next[idx] = { ...next[idx], is_chargeable: val };
      return next;
    });
  };

  const setAllChargeable = (chargeable: boolean) => {
    setChecklistRows((prev) => prev.map((r) => ({ ...r, is_chargeable: chargeable })));
  };

  // Calculations
  const totalMissingItems = useMemo(
    () => checklistRows.reduce((acc, r) => acc + (r.missing_qty > 0 ? r.missing_qty : 0), 0),
    [checklistRows]
  );

  const totalPhysicalLoss = useMemo(
    () => checklistRows.reduce((acc, r) => acc + r.missing_qty * r.reference_purchase_price, 0),
    [checklistRows]
  );

  const totalSchoolClaim = useMemo(
    () =>
      checklistRows.reduce(
        (acc, r) => acc + (r.is_chargeable ? r.missing_qty * r.reference_purchase_price : 0),
        0
      ),
    [checklistRows]
  );

  const submitMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        inspection_type: inspectionType,
        product_id: Number(selectedProductId),
        customer_name: customerName.trim() || undefined,
        batch_no: batchNo.trim() || undefined,
        unit_count: Math.max(1, Number(unitCount) || 1),
        notes: generalNotes.trim() || undefined,
        items: checklistRows.map((r) => ({
          product_id: r.product_id,
          tier: r.tier,
          expected_qty: r.expected_qty,
          actual_qty: r.actual_qty,
          unit_price: r.reference_purchase_price,
          is_chargeable: r.is_chargeable,
          notes: r.notes || undefined,
        })),
      };

      const res = await http.post('/quality-inspections', payload);
      return res.data;
    },
    onSuccess: (res: any) => {
      qc.invalidateQueries({ queryKey: ['qc-inspections'] });
      notify(res.message, 'success');
      onSuccess();
      onClose();
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message ?? 'Gagal menyimpan hasil inspeksi QC.', 'error');
    },
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!selectedProductId) {
      notify('Pilih produk Finished Good yang diinspeksi.', 'error');
      return;
    }
    submitMutation.mutate();
  };

  const getTierBadge = (tier: string) => {
    switch (tier) {
      case 'BOX':
        return (
          <span className="chip" style={{ background: '#fef3c7', color: '#92400e', fontWeight: 600 }}>
            <Package size={11} style={{ display: 'inline', marginRight: 3 }} />
            Kit Box
          </span>
        );
      case 'MODULE':
        return (
          <span className="chip" style={{ background: '#e0f2fe', color: '#0369a1', fontWeight: 600 }}>
            <Layers size={11} style={{ display: 'inline', marginRight: 3 }} />
            Modul
          </span>
        );
      default:
        return (
          <span className="chip" style={{ background: '#f1f5f9', color: '#475569', fontWeight: 500 }}>
            <Cpu size={11} style={{ display: 'inline', marginRight: 3 }} />
            Komponen
          </span>
        );
    }
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title="Mulai Inspeksi Quality Control (QC)"
      description="Checklist kelengkapan Box, Modul, dan Komponen Finished Good (MLK) dengan kalkulasi otomatis selisih dan kerugian sekolah."
      size="xl"
      footer={
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%' }}>
          <div style={{ fontSize: '0.85rem' }}>
            {totalMissingItems > 0 ? (
              <span style={{ color: '#b91c1c', fontWeight: 700 }}>
                Ditemukan {totalMissingItems} item hilang &bull; Klaim Ganti Rugi Sekolah: {formatRupiah(totalSchoolClaim)}
              </span>
            ) : (
              <span style={{ color: '#15803d', fontWeight: 700 }}>
                <CheckCircle2 size={14} style={{ display: 'inline', marginRight: 4 }} />
                Seluruh kelengkapan 100% lengkap (Tidak ada kerugian)
              </span>
            )}
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <Button variant="secondary" onClick={onClose}>
              Batal
            </Button>
            <Button
              variant="primary"
              onClick={handleSubmit}
              loading={submitMutation.isPending}
              icon={<CheckCircle2 size={16} />}
            >
              Simpan Hasil Inspeksi QC
            </Button>
          </div>
        </div>
      }
    >
      <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        {/* Mode Inspeksi Selector */}
        <div
          style={{
            padding: 14,
            borderRadius: 10,
            background: 'var(--color-surface-muted, #f8faff)',
            border: '1px solid var(--color-border, #e2e8f0)',
          }}
        >
          <label style={{ fontSize: '0.85rem', fontWeight: 700, color: 'var(--color-primary-dark, #263f7d)', display: 'block', marginBottom: 8 }}>
            Pilih Jenis Pelaksanaan QC:
          </label>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 16 }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontWeight: 600 }}>
              <input
                type="radio"
                name="qc-type"
                checked={inspectionType === 'RENTAL_RETURN_SCHOOL'}
                onChange={() => setInspectionType('RENTAL_RETURN_SCHOOL')}
              />
              <School size={16} color="var(--color-primary, #3859ac)" />
              <span>Berdasarkan Batch Per Sekolah (Pengembalian Sewa Selesai)</span>
            </label>

            <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', fontWeight: 600 }}>
              <input
                type="radio"
                name="qc-type"
                checked={inspectionType === 'UNIT_DEFECT'}
                onChange={() => setInspectionType('UNIT_DEFECT')}
              />
              <AlertTriangle size={16} color="#d97706" />
              <span>Bentuk Satuan (Insidental / Pengiriman Cacat & Tidak Lengkap)</span>
            </label>
          </div>
        </div>

        {/* Pemilihan Order Sewa Sekolah untuk Mode Pengembalian */}
        {inspectionType === 'RENTAL_RETURN_SCHOOL' && (
          <FormField
            label="Pilih Order Penyewaan Sekolah"
            htmlFor="qc-rental-order"
            required
            hint="Pilih kontrak sewa sekolah yang telah terdaftar untuk memuat data sekolah, produk, dan kuantitas unit"
          >
            <select
              id="qc-rental-order"
              className="select font-semibold text-[var(--color-primary-dark, #263f7d)]"
              value={selectedRentalId}
              onChange={(e) => handleRentalSelect(e.target.value ? Number(e.target.value) : '')}
              required
            >
              <option value="">Pilih order sewa sekolah...</option>
              {(rentalOrdersQuery.data ?? []).map((ro) => (
                <option key={ro.id} value={ro.id}>
                  [{ro.rental_no}] {ro.customer_name} &bull; {ro.product_name || 'MLK Standar'} ({ro.total_units || 1} Unit)
                </option>
              ))}
            </select>
          </FormField>
        )}

        {/* Informasi Subjek QC */}
        <div className="grid grid--3">
          <FormField label="Produk Finished Good Target" htmlFor="qc-prod" required>
            <select
              id="qc-prod"
              className="select"
              value={selectedProductId}
              onChange={(e) => setSelectedProductId(Number(e.target.value))}
            >
              <option value="">Pilih Finished Good...</option>
              {products.data?.data.map((p) => (
                <option key={p.id} value={p.id}>
                  [{p.code}] {p.name}
                </option>
              ))}
            </select>
          </FormField>

          {inspectionType === 'RENTAL_RETURN_SCHOOL' ? (
            <FormField label="Pihak Sekolah (Customer)" htmlFor="qc-cust">
              <input
                id="qc-cust"
                type="text"
                className="input font-semibold"
                placeholder="Pilih dari order sewa di atas"
                value={customerName}
                onChange={(e) => setCustomerName(e.target.value)}
                list="customers-list"
              />
              <datalist id="customers-list">
                {customers.data?.map((c) => (
                  <option key={c.id} value={c.name} />
                ))}
              </datalist>
            </FormField>
          ) : (
            <FormField label="No. Dokumen / No. Surat Jalan Masalah" htmlFor="qc-ref">
              <input
                id="qc-ref"
                type="text"
                className="input"
                placeholder="Mis. SJ-20261109-0012"
                value={batchNo}
                onChange={(e) => setBatchNo(e.target.value)}
              />
            </FormField>
          )}

          <FormField label="Jumlah Unit Finished Good Diperiksa" htmlFor="qc-unit-count" required>
            <input
              id="qc-unit-count"
              type="number"
              min="1"
              className="input font-mono font-bold"
              value={unitCount}
              onChange={(e) => setUnitCount(e.target.value)}
            />
          </FormField>
        </div>

        {/* Header Checklist & Action Controls */}
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 4 }}>
          <div>
            <h3 style={{ margin: 0, fontSize: '0.95rem', fontWeight: 800, color: 'var(--color-primary-dark, #263f7d)' }}>
              Checklist Kelengkapan Struktur Finished Good (Box, Modul, & Komponen)
            </h3>
            <p style={{ margin: '2px 0 0 0', fontSize: '0.75rem', color: '#64748b' }}>
              Periksa isi fisik dan bandingkan Qty Realisasi dengan Qty yang Ditentukan.
            </p>
          </div>

          <div style={{ display: 'flex', gap: 6 }}>
            <Button type="button" size="sm" variant="secondary" onClick={() => setAllChargeable(true)}>
              Semua Komponen Diganti Rugi
            </Button>
            <Button
              type="button"
              size="sm"
              variant="secondary"
              onClick={() =>
                setChecklistRows((prev) =>
                  prev.map((r) => ({ ...r, is_chargeable: r.tier === 'BOX' || r.tier === 'MODULE' }))
                )
              }
            >
              Hanya Box & Modul
            </Button>
          </div>
        </div>

        {/* Tabel Checklist Kelengkapan */}
        {isTemplateLoading ? (
          <LoadingState label="Memuat resep struktur kelengkapan Finished Good..." />
        ) : checklistRows.length === 0 ? (
          <div className="alert alert--danger">Resep kelengkapan komponen Finished Good belum terdaftar.</div>
        ) : (
          <div className="table-wrap">
            <table className="table" style={{ fontSize: '0.825rem' }}>
              <thead>
                <tr>
                  <th style={{ width: '40px' }}>#</th>
                  <th>Kelengkapan Box / Modul / Komponen</th>
                  <th style={{ width: '100px' }}>Kategori</th>
                  <th style={{ width: '90px', textAlign: 'right' }}>Qty Standar</th>
                  <th style={{ width: '100px', textAlign: 'center' }}>Qty Realisasi</th>
                  <th style={{ width: '85px', textAlign: 'right' }}>Selisih Hilang</th>
                  <th style={{ width: '110px', textAlign: 'right' }}>Harga Satuan</th>
                  <th style={{ width: '110px', textAlign: 'center' }}>Ganti Rugi?</th>
                  <th style={{ width: '120px', textAlign: 'right' }}>Subtotal Kerugian</th>
                </tr>
              </thead>
              <tbody>
                {checklistRows.map((row, idx) => {
                  const hasLoss = row.missing_qty > 0;
                  const itemClaim = row.is_chargeable ? row.missing_qty * row.reference_purchase_price : 0;

                  return (
                    <tr
                      key={idx}
                      style={{
                        backgroundColor: hasLoss ? (row.is_chargeable ? '#fff1f2' : '#fefce8') : undefined,
                      }}
                    >
                      <td style={{ textAlign: 'center', color: '#94a3b8' }}>{idx + 1}</td>
                      <td>
                        <div style={{ fontWeight: 600, color: 'var(--color-primary-dark, #263f7d)' }}>
                          {row.name}
                        </div>
                        <code style={{ fontSize: '0.75rem' }}>{row.code}</code>
                      </td>
                      <td>{getTierBadge(row.tier)}</td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 600 }}>
                        {formatNumber(row.expected_qty)} {row.uom}
                      </td>
                      <td style={{ textAlign: 'center' }}>
                        <input
                          type="number"
                          min="0"
                          step="1"
                          className="input font-mono font-bold"
                          style={{
                            width: 75,
                            padding: '4px 6px',
                            minHeight: 32,
                            textAlign: 'center',
                            borderColor: hasLoss ? '#ef4444' : undefined,
                          }}
                          value={row.actual_qty}
                          onChange={(e) => handleActualQtyChange(idx, e.target.value)}
                        />
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 800 }}>
                        {hasLoss ? (
                          <span style={{ color: '#b91c1c' }}>-{formatNumber(row.missing_qty)}</span>
                        ) : (
                          <span style={{ color: '#15803d' }}>0</span>
                        )}
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace' }}>
                        {formatRupiah(row.reference_purchase_price)}
                      </td>
                      <td style={{ textAlign: 'center' }}>
                        {hasLoss ? (
                          <label style={{ display: 'inline-flex', alignItems: 'center', gap: 4, cursor: 'pointer' }}>
                            <input
                              type="checkbox"
                              checked={row.is_chargeable}
                              onChange={(e) => handleChargeableToggle(idx, e.target.checked)}
                            />
                            <span style={{ fontSize: '0.75rem', fontWeight: 600, color: row.is_chargeable ? '#b91c1c' : '#64748b' }}>
                              {row.is_chargeable ? 'Klaim' : 'Bebas'}
                            </span>
                          </label>
                        ) : (
                          <span className="table__muted">—</span>
                        )}
                      </td>
                      <td style={{ textAlign: 'right', fontFamily: 'monospace', fontWeight: 700 }}>
                        {hasLoss ? (
                          <span style={{ color: row.is_chargeable ? '#b91c1c' : '#64748b' }}>
                            {formatRupiah(itemClaim)}
                          </span>
                        ) : (
                          <span style={{ color: '#94a3b8' }}>Rp 0</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Ringkasan Kerugian & Ganti Rugi Sekolah */}
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
            gap: 12,
            padding: 14,
            borderRadius: 10,
            background: totalSchoolClaim > 0 ? '#fff1f2' : 'var(--color-surface-muted, #f8faff)',
            border: `1px solid ${totalSchoolClaim > 0 ? '#fecdd3' : 'var(--color-border, #e2e8f0)'}`,
          }}
        >
          <div>
            <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Total Komponen/Modul Hilang</div>
            <div style={{ fontSize: '1.25rem', fontWeight: 800, color: totalMissingItems > 0 ? '#b91c1c' : '#15803d', fontFamily: 'monospace' }}>
              {totalMissingItems} Unit
            </div>
          </div>

          <div>
            <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Total Nilai Kerugian Fisik</div>
            <div style={{ fontSize: '1.1rem', fontWeight: 700, color: '#475569', fontFamily: 'monospace' }}>
              {formatRupiah(totalPhysicalLoss)}
            </div>
          </div>

          <div
            style={{
              background: '#ffffff',
              padding: '8px 12px',
              borderRadius: 8,
              border: '1px solid #cbd5e1',
            }}
          >
            <div style={{ fontSize: '0.75rem', fontWeight: 700, color: '#b91c1c' }}>
              BEBAN GANTI RUGI PIHAK SEKOLAH
            </div>
            <div style={{ fontSize: '1.35rem', fontWeight: 900, color: '#b91c1c', fontFamily: 'monospace' }}>
              {formatRupiah(totalSchoolClaim)}
            </div>
          </div>
        </div>

        {/* Catatan Tambahan */}
        <FormField label="Catatan / Berita Acara Temuan QC (Opsional)" htmlFor="qc-notes">
          <textarea
            id="qc-notes"
            className="input"
            style={{ minHeight: 60 }}
            placeholder="Mis. Ditemukan kabel jumper dan adaptor hilang saat pengembalian sewa, pihak sekolah menyetujui penggantian denda..."
            value={generalNotes}
            onChange={(e) => setGeneralNotes(e.target.value)}
          />
        </FormField>
      </form>
    </Modal>
  );
}
