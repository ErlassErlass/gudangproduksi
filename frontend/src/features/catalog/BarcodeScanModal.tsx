import { useState, useRef, useEffect, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  AlertCircle,
  AlertTriangle,
  Barcode as BarcodeIcon,
  Camera,
  CheckCircle2,
  ClipboardCheck,
  History,
  Printer,
  QrCode,
  RotateCw,
  School,
  Search,
  Sparkles,
} from 'lucide-react';
import { http } from '@/api/client';
import type { ResourceResponse } from '@/api/types';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useCustomers } from '@/features/master-data/api';
import { formatDate, formatDateTime, formatNumber, formatRupiah } from '@/lib/format';
import type { ProductItem } from '@/features/master-data/api';

interface ScannedProduct {
  id: number;
  code: string;
  name: string;
  description: string | null;
  category: { id: number; code: string; name: string } | null;
  base_uom: { id: number; code: string; name: string } | null;
  barcode: string;
  available_qty: string;
  total_on_hand: string;
  last_printed_at: string | null;
  total_print_batches: number;
}

interface BarcodeScanModalProps {
  isOpen: boolean;
  onClose: () => void;
  initialBarcode?: string;
  onOpenBarcodePrint?: (product: ProductItem) => void;
  onOpenStockMovement?: (product: ProductItem) => void;
}

export function BarcodeScanModal({
  isOpen,
  onClose,
  initialBarcode,
  onOpenBarcodePrint,
  onOpenStockMovement,
}: BarcodeScanModalProps) {
  const qc = useQueryClient();
  const { notify } = useToast();
  const customers = useCustomers();

  const [barcodeInput, setBarcodeInput] = useState(initialBarcode || '');
  const [activeBarcode, setActiveBarcode] = useState(initialBarcode || '');

  // State Kamera Scanner
  const [useCamera, setUseCamera] = useState(true);
  const [cameraError, setCameraError] = useState<string | null>(null);
  const [facingMode, setFacingMode] = useState<'environment' | 'user'>('environment');
  const [isScanning, setIsScanning] = useState(false);

  // Form Edit Penugasan Sekolah & Status Unit MLK
  const [editSchoolId, setEditSchoolId] = useState<number | ''>('');
  const [editStatus, setEditStatus] = useState<'ACTIVE' | 'RETURNED'>('ACTIVE');

  const videoRef = useRef<HTMLVideoElement>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const scanIntervalRef = useRef<any>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (isOpen) {
      if (initialBarcode) {
        setBarcodeInput(initialBarcode);
        setActiveBarcode(initialBarcode);
      }
      setTimeout(() => {
        inputRef.current?.focus();
      }, 200);
    } else {
      stopCamera();
      if (!initialBarcode) {
        setBarcodeInput('');
        setActiveBarcode('');
      }
    }
  }, [isOpen, initialBarcode]);

  // Query 1: Deteksi Produk Finished Good dari Barcode
  const productQuery = useQuery({
    queryKey: ['products', 'scan', activeBarcode],
    enabled: Boolean(activeBarcode),
    queryFn: async () => {
      const res = await http.get<{ data: ScannedProduct }>(`/products/scan/${encodeURIComponent(activeBarcode)}`);
      return res.data.data;
    },
    retry: false,
  });

  // Query 2: Deteksi Unit Fisik MLK dari Barcode
  const unitQuery = useQuery({
    queryKey: ['rental-orders', 'units', 'scan', activeBarcode],
    enabled: Boolean(activeBarcode),
    queryFn: async () => {
      const res = await http.get<ResourceResponse<any>>(`/rental-orders/units/scan/${encodeURIComponent(activeBarcode)}`);
      return res.data.data;
    },
    retry: false,
  });

  // Sinkronisasi data unit ke form edit saat data unit tersedia
  useEffect(() => {
    if (unitQuery.data) {
      const u = unitQuery.data;
      if (u.current_rental) {
        setEditSchoolId(u.current_rental.customer_id || '');
        setEditStatus(u.current_rental.status === 'ACTIVE' || u.current_rental.status === 'DISPATCHED' ? 'ACTIVE' : 'RETURNED');
      } else {
        setEditSchoolId('');
        setEditStatus('RETURNED');
      }
    }
  }, [unitQuery.data]);

  // Mutation simpan perubahan penugasan sekolah & status unit MLK
  const updateAssignment = useMutation({
    mutationFn: async () => {
      if (!unitQuery.data?.current_rental?.id) {
        throw new Error('Unit ini belum terkait dengan kontrak sewa.');
      }
      const res = await http.patch('/rental-orders/units/update-assignment', {
        rental_id: unitQuery.data.current_rental.id,
        customer_id: editSchoolId ? Number(editSchoolId) : undefined,
        status: editStatus,
      });
      return res.data;
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['rental-orders'] });
      qc.invalidateQueries({ queryKey: ['rental-orders', 'units', 'scan', activeBarcode] });
      qc.invalidateQueries({ queryKey: ['rental-orders', 'units-tracking'] });
      notify('Data sekolah dan status unit MLK berhasil diperbarui.', 'success');
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message || err.message || 'Gagal memperbarui unit.', 'error');
    },
  });

  // Kamera Control: Start & Stop
  const startCamera = async () => {
    setCameraError(null);
    try {
      if (streamRef.current) {
        streamRef.current.getTracks().forEach((t) => t.stop());
      }

      if (!navigator?.mediaDevices?.getUserMedia) {
        throw new Error('Perangkat browser ini tidak mendukung akses kamera langsung.');
      }

      const stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: { ideal: facingMode },
          width: { ideal: 1280 },
          height: { ideal: 720 },
        },
        audio: false,
      });

      streamRef.current = stream;
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        videoRef.current.play();
        setIsScanning(true);
      }
    } catch (err: any) {
      console.warn('Camera access error:', err);
      setCameraError(err.message || 'Tidak dapat mengakses kamera perangkat.');
      setIsScanning(false);
      setUseCamera(false);
    }
  };

  const stopCamera = () => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach((t) => t.stop());
      streamRef.current = null;
    }
    if (scanIntervalRef.current) {
      clearInterval(scanIntervalRef.current);
      scanIntervalRef.current = null;
    }
    setIsScanning(false);
  };

  const handleToggleFacing = () => {
    setFacingMode((prev) => (prev === 'environment' ? 'user' : 'environment'));
  };

  useEffect(() => {
    if (isOpen && useCamera) {
      startCamera();
    } else {
      stopCamera();
    }
    return () => {
      stopCamera();
    };
  }, [isOpen, useCamera, facingMode]);

  // Barcode Detection Loop (BarcodeDetector API)
  useEffect(() => {
    if (!isScanning || !useCamera || !videoRef.current) return;

    const hasBarcodeDetector = 'BarcodeDetector' in window;
    let detector: any = null;

    if (hasBarcodeDetector) {
      try {
        detector = new (window as any).BarcodeDetector({
          formats: ['code_128', 'code_39', 'qr_code', 'ean_13', 'ean_8'],
        });
      } catch (e) {
        detector = null;
      }
    }

    if (!detector) return;

    scanIntervalRef.current = setInterval(async () => {
      if (!videoRef.current || videoRef.current.readyState < 2) return;
      try {
        const barcodes = await detector.detect(videoRef.current);
        if (barcodes && barcodes.length > 0) {
          const rawValue = barcodes[0].rawValue?.trim();
          if (rawValue && rawValue !== activeBarcode) {
            setBarcodeInput(rawValue);
            setActiveBarcode(rawValue);
            if (navigator?.vibrate) {
              navigator.vibrate(100);
            }
          }
        }
      } catch (err) {
        // Abaikan error frame transien
      }
    }, 400);

    return () => {
      if (scanIntervalRef.current) {
        clearInterval(scanIntervalRef.current);
      }
    };
  }, [isScanning, useCamera, activeBarcode]);

  const handleScanSubmit = (e: FormEvent) => {
    e.preventDefault();
    const trimmed = barcodeInput.trim();
    if (trimmed) {
      setActiveBarcode(trimmed);
    }
  };

  const handleQuickPrint = () => {
    if (!productQuery.data || !onOpenBarcodePrint) return;
    const data = productQuery.data;
    const item: ProductItem = {
      id: data.id,
      code: data.code,
      legacy_code: null,
      name: data.name,
      description: data.description,
      category_id: data.category?.id ?? null,
      category: data.category,
      product_type: 'PRODUCT',
      physical_type: 'PHYSICAL',
      fulfillment_type: 'SELL',
      tracking_method: 'QUANTITY',
      base_uom_id: data.base_uom?.id ?? 1,
      safety_stock_qty: '0.0000',
      reference_purchase_price: null,
      is_manufactured: true,
      is_purchasable: false,
      is_sellable: true,
      is_rentable: false,
      status: 'ACTIVE',
      available_qty: data.available_qty,
      barcodes: [{ id: 1, barcode: data.barcode, barcode_type: 'CODE128', is_primary: true }],
    };
    onClose();
    onOpenBarcodePrint(item);
  };

  const handleQuickMovement = () => {
    if (!productQuery.data || !onOpenStockMovement) return;
    const data = productQuery.data;
    const item: ProductItem = {
      id: data.id,
      code: data.code,
      legacy_code: null,
      name: data.name,
      description: data.description,
      category_id: data.category?.id ?? null,
      category: data.category,
      product_type: 'PRODUCT',
      physical_type: 'PHYSICAL',
      fulfillment_type: 'SELL',
      tracking_method: 'QUANTITY',
      base_uom_id: data.base_uom?.id ?? 1,
      safety_stock_qty: '0.0000',
      reference_purchase_price: null,
      is_manufactured: true,
      is_purchasable: false,
      is_sellable: true,
      is_rentable: false,
      status: 'ACTIVE',
      available_qty: data.available_qty,
    };
    onClose();
    onOpenStockMovement(item);
  };

  const hasProduct = Boolean(productQuery.data);
  const hasUnit = Boolean(unitQuery.data);
  const isLoadingAny = productQuery.isLoading || unitQuery.isLoading;
  const isNotFound = Boolean(activeBarcode && !isLoadingAny && !hasProduct && !hasUnit);

  const unitData = unitQuery.data;
  const productData = productQuery.data;

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title="Scan Barcode Finished Good & Unit MLK"
      description="Gunakan kamera perangkat (mobile/HP) atau scanner barcode fisik untuk memindai barcode Finished Good dan nomor unit fisik sewa MLK."
      size="lg"
      footer={
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', width: '100%' }}>
          <div style={{ fontSize: '0.75rem', color: '#64748b' }}>
            {activeBarcode ? (
              <span>
                Barcode Aktif: <code>{activeBarcode}</code>
              </span>
            ) : (
              <span>Arahkan kamera atau ketik barcode</span>
            )}
          </div>
          <Button variant="secondary" onClick={onClose}>
            Tutup
          </Button>
        </div>
      }
    >
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        {/* Toggle & Kontrol Kamera Device / Mobile */}
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            background: '#f8fafc',
            border: '1px solid #e2e8f0',
            borderRadius: 8,
            padding: '8px 12px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <Camera size={16} style={{ color: useCamera ? '#15803d' : '#64748b' }} />
            <span style={{ fontSize: '0.85rem', fontWeight: 600, color: 'var(--color-primary-dark, #263f7d)' }}>
              Kamera Perangkat: {useCamera ? 'Aktif' : 'Nonaktif'}
            </span>
          </div>

          <div style={{ display: 'flex', gap: 8 }}>
            {useCamera && (
              <Button
                size="sm"
                variant="secondary"
                icon={<RotateCw size={13} />}
                onClick={handleToggleFacing}
                title="Ganti Kamera Belakang / Depan"
              >
                Putar ({facingMode === 'environment' ? 'Belakang' : 'Depan'})
              </Button>
            )}
            <Button
              size="sm"
              variant={useCamera ? 'secondary' : 'primary'}
              onClick={() => setUseCamera((prev) => !prev)}
            >
              {useCamera ? 'Tutup Kamera' : 'Buka Kamera'}
            </Button>
          </div>
        </div>

        {/* Viewfinder Kamera Live */}
        {useCamera && (
          <div
            style={{
              position: 'relative',
              width: '100%',
              maxHeight: 280,
              height: 240,
              backgroundColor: '#0f172a',
              borderRadius: 12,
              overflow: 'hidden',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              border: '2px solid var(--color-primary, #3859ac)',
            }}
          >
            <video
              ref={videoRef}
              style={{
                width: '100%',
                height: '100%',
                objectFit: 'cover',
              }}
              playsInline
              muted
            />

            {/* Reticle Bingkai Bidik & Laser Scan */}
            <div
              style={{
                position: 'absolute',
                width: '75%',
                maxWidth: 320,
                height: '65%',
                border: '2px solid rgba(255, 255, 255, 0.85)',
                borderRadius: 12,
                boxShadow: '0 0 0 4000px rgba(15, 23, 42, 0.45)',
                pointerEvents: 'none',
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'space-between',
              }}
            >
              {/* Garis Pemindai Laser Animasi */}
              <div
                style={{
                  height: 2,
                  backgroundColor: '#ef4444',
                  boxShadow: '0 0 10px #ef4444, 0 0 5px #ef4444',
                  animation: 'pulse 1.5s infinite',
                }}
              />
              <div
                style={{
                  textAlign: 'center',
                  color: '#ffffff',
                  fontSize: '0.75rem',
                  fontWeight: 600,
                  textShadow: '0 1px 2px rgba(0,0,0,0.8)',
                  paddingBottom: 6,
                }}
              >
                Posisikan Barcode atau QR Code di Dalam Bingkai
              </div>
            </div>
          </div>
        )}

        {cameraError && (
          <div className="alert alert--danger" style={{ margin: 0, fontSize: '0.825rem' }}>
            <AlertTriangle size={16} />
            <span>{cameraError}</span>
          </div>
        )}

        {/* Form Input Scanner Teks / USB Scanner Barcode */}
        <form onSubmit={handleScanSubmit} style={{ display: 'flex', gap: 8 }}>
          <div style={{ position: 'relative', flex: 1 }}>
            <input
              ref={inputRef}
              type="text"
              className="input font-mono"
              placeholder="Arahkan scanner USB / ketik nomor barcode (misal: 1001 atau 1001_01)..."
              value={barcodeInput}
              onChange={(e) => setBarcodeInput(e.target.value)}
              style={{ paddingLeft: 38, fontSize: '0.95rem', fontWeight: 600 }}
              autoComplete="off"
            />
            <QrCode
              size={18}
              style={{
                position: 'absolute',
                left: 12,
                top: '50%',
                transform: 'translateY(-50%)',
                color: 'var(--color-primary, #3859ac)',
              }}
            />
          </div>
          <Button type="submit" variant="primary" icon={<Search size={16} />} loading={isLoadingAny}>
            Deteksi
          </Button>
        </form>

        {/* State Loading */}
        {isLoadingAny && <LoadingState label="Mendeteksi barcode Finished Good / Unit MLK..." />}

        {/* State Not Found */}
        {isNotFound && (
          <div className="alert alert--danger" style={{ display: 'flex', alignItems: 'center', gap: 10, margin: 0 }}>
            <AlertCircle size={20} />
            <div>
              <strong>Barcode Tidak Ditemukan:</strong> Kode <code>{activeBarcode}</code> tidak terdaftar sebagai produk Finished Good maupun nomor unit sewa MLK yang valid.
            </div>
          </div>
        )}

        {/* 1. HASIL DETEKSI PRODUK FINISHED GOOD */}
        {productData && (
          <div
            style={{
              padding: 16,
              borderRadius: 'var(--radius-md, 12px)',
              background: 'var(--color-surface-muted, #f7f9fe)',
              border: '1px solid var(--color-border, #e1e8f5)',
              display: 'flex',
              flexDirection: 'column',
              gap: 12,
            }}
          >
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
              <div>
                <span className="chip" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 700, marginBottom: 6 }}>
                  <CheckCircle2 size={12} style={{ display: 'inline', marginRight: 4 }} />
                  Finished Good Terdeteksi
                </span>
                <h3 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 800, color: 'var(--color-primary-dark, #263f7d)' }}>
                  {productData.name}
                </h3>
                <div style={{ fontSize: '0.85rem', color: '#64748b', marginTop: 2 }}>
                  Kode Produk: <code>{productData.code}</code> &bull; Kategori: {productData.category?.name ?? 'Finished Good'}
                </div>
              </div>
            </div>

            {/* Grid Informasi Stok & Barcode */}
            <div className="grid grid--2" style={{ gap: 10, marginTop: 4 }}>
              <div
                style={{
                  background: '#ffffff',
                  padding: 10,
                  borderRadius: 8,
                  border: '1px solid #e2e8f0',
                }}
              >
                <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Stok Fisik Tersedia</div>
                <div style={{ fontSize: '1.25rem', fontWeight: 800, color: '#15803d', fontFamily: 'monospace' }}>
                  {formatNumber(productData.available_qty)} {productData.base_uom?.code ?? 'Unit'}
                </div>
              </div>

              <div
                style={{
                  background: '#ffffff',
                  padding: 10,
                  borderRadius: 8,
                  border: '1px solid #e2e8f0',
                }}
              >
                <div style={{ fontSize: '0.75rem', color: '#64748b' }}>Nomor Barcode Fisik</div>
                <div style={{ fontSize: '1.1rem', fontWeight: 800, color: 'var(--color-primary, #3859ac)', fontFamily: 'monospace' }}>
                  {productData.barcode}
                </div>
              </div>
            </div>

            {/* Info Riwayat Cetak Terakhir */}
            <div style={{ fontSize: '0.8rem', color: '#64748b', borderTop: '1px solid #e2e8f0', paddingTop: 8 }}>
              Terakhir dicetak: <strong>{productData.last_printed_at ? formatDateTime(productData.last_printed_at) : 'Belum pernah dicetak'}</strong> &bull; Total Riwayat Batch: <strong>{productData.total_print_batches} kali</strong>
            </div>

            {/* Tombol Aksi Cepat Produk */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, marginTop: 6 }}>
              {onOpenBarcodePrint && (
                <Button
                  size="sm"
                  variant="primary"
                  icon={<BarcodeIcon size={14} />}
                  onClick={handleQuickPrint}
                >
                  Cetak / Pengeluaran Barcode
                </Button>
              )}
              {onOpenStockMovement && (
                <Button
                  size="sm"
                  variant="secondary"
                  icon={<Printer size={14} />}
                  onClick={handleQuickMovement}
                >
                  Riwayat Keluar Masuk
                </Button>
              )}
            </div>
          </div>
        )}

        {/* 2. HASIL DETEKSI UNIT FISIK MLK (SEWA SEKOLAH) */}
        {unitData && (
          <div
            style={{
              padding: 16,
              borderRadius: 'var(--radius-md, 12px)',
              background: '#f8fafc',
              border: '1px solid #cbd5e1',
              display: 'flex',
              flexDirection: 'column',
              gap: 14,
            }}
          >
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: 8 }}>
              <div>
                <span className="chip" style={{ background: '#dbeafe', color: '#1e40af', fontWeight: 700, marginBottom: 6 }}>
                  <Sparkles size={12} style={{ display: 'inline', marginRight: 4 }} />
                  Nomor Unit Fisik MLK Terdeteksi
                </span>
                <h3 style={{ margin: 0, fontSize: '1.15rem', fontWeight: 800, color: 'var(--color-primary-dark, #263f7d)' }}>
                  Unit {unitData.unit_code}
                </h3>
                <div style={{ fontSize: '0.85rem', color: '#64748b', marginTop: 2 }}>
                  Produk: <strong>{unitData.product_name}</strong> &bull; Nomor Serial: <code>{unitData.unit_no}</code>
                </div>
              </div>

              <div>
                {unitData.current_status === 'IN_USE' ? (
                  <span className="chip" style={{ background: '#dbeafe', color: '#1d4ed8', fontWeight: 800, fontSize: '0.8rem' }}>
                    SEDANG DISEWA
                  </span>
                ) : (
                  <span className="chip" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 800, fontSize: '0.8rem' }}>
                    TERSEDIA DI GUDANG
                  </span>
                )}
              </div>
            </div>

            {/* Detail Lokasi / Sekolah Saat Ini */}
            <div
              style={{
                background: '#ffffff',
                border: '1px solid #e2e8f0',
                borderRadius: 8,
                padding: '12px 14px',
              }}
            >
              <div style={{ fontSize: '0.75rem', color: '#64748b', textTransform: 'uppercase', fontWeight: 700 }}>
                Status Penempatan Saat Ini:
              </div>
              <div style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--color-primary-dark, #263f7d)', marginTop: 2 }}>
                {unitData.current_school}
              </div>
              {unitData.current_rental && (
                <div style={{ fontSize: '0.8rem', color: '#64748b', marginTop: 4, fontFamily: 'monospace' }}>
                  Kontrak Sewa: {unitData.current_rental.rental_no} &bull; Mulai Sewa: {formatDate(unitData.current_rental.start_date)}
                </div>
              )}
            </div>

            {/* FORM UBAH STATUS & SEKOLAH PEMAKAI */}
            {unitData.current_rental && (
              <div
                style={{
                  background: '#f1f5f9',
                  border: '1px solid #cbd5e1',
                  borderRadius: 8,
                  padding: 12,
                  display: 'flex',
                  flexDirection: 'column',
                  gap: 10,
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                  <School size={16} style={{ color: 'var(--color-primary, #3859ac)' }} />
                  <strong style={{ fontSize: '0.875rem', color: 'var(--color-primary-dark, #263f7d)' }}>
                    Ubah Data Sekolah Pemakai & Status Unit
                  </strong>
                </div>

                <div className="grid grid--2" style={{ gap: 10 }}>
                  <FormField label="Pihak Sekolah Pemakai" htmlFor="edit-school">
                    <select
                      id="edit-school"
                      className="select"
                      value={editSchoolId}
                      onChange={(e) => setEditSchoolId(e.target.value ? Number(e.target.value) : '')}
                    >
                      <option value="">-- Pilih Sekolah --</option>
                      {customers.data?.map((c) => (
                        <option key={c.id} value={c.id}>
                          {c.code} - {c.name}
                        </option>
                      ))}
                    </select>
                  </FormField>

                  <FormField label="Status Pemakaian Unit" htmlFor="edit-status">
                    <select
                      id="edit-status"
                      className="select"
                      value={editStatus}
                      onChange={(e) => setEditStatus(e.target.value as any)}
                    >
                      <option value="ACTIVE">Masih Disewakan (ACTIVE)</option>
                      <option value="RETURNED">Selesai / Tersedia di Gudang (RETURNED)</option>
                    </select>
                  </FormField>
                </div>

                <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: 4 }}>
                  <Button
                    size="sm"
                    variant="primary"
                    loading={updateAssignment.isPending}
                    onClick={() => updateAssignment.mutate()}
                  >
                    Simpan Pembaruan Unit
                  </Button>
                </div>
              </div>
            )}

            {/* RIWAYAT QC KELENGKAPAN UNIT */}
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 6 }}>
                <ClipboardCheck size={16} style={{ color: 'var(--color-primary, #3859ac)' }} />
                <strong style={{ fontSize: '0.875rem', color: 'var(--color-primary-dark, #263f7d)' }}>
                  Riwayat Kelengkapan & QC Unit ({unitData.inspection_history?.length || 0})
                </strong>
              </div>

              {!unitData.inspection_history || unitData.inspection_history.length === 0 ? (
                <div style={{ fontSize: '0.8rem', color: '#64748b', fontStyle: 'italic', background: '#ffffff', padding: 8, borderRadius: 6 }}>
                  Belum ada catatan inspeksi pengembalian sewa untuk unit ini.
                </div>
              ) : (
                <div className="table-wrap" style={{ maxHeight: 180, overflowY: 'auto', background: '#ffffff' }}>
                  <table className="table" style={{ fontSize: '0.8rem' }}>
                    <thead>
                      <tr>
                        <th>Tgl Inspeksi</th>
                        <th>Sekolah Penyewa</th>
                        <th>Status</th>
                        <th>Klaim Ganti Rugi</th>
                        <th>Keterangan Komponen</th>
                      </tr>
                    </thead>
                    <tbody>
                      {unitData.inspection_history.map((ih: any) => (
                        <tr key={ih.id}>
                          <td>{formatDate(ih.inspected_at)}</td>
                          <td className="font-semibold">{ih.customer_name}</td>
                          <td>
                            <span
                              className="chip"
                              style={{
                                background: ih.status === 'PASSED' ? '#dcfce7' : '#fef3c7',
                                color: ih.status === 'PASSED' ? '#15803d' : '#b45309',
                                fontSize: '0.7rem',
                                fontWeight: 700,
                              }}
                            >
                              {ih.status === 'PASSED' ? 'LENGKAP' : 'KOMPONEN HILANG'}
                            </span>
                          </td>
                          <td className="font-mono font-bold text-red-600">
                            {ih.total_claim_amount && Number(ih.total_claim_amount) > 0
                              ? formatRupiah(ih.total_claim_amount)
                              : 'Rp 0'}
                          </td>
                          <td>
                            {ih.discrepancies?.map((d: any) => (
                              <div key={d.id} className="text-xs text-red-600">
                                &bull; {d.item_name}: kurang {formatNumber(d.missing_qty)}
                              </div>
                            )) || <span className="text-xs text-emerald-700">Lengkap</span>}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>

            {/* RIWAYAT PENYEWAAN SEKOLAH SEBELUMNYA */}
            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 6 }}>
                <History size={16} style={{ color: 'var(--color-primary, #3859ac)' }} />
                <strong style={{ fontSize: '0.875rem', color: 'var(--color-primary-dark, #263f7d)' }}>
                  Riwayat Pemakaian Sekolah Sebelumnya ({unitData.rental_history?.length || 0})
                </strong>
              </div>

              {!unitData.rental_history || unitData.rental_history.length === 0 ? (
                <div style={{ fontSize: '0.8rem', color: '#64748b', fontStyle: 'italic', background: '#ffffff', padding: 8, borderRadius: 6 }}>
                  Unit ini belum memiliki riwayat sewa sebelumnya.
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                  {unitData.rental_history.map((rh: any, idx: number) => (
                    <div
                      key={idx}
                      style={{
                        background: '#ffffff',
                        border: '1px solid #e2e8f0',
                        borderRadius: 6,
                        padding: '6px 10px',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        fontSize: '0.8rem',
                      }}
                    >
                      <div>
                        <strong>{rh.school_name}</strong>
                        <div style={{ fontSize: '0.725rem', color: '#64748b' }}>
                          Mulai: {formatDate(rh.start_date)} &bull; No: {rh.rental_no}
                        </div>
                      </div>
                      <span className="chip" style={{ fontSize: '0.7rem' }}>
                        {rh.status}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </Modal>
  );
}
