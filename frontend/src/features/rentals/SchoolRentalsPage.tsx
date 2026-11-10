import { useState, useMemo, useEffect, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  Barcode,
  CheckCircle2,
  ClipboardCheck,
  FileText,
  PackageSearch,
  Plus,
  QrCode,
  RotateCcw,
  ScanBarcode,
  School,
  Search,
  Eye,
} from 'lucide-react';
import { http } from '@/api/client';
import type { ResourceResponse } from '@/api/types';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { StatCard } from '@/components/StatCard';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import { useCustomers, useProducts, useProductStock } from '@/features/master-data/api';
import { QualityInspectionModal } from '@/features/operations/QualityInspectionModal';
import { formatDate, formatNumber, formatRupiah } from '@/lib/format';
import { SchoolRentalBarcodeModal, type SchoolRentalRecord } from './SchoolRentalBarcodeModal';
import { DeliveryOrderModal, type DeliveryOrderData } from './DeliveryOrderModal';
import { BarcodeScanModal } from '@/features/catalog/BarcodeScanModal';

export function SchoolRentalsPage() {
  const { can } = useAuth();
  const canManage = can('rental.manage');
  const qc = useQueryClient();
  const { notify } = useToast();

  const [activeTab, setActiveTab] = useState<'orders' | 'units' | 'qc_history'>('orders');
  const [unitSearch, setUnitSearch] = useState('');
  const [isNewOpen, setIsNewOpen] = useState(false);
  const [isQcOpen, setIsQcOpen] = useState(false);
  const [barcodeRental, setBarcodeRental] = useState<SchoolRentalRecord | null>(null);
  const [deliveryOrderData, setDeliveryOrderData] = useState<DeliveryOrderData | null>(null);
  const [selectedInspection, setSelectedInspection] = useState<any | null>(null);
  const [isUnitScanOpen, setIsUnitScanOpen] = useState(false);
  const [scannedUnitCode, setScannedUnitCode] = useState<string | undefined>(undefined);

  const generateDo = useMutation({
    mutationFn: async (orderId: number) => {
      const res = await http.post<ResourceResponse<DeliveryOrderData>>(`/rental-orders/${orderId}/generate-do`);
      return res.data;
    },
    onSuccess: (data) => {
      qc.invalidateQueries({ queryKey: ['shipments'] });
      qc.invalidateQueries({ queryKey: ['rental-orders'] });
      notify('Surat DO diterbitkan dan otomatis ditambahkan ke halaman Distribusi.', 'success');
      setDeliveryOrderData(data.data);
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message || 'Gagal menerbitkan Surat DO.', 'error');
    },
  });

  const markReturned = useMutation({
    mutationFn: async (rentalId: number) => {
      await http.patch(`/rental-orders/${rentalId}/status`, { status: 'RETURNED' });
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['rental-orders'] });
      qc.invalidateQueries({ queryKey: ['rental-orders', 'units-tracking'] });
      notify('Status sewa diperbarui: Selesai/Kembali. Unit dapat disewakan kembali.', 'success');
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message || 'Gagal memperbarui status sewa.', 'error');
    },
  });

  // Query rental orders khusus sewa sekolah
  const rentals = useQuery({
    queryKey: ['rental-orders', 'schools'],
    queryFn: async () => (await http.get<ResourceResponse<SchoolRentalRecord[]>>('/rental-orders')).data.data,
  });

  // Query pelacakan unit fisik MLK & riwayat inspeksi
  const unitTracking = useQuery({
    queryKey: ['rental-orders', 'units-tracking'],
    queryFn: async () => (await http.get<ResourceResponse<{ units: any[]; inspections: any[] }>>('/rental-orders/units-tracking')).data.data,
  });

  const schoolRentals = rentals.data ?? [];
  const activeCount = schoolRentals.filter((r) => r.status === 'ACTIVE' || r.status === 'DISPATCHED').length;
  const returnedCount = schoolRentals.filter((r) => r.status === 'RETURNED' || r.status === 'CLOSED').length;

  const allUnits = unitTracking.data?.units ?? [];
  const filteredUnits = useMemo(() => {
    if (!unitSearch.trim()) return allUnits;
    const q = unitSearch.toLowerCase();
    return allUnits.filter(
      (u) =>
        u.unit_code?.toLowerCase().includes(q) ||
        u.current_school?.toLowerCase().includes(q) ||
        String(u.unit_no).includes(q)
    );
  }, [allUnits, unitSearch]);

  const qcInspections = unitTracking.data?.inspections ?? [];

  return (
    <>
      <PageHeader
        eyebrow="Gudang & Operasional — Sewa Sekolah"
        title="Pelacakan Sewa Finished Good MLK (Pihak Sekolah)"
        description="Pelacakan sirkulasi sewa produk Finished Good (Mobile Learning Kit) untuk pihak sekolah mitra: cetak stiker barcode fisik netral aset, pelacakan nomor unit di sekolah saat ini, dan riwayat kelengkapan unit per sekolah."
        actions={
          <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
            <Button
              variant="secondary"
              icon={<ScanBarcode size={16} />}
              onClick={() => {
                setScannedUnitCode(undefined);
                setIsUnitScanOpen(true);
              }}
              title="Scan barcode untuk langsung mendeteksi produk Finished Good"
            >
              Scan Barcode
            </Button>
            <Button
              variant="secondary"
              icon={<ClipboardCheck size={16} />}
              onClick={() => setIsQcOpen(true)}
            >
              Inspeksi Kelengkapan QC
            </Button>
            {canManage && (
              <Button
                variant="primary"
                icon={<Plus size={16} />}
                onClick={() => setIsNewOpen(true)}
              >
                + Buat Order Sewa Sekolah
              </Button>
            )}
          </div>
        }
      />

      <div className="grid grid--3 mb-6">
        <StatCard
          label="Total Sewa Sekolah"
          value={schoolRentals.length}
          hint="Kontrak sewa terdaftar"
          icon={<School size={18} />}
        />
        <StatCard
          label="Sedang Berlangsung di Sekolah"
          value={activeCount}
          hint="Unit MLK aktif di lapangan"
          icon={<PackageSearch size={18} />}
          accent
        />
        <StatCard
          label="Selesai / Kembali"
          value={returnedCount}
          hint="Unit kembali ke gudang (siap pakai ulang)"
          icon={<CheckCircle2 size={18} />}
        />
      </div>

      {/* Tab Navigation */}
      <div style={{ display: 'flex', gap: 8, marginBottom: 16 }}>
        <button
          className="tab-btn"
          onClick={() => setActiveTab('orders')}
          style={{
            padding: '8px 16px',
            borderRadius: 6,
            fontWeight: 600,
            fontSize: '0.875rem',
            border: '1px solid var(--color-border)',
            backgroundColor: activeTab === 'orders' ? 'var(--color-primary-dark, #263f7d)' : 'var(--color-bg-surface, #ffffff)',
            color: activeTab === 'orders' ? '#ffffff' : 'var(--color-text-main, #0f172a)',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            gap: 6,
          }}
        >
          <School size={16} />
          <span>Kontrak Sewa Sekolah ({schoolRentals.length})</span>
        </button>
        <button
          className="tab-btn"
          onClick={() => setActiveTab('units')}
          style={{
            padding: '8px 16px',
            borderRadius: 6,
            fontWeight: 600,
            fontSize: '0.875rem',
            border: '1px solid var(--color-border)',
            backgroundColor: activeTab === 'units' ? 'var(--color-primary-dark, #263f7d)' : 'var(--color-bg-surface, #ffffff)',
            color: activeTab === 'units' ? '#ffffff' : 'var(--color-text-main, #0f172a)',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            gap: 6,
          }}
        >
          <PackageSearch size={16} />
          <span>Pelacakan Unit Fisik MLK ({allUnits.length})</span>
        </button>
        <button
          className="tab-btn"
          onClick={() => setActiveTab('qc_history')}
          style={{
            padding: '8px 16px',
            borderRadius: 6,
            fontWeight: 600,
            fontSize: '0.875rem',
            border: '1px solid var(--color-border)',
            backgroundColor: activeTab === 'qc_history' ? 'var(--color-primary-dark, #263f7d)' : 'var(--color-bg-surface, #ffffff)',
            color: activeTab === 'qc_history' ? '#ffffff' : 'var(--color-text-main, #0f172a)',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            gap: 6,
          }}
        >
          <ClipboardCheck size={16} />
          <span>Riwayat Kelengkapan & QC ({qcInspections.length})</span>
        </button>
      </div>

      {/* TAB 1: KONTRAK SEWA SEKOLAH */}
      {activeTab === 'orders' && (
        <section className="card">
          {rentals.isLoading ? (
            <LoadingState label="Memuat data sewa sekolah..." />
          ) : schoolRentals.length === 0 ? (
            <EmptyState
              title="Belum ada pesanan sewa sekolah"
              description="Buat order sewa sekolah pertama Anda yang mengambil unit MLK dari rencana produksi yang telah selesai."
              action={
                canManage ? (
                  <Button variant="primary" icon={<Plus size={16} />} onClick={() => setIsNewOpen(true)}>
                    + Buat Order Sewa Sekolah
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th style={{ width: '140px' }}>No. Order Sewa</th>
                    <th>Pihak Sekolah (Customer)</th>
                    <th>Ketersediaan Stok Gudang</th>
                    <th>Produk & Unit Serial Barcode</th>
                    <th style={{ width: '130px' }}>Tgl. Mulai Sewa</th>
                    <th style={{ width: '110px' }}>Status</th>
                    <th style={{ width: '220px', textAlign: 'center' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {schoolRentals.map((r) => {
                    const startCode = r.barcode_prefix && r.start_unit_no
                      ? `${r.barcode_prefix}_${String(r.start_unit_no).padStart(2, '0')}`
                      : null;
                    const endCode = r.barcode_prefix && r.end_unit_no
                      ? `${r.barcode_prefix}_${String(r.end_unit_no).padStart(2, '0')}`
                      : null;

                    return (
                      <tr key={r.id}>
                        <td>
                          <code className="font-bold text-[var(--color-primary-dark, #263f7d)]">{r.rental_no}</code>
                          {r.notes && <div className="text-xs text-[var(--color-text-muted)] mt-1">{r.notes}</div>}
                        </td>
                        <td>
                          <div className="font-semibold text-[var(--color-primary-dark, #263f7d)]">
                            {r.customer_name ?? 'Sekolah Mitra'}
                          </div>
                          {r.customer_code && (
                            <div className="text-xs font-mono text-[var(--color-text-muted)]">{r.customer_code}</div>
                          )}
                        </td>
                        <td>
                          {r.is_stock_shortage ? (
                            <div>
                              <span
                                className="chip"
                                style={{
                                  background: '#fef2f2',
                                  color: '#b91c1c',
                                  border: '1px solid #fecaca',
                                  fontWeight: 700,
                                  fontSize: '0.75rem',
                                  display: 'inline-flex',
                                  alignItems: 'center',
                                  gap: 4,
                                }}
                                title={r.stock_notice || 'Stok finished good di gudang kurang'}
                              >
                                <span>⚠️ Stok Kurang</span>
                                {r.shortage_qty !== undefined && r.shortage_qty > 0 && (
                                  <span>({formatNumber(r.shortage_qty)} Unit)</span>
                                )}
                              </span>
                              <div className="text-xs text-red-600 mt-1 font-medium">
                                Tersedia: {formatNumber(r.available_fg_stock ?? 0)} / Pesan: {formatNumber(r.total_units || 1)} unit
                              </div>
                              {r.production_plan_no && (
                                <div className="text-[11px] text-[var(--color-text-muted)] mt-0.5">Ref: {r.production_plan_no}</div>
                              )}
                            </div>
                          ) : (
                            <div>
                              <span
                                className="chip"
                                style={{
                                  background: '#f0fdf4',
                                  color: '#166534',
                                  border: '1px solid #bbf7d0',
                                  fontWeight: 700,
                                  fontSize: '0.75rem',
                                  display: 'inline-flex',
                                  alignItems: 'center',
                                  gap: 4,
                                }}
                                title="Stok Finished Good mencukupi di gudang"
                              >
                                <span>✓ Stok Siap</span>
                                {r.available_fg_stock !== undefined && (
                                  <span>({formatNumber(r.available_fg_stock)} Unit)</span>
                                )}
                              </span>
                              <div className="text-xs text-emerald-700 mt-1 font-medium">
                                Cukup untuk {formatNumber(r.total_units || 1)} unit
                              </div>
                              {r.production_plan_no && (
                                <div className="text-[11px] text-[var(--color-text-muted)] mt-0.5">Ref: {r.production_plan_no}</div>
                              )}
                            </div>
                          )}
                        </td>
                        <td>
                          <div className="font-semibold text-[var(--color-primary-dark, #263f7d)]">
                            {r.product_name || 'Mobile Learning Kit (MLK) Standar'}
                          </div>
                          {startCode && endCode ? (
                            <div className="text-xs font-mono font-bold text-emerald-700 mt-0.5">
                              Unit: {startCode} s/d {endCode} ({r.total_units || 1} Unit)
                            </div>
                          ) : (
                            <div className="text-xs font-mono text-[var(--color-text-muted)]">
                              {r.total_units || 1} Unit
                            </div>
                          )}
                        </td>
                        <td className="font-mono text-xs">
                          {formatDate(r.start_date)}
                        </td>
                        <td>
                          <span
                            className="chip"
                            style={{
                              background: r.status === 'ACTIVE' || r.status === 'DISPATCHED' ? '#dbeafe' : '#dcfce7',
                              color: r.status === 'ACTIVE' || r.status === 'DISPATCHED' ? '#1d4ed8' : '#15803d',
                              fontWeight: 700,
                              fontSize: '0.75rem',
                            }}
                          >
                            {r.status === 'ACTIVE' ? 'DISEWA' : r.status === 'RETURNED' ? 'SELESAI/KEMBALI' : r.status}
                          </span>
                        </td>
                        <td style={{ textAlign: 'center' }}>
                          <div style={{ display: 'flex', gap: '6px', justifyContent: 'center', flexWrap: 'wrap' }}>
                            <Button
                              size="sm"
                              variant="secondary"
                              icon={<Barcode size={14} />}
                              onClick={() => setBarcodeRental(r)}
                            >
                              Cetak Barcode
                            </Button>
                            <Button
                              size="sm"
                              variant="primary"
                              icon={<FileText size={14} />}
                              loading={generateDo.isPending && (generateDo.variables as any) === r.id}
                              onClick={() => generateDo.mutate(r.id)}
                            >
                              Cetak Surat DO
                            </Button>
                            {(r.status === 'ACTIVE' || r.status === 'DISPATCHED') && (
                              <Button
                                size="sm"
                                variant="secondary"
                                icon={<RotateCcw size={14} />}
                                loading={markReturned.isPending && (markReturned.variables as any) === r.id}
                                onClick={() => {
                                  if (confirm(`Selesaikan masa sewa ${r.rental_no} (${r.customer_name})? Unit serial akan kembali tersedia di gudang untuk dipakai ulang.`)) {
                                    markReturned.mutate(r.id);
                                  }
                                }}
                              >
                                Selesai Sewa
                              </Button>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </section>
      )}

      {/* TAB 2: PELACAKAN NOMOR UNIT FISIK MLK */}
      {activeTab === 'units' && (
        <section className="card">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16, flexWrap: 'wrap', gap: 12 }}>
            <div>
              <h3 style={{ fontSize: '1.05rem', fontWeight: 700, margin: 0 }}>
                Status Penempatan Nomor Unit Fisik Saat Ini
              </h3>
              <p style={{ fontSize: '0.8rem', color: 'var(--color-text-muted)', margin: '2px 0 0 0' }}>
                Nomor barcode unit MLK bersifat dapat dipakai berulang kali. Pantau sekolah mana yang saat ini sedang memegang unit tersebut serta riwayat pemakaiannya.
              </p>
            </div>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
              <div style={{ position: 'relative', width: 220 }}>
                <input
                  type="text"
                  className="input"
                  placeholder="Cari kode unit / sekolah..."
                  value={unitSearch}
                  onChange={(e) => setUnitSearch(e.target.value)}
                  style={{ paddingLeft: 30 }}
                />
                <Search size={14} style={{ position: 'absolute', left: 10, top: 12, color: 'var(--color-text-muted)' }} />
              </div>
              <Button
                variant="secondary"
                size="sm"
                icon={<ScanBarcode size={14} />}
                onClick={() => {
                  setScannedUnitCode(undefined);
                  setIsUnitScanOpen(true);
                }}
                title="Scan barcode untuk langsung mendeteksi produk Finished Good"
              >
                Scan Barcode
              </Button>
            </div>
          </div>

          {unitTracking.isLoading ? (
            <LoadingState label="Memuat status pelacakan nomor unit..." />
          ) : filteredUnits.length === 0 ? (
            <EmptyState
              title="Belum ada nomor unit fisik yang terdata"
              description="Nomor unit fisik MLK akan otomatis tercatat saat order sewa sekolah pertama dibuat."
            />
          ) : (
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th style={{ width: '130px' }}>Nomor Unit (Barcode)</th>
                    <th>Produk Fisik MLK</th>
                    <th>Status Penggunaan Saat Ini</th>
                    <th>Riwayat Pemakaian Sekolah</th>
                    <th>Riwayat QC Kelengkapan</th>
                    <th style={{ width: '130px', textAlign: 'center' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredUnits.map((u: any) => (
                    <tr key={u.unit_code}>
                      <td>
                        <code className="font-bold text-[var(--color-primary-dark, #263f7d)]" style={{ fontSize: '0.9rem' }}>
                          {u.unit_code}
                        </code>
                      </td>
                      <td>
                        <div className="font-medium">{u.product_name}</div>
                        <div className="text-xs text-[var(--color-text-muted)]">Aset Fisik Sewa Netral</div>
                      </td>
                      <td>
                        {u.current_status === 'IN_USE' ? (
                          <div>
                            <span className="chip" style={{ background: '#dbeafe', color: '#1d4ed8', fontWeight: 700, fontSize: '0.75rem' }}>
                              SEDANG DISEWA
                            </span>
                            <div className="font-semibold text-[var(--color-primary-dark, #263f7d)] mt-1">
                              {u.current_school}
                            </div>
                            <div className="text-xs text-[var(--color-text-muted)] font-mono">
                              Order: {u.current_rental_no} &bull; Sejak {formatDate(u.current_start_date)}
                            </div>
                          </div>
                        ) : (
                          <div>
                            <span className="chip" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 700, fontSize: '0.75rem' }}>
                              TERSEDIA DI GUDANG
                            </span>
                            <div className="text-xs text-[var(--color-text-muted)] mt-1">
                              Siap disewakan kembali ke sekolah mitra
                            </div>
                          </div>
                        )}
                      </td>
                      <td>
                        <div style={{ fontSize: '0.8rem' }}>
                          <span className="font-semibold">Pernah dipakai {u.rental_history?.length || 0}x di:</span>
                          <div style={{ display: 'flex', flexDirection: 'column', gap: 2, marginTop: 4 }}>
                            {u.rental_history?.map((rh: any, idx: number) => (
                              <div key={idx} className="text-xs text-[var(--color-text-muted)] flex items-center gap-1.5">
                                <span className="font-semibold text-[var(--color-primary-dark, #263f7d)]">{rh.school_name}</span>
                                <span>({formatDate(rh.start_date)})</span>
                                <span className="text-[10px] font-mono px-1 rounded bg-slate-100">{rh.status}</span>
                              </div>
                            ))}
                          </div>
                        </div>
                      </td>
                      <td>
                        {u.inspection_history && u.inspection_history.length > 0 ? (
                          <div>
                            <span className={`chip ${u.inspection_history[0].status === 'PASSED' ? 'chip--success' : 'chip--warning'}`} style={{ fontSize: '0.7rem' }}>
                              {u.inspection_history[0].status === 'PASSED' ? 'Lengkap (PASSED)' : 'Ada Komponen Hilang'}
                            </span>
                            <div className="text-xs text-[var(--color-text-muted)] mt-1">
                              Terakhir: {formatDate(u.inspection_history[0].inspected_at)} &bull; {u.inspection_history[0].customer_name}
                            </div>
                          </div>
                        ) : (
                          <span className="text-xs text-[var(--color-text-muted)]">Belum ada catatan inspeksi</span>
                        )}
                      </td>
                      <td style={{ textAlign: 'center' }}>
                        <Button
                          size="sm"
                          variant="secondary"
                          icon={<QrCode size={14} />}
                          onClick={() => {
                            setScannedUnitCode(u.unit_code);
                            setIsUnitScanOpen(true);
                          }}
                        >
                          Kelola / Ubah
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>
      )}

      {/* TAB 3: RIWAYAT KELENGKAPAN & QC PER SEKOLAH */}
      {activeTab === 'qc_history' && (
        <section className="card">
          <div style={{ marginBottom: 16 }}>
            <h3 style={{ fontSize: '1.05rem', fontWeight: 700, margin: 0 }}>
              Riwayat Pemeriksaan Kelengkapan Unit per Sekolah
            </h3>
            <p style={{ fontSize: '0.8rem', color: 'var(--color-text-muted)', margin: '2px 0 0 0' }}>
              Catatan historis kelengkapan komponen, modul, dan box MLK pada tanggal inspeksi tertentu setelah masa sewa sekolah selesai.
            </p>
          </div>

          {unitTracking.isLoading ? (
            <LoadingState label="Memuat riwayat kelengkapan..." />
          ) : qcInspections.length === 0 ? (
            <EmptyState
              title="Belum ada riwayat kelengkapan QC pengembalian sewa"
              description="Gunakan tombol 'Inspeksi Kelengkapan QC' di pojok kanan atas untuk memeriksa kelengkapan unit saat sekolah menyelesaikan masa sewa."
              action={
                <Button variant="primary" icon={<ClipboardCheck size={16} />} onClick={() => setIsQcOpen(true)}>
                  Inspeksi Kelengkapan QC
                </Button>
              }
            />
          ) : (
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th>Tanggal Pemeriksaan</th>
                    <th>Pihak Sekolah Penyewa</th>
                    <th>Nomor QC / Referensi</th>
                    <th>Status Kelengkapan</th>
                    <th>Klaim Ganti Rugi Sekolah</th>
                    <th>Petugas QC</th>
                    <th style={{ width: '130px', textAlign: 'center' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {qcInspections.map((qi: any) => {
                    const hasLoss = qi.total_claim_amount && Number(qi.total_claim_amount) > 0;
                    return (
                      <tr key={qi.id}>
                        <td className="font-mono text-xs">{formatDate(qi.inspected_at)}</td>
                        <td>
                          <div className="font-semibold text-[var(--color-primary-dark, #263f7d)]">
                            {qi.customer_name || 'Sekolah Mitra'}
                          </div>
                        </td>
                        <td>
                          <code className="text-xs font-semibold">{qi.inspection_no}</code>
                          {qi.notes && <div className="text-xs text-[var(--color-text-muted)] mt-0.5">{qi.notes}</div>}
                        </td>
                        <td>
                          <span
                            className="chip"
                            style={{
                              background: qi.status === 'PASSED' ? '#dcfce7' : '#fef3c7',
                              color: qi.status === 'PASSED' ? '#15803d' : '#b45309',
                              fontWeight: 700,
                              fontSize: '0.75rem',
                            }}
                          >
                            {qi.status === 'PASSED' ? 'LENGKAP (PASSED)' : 'KOMPONEN HILANG'}
                          </span>
                        </td>
                        <td>
                          {hasLoss ? (
                            <span className="font-mono font-bold text-red-600">
                              {formatRupiah(qi.total_claim_amount)}
                            </span>
                          ) : (
                            <span className="text-xs text-emerald-700 font-semibold">Rp 0 (Tidak ada klaim)</span>
                          )}
                        </td>
                        <td className="text-xs">{qi.inspector_name || 'Petugas QC'}</td>
                        <td style={{ textAlign: 'center' }}>
                          <Button
                            size="sm"
                            variant="secondary"
                            icon={<Eye size={14} />}
                            onClick={() => setSelectedInspection(qi)}
                          >
                            Rincian
                          </Button>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </section>
      )}

      {/* Modal Buat Order Sewa Sekolah Baru */}
      {isNewOpen && (
        <NewSchoolRentalModal
          onClose={() => setIsNewOpen(false)}
          onSuccess={(createdOrder) => {
            qc.invalidateQueries({ queryKey: ['rental-orders'] });
            qc.invalidateQueries({ queryKey: ['rental-orders', 'units-tracking'] });
            setIsNewOpen(false);
            if (createdOrder?.is_stock_shortage) {
              notify(
                `Order sewa dibuat. Perhatian: Stok Finished Good di gudang kurang (${formatNumber(createdOrder.shortage_qty ?? 0)} unit).`,
                'info'
              );
            } else {
              notify('Order sewa sekolah berhasil dibuat.', 'success');
            }
            if (createdOrder) {
              setBarcodeRental(createdOrder);
            }
          }}
        />
      )}

      {/* Modal Cetak Barcode Unit Sewa MLK (Label Netral Tanpa Nama Sekolah) */}
      {barcodeRental && (
        <SchoolRentalBarcodeModal
          rental={barcodeRental}
          onClose={() => setBarcodeRental(null)}
        />
      )}

      {/* Modal Cetak Surat DO (Delivery Order) */}
      {deliveryOrderData && (
        <DeliveryOrderModal
          data={deliveryOrderData}
          onClose={() => setDeliveryOrderData(null)}
        />
      )}

      {/* Modal Inspeksi Kelengkapan QC Pengembalian Sewa */}
      {isQcOpen && (
        <QualityInspectionModal
          isOpen={isQcOpen}
          onClose={() => setIsQcOpen(false)}
          onSuccess={() => {
            qc.invalidateQueries({ queryKey: ['rental-orders'] });
            qc.invalidateQueries({ queryKey: ['rental-orders', 'units-tracking'] });
            setIsQcOpen(false);
            notify('Inspeksi kelengkapan berhasil disimpan. Data otomatis terhubung ke riwayat kelengkapan.', 'success');
          }}
        />
      )}

      {/* Modal Detail Kelengkapan QC */}
      {selectedInspection && (
        <InspectionDetailModal
          inspection={selectedInspection}
          onClose={() => setSelectedInspection(null)}
        />
      )}

      {/* Modal Scan Barcode Finished Good & Unit Kamera HP */}
      {isUnitScanOpen && (
        <BarcodeScanModal
          isOpen={isUnitScanOpen}
          initialBarcode={scannedUnitCode}
          onClose={() => {
            setIsUnitScanOpen(false);
            setScannedUnitCode(undefined);
          }}
        />
      )}
    </>
  );
}

function InspectionDetailModal({
  inspection,
  onClose,
}: {
  inspection: any;
  onClose: () => void;
}) {
  const discrepancies = inspection.discrepancies ?? [];

  return (
    <Modal
      open
      title={`Rincian Kelengkapan Unit — ${inspection.customer_name || 'Sekolah Mitra'}`}
      description={`Dokumen: ${inspection.inspection_no} &bull; Tanggal Pemeriksaan: ${formatDate(inspection.inspected_at)}`}
      size="lg"
      onClose={onClose}
      footer={<Button onClick={onClose}>Tutup</Button>}
    >
      <div className="grid grid--2 mb-4 p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs">
        <div>
          <span className="text-slate-500 block">Pihak Sekolah:</span>
          <strong className="text-slate-900 text-sm">{inspection.customer_name}</strong>
          <span className="text-slate-500 block mt-2">Status Pemeriksaan:</span>
          <span className={`chip ${inspection.status === 'PASSED' ? 'chip--success' : 'chip--warning'}`}>
            {inspection.status === 'PASSED' ? 'Lengkap (PASSED)' : 'Ada Kekurangan / Komponen Hilang'}
          </span>
        </div>
        <div>
          <span className="text-slate-500 block">Petugas Pemeriksa:</span>
          <strong>{inspection.inspector_name || 'Petugas QC'}</strong>
          <span className="text-slate-500 block mt-2">Total Klaim Ganti Rugi:</span>
          <strong className="text-red-600 text-sm font-mono">
            {formatRupiah(inspection.total_claim_amount || 0)}
          </strong>
        </div>
      </div>

      {discrepancies.length === 0 ? (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-3 text-xs text-emerald-900">
          <CheckCircle2 size={18} className="text-emerald-600 shrink-0" />
          <div>
            <strong>Semua Item Lengkap:</strong> Seluruh komponen, modul, dan box Finished Good MLK yang disewa oleh pihak sekolah telah diperiksa dan dikembalikan dalam kondisi lengkap tanpa kekurangan.
          </div>
        </div>
      ) : (
        <div>
          <h4 className="text-xs font-bold uppercase text-slate-600 mb-2">
            Daftar Komponen / Modul / Box yang Hilang & Nilai Ganti Rugi:
          </h4>
          <div className="table-wrap">
            <table className="table" style={{ fontSize: '0.8rem' }}>
              <thead>
                <tr>
                  <th>Tingkat</th>
                  <th>Kode Produk</th>
                  <th>Nama Item / Komponen</th>
                  <th>Jumlah Hilang</th>
                  <th>Harga Acuan</th>
                  <th>Estimasi Klaim</th>
                  <th>Ganti Rugi?</th>
                </tr>
              </thead>
              <tbody>
                {discrepancies.map((d: any) => (
                  <tr key={d.id}>
                    <td>
                      <span className="chip" style={{ fontSize: '0.7rem' }}>
                        {d.component_tier || 'KOMPONEN'}
                      </span>
                    </td>
                    <td>
                      <code>{d.product_code}</code>
                    </td>
                    <td className="font-semibold">{d.product_name}</td>
                    <td className="font-bold text-red-600">
                      {formatNumber(d.quantity)} Unit
                    </td>
                    <td className="font-mono">{formatRupiah(d.reference_unit_price || 0)}</td>
                    <td className="font-mono font-bold text-red-700">
                      {formatRupiah(d.estimated_claim_amount || 0)}
                    </td>
                    <td>
                      {d.is_chargeable ? (
                        <span className="text-red-700 font-bold text-xs">Ya (Klaim)</span>
                      ) : (
                        <span className="text-slate-500 text-xs">Tidak (Bebas)</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </Modal>
  );
}

function NewSchoolRentalModal({
  onClose,
  onSuccess,
}: {
  onClose: () => void;
  onSuccess: (order: SchoolRentalRecord) => void;
}) {
  const { notify } = useToast();
  const customers = useCustomers();
  const productsQuery = useProducts({ per_page: 100 });

  // Filter produk finished good / fisik yang dapat disewakan
  const finishedGoods = useMemo(() => {
    const list = productsQuery.data?.data ?? [];
    const filtered = list.filter((p) =>
      p.physical_type === 'PHYSICAL' && (
        p.category?.code === 'FG' ||
        p.category?.name?.toLowerCase().includes('finished') ||
        p.category?.name?.toLowerCase().includes('kit') ||
        p.code === '1001' ||
        p.is_manufactured ||
        p.is_rentable
      )
    );
    return filtered.length > 0 ? filtered : list.filter((p) => p.physical_type === 'PHYSICAL');
  }, [productsQuery.data?.data]);

  const [form, setForm] = useState({
    customer_id: '',
    product_id: '',
    total_units: 10,
    start_date: new Date().toISOString().slice(0, 10),
    start_unit_no: '',
    notes: '',
  });

  // Default ke produk MLK (kode 1001) atau produk FG pertama
  useEffect(() => {
    if (!form.product_id && finishedGoods.length > 0) {
      const mlkProd = finishedGoods.find((p) => p.code === '1001') || finishedGoods[0];
      if (mlkProd) {
        setForm((prev) => ({ ...prev, product_id: String(mlkProd.id) }));
      }
    }
  }, [finishedGoods, form.product_id]);

  const selectedProductId = form.product_id ? Number(form.product_id) : null;
  const selectedProduct = useMemo(() => {
    return finishedGoods.find((p) => p.id === selectedProductId);
  }, [finishedGoods, selectedProductId]);

  const stockQuery = useProductStock(selectedProductId);

  const availableFgStock = useMemo(() => {
    return (stockQuery.data ?? [])
      .filter((b) => b.stock_status === 'AVAILABLE')
      .reduce((sum, b) => sum + Number(b.available_qty ?? b.on_hand_qty ?? 0), 0);
  }, [stockQuery.data]);

  const requestedUnits = Math.max(1, Number(form.total_units) || 1);
  const isStockShortage = availableFgStock < requestedUnits;
  const shortageQty = Math.max(0, requestedUnits - availableFgStock);

  const save = useMutation({
    mutationFn: async () => {
      const res = await http.post<{ data: SchoolRentalRecord }>('/rental-orders', {
        customer_id: Number(form.customer_id),
        product_id: Number(form.product_id),
        total_units: requestedUnits,
        start_date: form.start_date,
        start_unit_no: form.start_unit_no ? Number(form.start_unit_no) : undefined,
        notes: form.notes.trim() || null,
        order_type: 'SCHOOL',
      });
      return res.data.data;
    },
    onSuccess: (data) => {
      onSuccess(data);
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message || err?.message || 'Gagal menyimpan order sewa sekolah.', 'error');
    },
  });

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!form.customer_id) {
      notify('Pihak sekolah mitra wajib dipilih.', 'error');
      return;
    }
    if (!form.product_id) {
      notify('Jenis produk Finished Good wajib dipilih.', 'error');
      return;
    }
    if (!form.total_units || Number(form.total_units) <= 0) {
      notify('Banyak produk yang ingin dipesan sekolah wajib lebih dari 0.', 'error');
      return;
    }
    if (!form.start_date) {
      notify('Tanggal mulai sewa wajib diisi.', 'error');
      return;
    }
    save.mutate();
  };

  return (
    <Modal
      open
      title="Buat Order Sewa Finished Good (Pihak Sekolah)"
      description="Order sewa finished good mengambil jenis produk dan jumlah yang dipesan sekolah dari stok gudang. Jika stok fisik kurang, sistem akan memberikan keterangan stok kurang."
      onClose={onClose}
      footer={
        <>
          <Button onClick={onClose}>Batal</Button>
          <Button variant="primary" type="submit" form="new-school-rental-form" loading={save.isPending}>
            Simpan & Terbitkan Order Sewa
          </Button>
        </>
      }
    >
      <form id="new-school-rental-form" onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
        {/* 1. Pihak Sekolah */}
        <FormField
          label="Pihak Sekolah Mitra (Customer)"
          htmlFor="rent-cust"
          required
          hint="Pilih sekolah mitra yang memesan sewa unit finished good"
        >
          <select
            id="rent-cust"
            className="select font-semibold"
            value={form.customer_id}
            onChange={(e) => setForm({ ...form, customer_id: e.target.value })}
            required
          >
            <option value="">Pilih sekolah mitra penyewa...</option>
            {customers.data?.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name} {c.code ? `(${c.code})` : ''} {c.sales_name ? `— Sales: ${c.sales_name}` : ''}
              </option>
            ))}
          </select>
        </FormField>

        {/* 2. Jenis Produk Finished Good */}
        <FormField
          label="Jenis Produk Finished Good"
          htmlFor="rent-product"
          required
          hint="Pilih produk finished good yang akan disewa (misal: Mobile Learning Kit / MLK)"
        >
          <select
            id="rent-product"
            className="select font-semibold"
            value={form.product_id}
            onChange={(e) => setForm({ ...form, product_id: e.target.value })}
            required
          >
            <option value="">Pilih jenis produk finished good...</option>
            {finishedGoods.map((p) => (
              <option key={p.id} value={p.id}>
                [{p.code}] {p.name} {p.category?.name ? `(${p.category.name})` : ''}
              </option>
            ))}
          </select>
        </FormField>

        {/* 3. Banyak Produk yang Ingin Dipesan Sekolah */}
        <FormField
          label="Banyak Produk yang Ingin Dipesan Sekolah (Unit)"
          htmlFor="rent-total-units"
          required
          hint="Masukkan total kuantitas unit finished good yang dibutuhkan sekolah"
        >
          <input
            id="rent-total-units"
            type="number"
            min="1"
            className="input font-mono font-bold text-base"
            placeholder="Contoh: 10"
            value={form.total_units}
            onChange={(e) => setForm({ ...form, total_units: Math.max(1, parseInt(e.target.value) || 0) })}
            required
          />
        </FormField>

        {/* 4. Keterangan Ketersediaan Stok Finished Good di Gudang */}
        {selectedProduct && (
          <div style={{ marginTop: 2, marginBottom: 2 }}>
            {stockQuery.isLoading ? (
              <div style={{ fontSize: '0.8rem', color: 'var(--color-text-muted)' }}>
                Memeriksa stok fisik Finished Good di gudang...
              </div>
            ) : isStockShortage ? (
              <div
                style={{
                  background: '#fef2f2',
                  border: '1.5px solid #fecaca',
                  borderRadius: 6,
                  padding: '12px 14px',
                  color: '#991b1b',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: 10 }}>
                  <span style={{ fontSize: 20, lineHeight: 1 }}>⚠️</span>
                  <div style={{ flex: 1 }}>
                    <div style={{ fontWeight: 700, fontSize: '0.875rem' }}>
                      Keterangan: Stok Finished Good di Gudang Kurang!
                    </div>
                    <div style={{ fontSize: '0.825rem', marginTop: 4, lineHeight: 1.4 }}>
                      Tersedia di gudang: <strong>{formatNumber(availableFgStock)} unit</strong> &bull; Dipesan sekolah: <strong>{formatNumber(requestedUnits)} unit</strong> &bull; <span style={{ color: '#dc2626', fontWeight: 800 }}>Kekurangan: {formatNumber(shortageQty)} unit</span>
                    </div>
                    <div style={{ fontSize: '0.75rem', marginTop: 6, color: '#7f1d1d', lineHeight: 1.35 }}>
                      Order sewa tetap dapat dibuat dengan catatan kekurangan stok. Segera jadwalkan rencana produksi dan perakitan tambahan di modul Operasi.
                    </div>
                  </div>
                </div>
              </div>
            ) : (
              <div
                style={{
                  background: '#f0fdf4',
                  border: '1.5px solid #bbf7d0',
                  borderRadius: 6,
                  padding: '12px 14px',
                  color: '#166534',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: 10 }}>
                  <span style={{ fontSize: 20, lineHeight: 1 }}>✓</span>
                  <div style={{ flex: 1 }}>
                    <div style={{ fontWeight: 700, fontSize: '0.875rem' }}>
                      Stok Finished Good di Gudang Mencukupi ({formatNumber(availableFgStock)} Unit Tersedia)
                    </div>
                    <div style={{ fontSize: '0.825rem', marginTop: 4 }}>
                      Stok gudang mencukupi untuk memenuhi pesanan sekolah sejumlah <strong>{formatNumber(requestedUnits)} unit</strong>. Unit serial barcode siap dialokasikan.
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}

        {/* 5. Tanggal Mulai Sewa */}
        <FormField label="Tanggal Mulai Sewa" htmlFor="rent-start" required>
          <input
            id="rent-start"
            type="date"
            className="input"
            value={form.start_date}
            onChange={(e) => setForm({ ...form, start_date: e.target.value })}
            required
          />
        </FormField>

        {/* 6. Opsi Nomor Unit Serial (Pakai Ulang Unit yang Ada) */}
        <FormField
          label="Nomor Awal Unit (Opsional — Pakai Ulang Nomor Unit)"
          htmlFor="rent-start-unit"
          hint="Kosongkan untuk penomoran otomatis berlanjut, atau isi nomor awal (misal: 1) jika ingin menggunakan kembali nomor unit yang telah dikembalikan ke gudang."
        >
          <input
            id="rent-start-unit"
            type="number"
            min="1"
            className="input font-mono"
            placeholder="Otomatis lanjut nomor baru (atau isi misal: 1 untuk pakai ulang 1001_01)"
            value={form.start_unit_no}
            onChange={(e) => setForm({ ...form, start_unit_no: e.target.value })}
          />
        </FormField>

        {/* 7. Catatan Tambahan */}
        <FormField label="Catatan Tambahan (Opsional)" htmlFor="rent-notes">
          <textarea
            id="rent-notes"
            className="textarea"
            rows={2}
            placeholder="Keterangan sewa, nomor PO sekolah, atau instruksi serah terima..."
            value={form.notes}
            onChange={(e) => setForm({ ...form, notes: e.target.value })}
          />
        </FormField>
      </form>
    </Modal>
  );
}
