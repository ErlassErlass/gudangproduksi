import { useEffect, useState, useMemo } from 'react';
import { AlertTriangle, Hammer, Layers } from 'lucide-react';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import {
  useAssembleStage,
  useLocationBalances,
  useLocations,
  useProductBom,
  type ProductItem,
} from '@/features/master-data/api';
import { formatNumber } from '@/lib/format';

interface AssembleStageModalProps {
  isOpen: boolean;
  onClose: () => void;
  products: ProductItem[];
  stageTitle: string;
  previousStageTitle: string;
  onSuccessAssemble?: () => void;
}

export function AssembleStageModal({
  isOpen,
  onClose,
  products,
  stageTitle,
  previousStageTitle,
  onSuccessAssemble,
}: AssembleStageModalProps) {
  const { notify } = useToast();
  const locations = useLocations();
  const assembleMutation = useAssembleStage();

  const [selectedProductId, setSelectedProductId] = useState<string>('');
  const [quantity, setQuantity] = useState<string>('5');
  const [sourceLocationId, setSourceLocationId] = useState<string>('');
  const [destLocationId, setDestLocationId] = useState<string>('');
  const [notes, setNotes] = useState<string>('');

  // Default product
  useEffect(() => {
    if (products.length > 0 && !selectedProductId) {
      setSelectedProductId(String(products[0].id));
    }
  }, [products, selectedProductId]);

  // Default locations
  useEffect(() => {
    if (locations.data && locations.data.length > 0) {
      if (!sourceLocationId) {
        setSourceLocationId(String(locations.data[0].id));
      }
      if (!destLocationId) {
        const dest = locations.data.length > 1 ? locations.data[1] : locations.data[0];
        setDestLocationId(String(dest.id));
      }
    }
  }, [locations.data, sourceLocationId, destLocationId]);

  const selectedProduct = products.find((p) => String(p.id) === selectedProductId) ?? null;
  const bomDetail = useProductBom(selectedProduct ? selectedProduct.id : null);
  const locationBalancesQuery = useLocationBalances(sourceLocationId ? Number(sourceLocationId) : undefined);

  const activeVersion = bomDetail.data?.output_bom?.active_version;
  const bomItems = activeVersion?.items ?? [];

  // Map balances in the selected source location by product_id
  const balancesMap = useMemo(() => {
    const map = new Map<number, number>();
    const list = locationBalancesQuery.data?.data ?? [];
    for (const b of list) {
      const current = map.get(b.product_id) ?? 0;
      map.set(b.product_id, current + Number(b.available_qty ?? b.on_hand_qty ?? 0));
    }
    return map;
  }, [locationBalancesQuery.data]);

  // Check shortages
  const { breakdown, hasInsufficientStock, shortageList } = useMemo(() => {
    const parsedQty = Math.max(0, Number(quantity) || 0);
    const shortages: { name: string; needed: number; available: number; missing: number; uom: string }[] = [];

    const itemsBreakdown = bomItems.map((item) => {
      const scrap = Number(item.scrap_rate || 0);
      const totalNeeded = Number(item.quantity) * (1 + scrap) * parsedQty;
      const available = balancesMap.get(item.component_product_id) ?? 0;
      const isShort = available < totalNeeded;

      if (isShort) {
        shortages.push({
          name: item.component.name,
          needed: totalNeeded,
          available,
          missing: totalNeeded - available,
          uom: item.uom.code,
        });
      }

      return {
        item,
        scrap,
        totalNeeded,
        available,
        isShort,
      };
    });

    return {
      breakdown: itemsBreakdown,
      hasInsufficientStock: shortages.length > 0,
      shortageList: shortages,
    };
  }, [bomItems, quantity, balancesMap]);

  const handleAssemble = async () => {
    if (!selectedProduct) {
      notify('Pilih produk yang akan dirakit terlebih dahulu.', 'error');
      return;
    }
    if (Number(quantity) <= 0) {
      notify('Kuantitas perakitan harus lebih besar dari 0.', 'error');
      return;
    }
    if (!activeVersion || bomItems.length === 0) {
      notify('Produk ini belum memiliki versi BOM aktif. Silakan buat resep BOM terlebih dahulu.', 'error');
      return;
    }
    if (hasInsufficientStock) {
      notify('Stok bahan tidak mencukupi untuk perakitan. Eksekusi dibatalkan.', 'error');
      return;
    }

    try {
      const res: any = await assembleMutation.mutateAsync({
        product_id: selectedProduct.id,
        quantity: quantity,
        source_location_id: sourceLocationId ? Number(sourceLocationId) : undefined as any,
        destination_location_id: destLocationId ? Number(destLocationId) : undefined as any,
        notes: notes || `Perakitan BOM tahap sebelumnya ke tahap ${selectedProduct.name}`,
      });
      notify(res?.message ?? 'Perakitan BOM berhasil! Stok di tahap ini telah bertambah.', 'success');
      onSuccessAssemble?.();
      onClose();
    } catch (err: any) {
      notify(err?.response?.data?.message ?? 'Gagal memproses perakitan stok.', 'error');
    }
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`Perakitan BOM (${previousStageTitle} → ${stageTitle})`}
      description={`Mengeksekusi konsumsi komponen dari tahap sebelumnya dan menambah saldo stok siap pakai pada tahap ${stageTitle} ini.`}
      size="lg"
      footer={
        <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end', width: '100%' }}>
          <Button variant="secondary" onClick={onClose}>
            Batal
          </Button>

          {/* Sesuai instruksi user: jika kuantitas yang ingin dirakit melebihi stok yang tersedia, HILANGKAN tombol eksekusi */}
          {!hasInsufficientStock && (
            <Button
              variant="primary"
              icon={<Hammer size={16} />}
              onClick={handleAssemble}
              loading={assembleMutation.isPending}
              disabled={!activeVersion || bomItems.length === 0}
            >
              Eksekusi Perakitan (Posting Stok)
            </Button>
          )}
        </div>
      }
    >
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        <div
          style={{
            padding: 12,
            borderRadius: 'var(--radius-sm, 8px)',
            background: 'var(--color-primary-light, #eef3ff)',
            border: '1px solid #c7d7f9',
            fontSize: '0.875rem',
            display: 'flex',
            alignItems: 'center',
            gap: 10,
          }}
        >
          <Layers size={18} color="var(--color-primary, #3859ac)" />
          <div>
            <span style={{ fontWeight: 600, color: '#263f7d' }}>
              Tahap Alur BOM: {previousStageTitle} &rarr; {stageTitle}
            </span>
            <p style={{ margin: '2px 0 0 0', fontSize: '0.8rem', color: '#475569' }}>
              Transaksi ini memotong stok bahan baku dan langsung membukukan penambahan stok barang di tahap ini secara atomik.
            </p>
          </div>
        </div>

        <div className="grid grid--2">
          <FormField label={`Produk Hasil Perakitan (${stageTitle})`} htmlFor="asm-prod" required>
            <select
              id="asm-prod"
              className="select"
              value={selectedProductId}
              onChange={(e) => setSelectedProductId(e.target.value)}
            >
              {products.map((p) => (
                <option key={p.id} value={p.id}>
                  [{p.code}] {p.name}
                </option>
              ))}
            </select>
          </FormField>

          <FormField label="Kuantitas yang Ingin Dirakit" htmlFor="asm-qty" required>
            <input
              id="asm-qty"
              type="number"
              step="1"
              min="1"
              className="input"
              style={{ fontWeight: 'bold', fontFamily: 'monospace' }}
              value={quantity}
              onChange={(e) => setQuantity(e.target.value)}
            />
          </FormField>
        </div>

        {/* Feedback langsung jika stok tidak mencukupi */}
        {hasInsufficientStock && (
          <div
            style={{
              padding: '12px 16px',
              borderRadius: 8,
              backgroundColor: '#fef2f2',
              border: '1px solid #f87171',
              color: '#991b1b',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontWeight: 700, fontSize: '0.925rem' }}>
              <AlertTriangle size={18} color="#dc2626" />
              Stok Bahan Baku Tidak Mencukupi!
            </div>
            <p style={{ margin: '6px 0 8px 0', fontSize: '0.85rem', lineHeight: 1.5 }}>
              Kuantitas yang ingin dirakit ({quantity} unit) melebihi stok yang tersedia di lokasi pengambilan bahan. Tombol eksekusi telah disembunyikan sampai kuantitas disesuaikan atau stok diisi.
            </p>
            <ul style={{ margin: 0, paddingLeft: 20, fontSize: '0.825rem' }}>
              {shortageList.map((s, idx) => (
                <li key={idx}>
                  <strong>{s.name}</strong>: Dibutuhkan {formatNumber(s.needed)} {s.uom}, tersedia hanya {formatNumber(s.available)} {s.uom} (Kekurangan: {formatNumber(s.missing)} {s.uom}).
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Required Components Breakdown based on Active BOM */}
        <div className="card" style={{ padding: 14 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 }}>
            <span style={{ fontWeight: 600, fontSize: '0.8rem', textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--color-text-primary, #34466f)' }}>
              Kebutuhan Bahan Baku dari {previousStageTitle}:
            </span>
            <span className="table__muted" style={{ fontSize: '0.8rem' }}>
              {activeVersion ? `BOM Versi ${activeVersion.version_no} (Aktif)` : 'Belum Ada Resep BOM'}
            </span>
          </div>

          {bomDetail.isLoading ? (
            <LoadingState label="Menghitung kebutuhan bahan BOM..." />
          ) : !activeVersion || bomItems.length === 0 ? (
            <EmptyState
              title="Resep BOM belum tersedia"
              description={`Produk [${selectedProduct?.code}] belum memiliki versi BOM aktif. Buka modul BOM untuk mengatur daftar bahan input.`}
            />
          ) : (
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th>Komponen Masukan</th>
                    <th style={{ width: '110px' }}>Rasio/Unit</th>
                    <th style={{ width: '80px' }}>Scrap</th>
                    <th style={{ width: '130px' }}>Total Kebutuhan</th>
                    <th style={{ width: '130px' }}>Stok di Lokasi</th>
                    <th style={{ width: '100px', textAlign: 'center' }}>Kondisi</th>
                  </tr>
                </thead>
                <tbody>
                  {breakdown.map(({ item, scrap, totalNeeded, available, isShort }) => (
                    <tr key={item.id} style={{ backgroundColor: isShort ? '#fff5f5' : undefined }}>
                      <td>
                        <span style={{ fontWeight: 600 }}>[{item.component.code}]</span> {item.component.name}
                      </td>
                      <td style={{ fontFamily: 'monospace', fontSize: '0.8rem' }}>
                        {formatNumber(item.quantity)} {item.uom.code}
                      </td>
                      <td style={{ fontFamily: 'monospace', fontSize: '0.8rem', color: '#64748b' }}>
                        {(scrap * 100).toFixed(0)}%
                      </td>
                      <td>
                        <span style={{ fontFamily: 'monospace', fontWeight: 700, color: isShort ? '#dc2626' : 'var(--color-primary, #3859ac)' }}>
                          {formatNumber(totalNeeded)} {item.uom.code}
                        </span>
                      </td>
                      <td>
                        <span style={{ fontFamily: 'monospace', fontWeight: 600, color: isShort ? '#dc2626' : 'var(--color-success, #2f9d62)' }}>
                          {formatNumber(available)} {item.uom.code}
                        </span>
                      </td>
                      <td style={{ textAlign: 'center' }}>
                        {isShort ? (
                          <span className="chip" style={{ background: '#fee2e2', color: '#b91c1c', fontWeight: 600 }}>
                            Kurang
                          </span>
                        ) : (
                          <span className="chip" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 600 }}>
                            Cukup
                          </span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>

        <FormField label="Catatan Perakitan (Opsional)" htmlFor="asm-notes">
          <input
            id="asm-notes"
            type="text"
            className="input"
            placeholder="Mis. Perakitan batch harian ruang produksi"
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
          />
        </FormField>
      </div>
    </Modal>
  );
}
