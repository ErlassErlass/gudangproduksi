import { Layers, Network, Warehouse } from 'lucide-react';
import { Button } from '@/components/Button';
import { Modal } from '@/components/Modal';
import { StatusBadge } from '@/components/StatusBadge';
import { EmptyState, LoadingState } from '@/components/States';
import {
  useProductBom,
  useProductStock,
  type ProductItem,
} from '@/features/master-data/api';
import { formatNumber, formatRupiah } from '@/lib/format';

interface ProductDetailModalProps {
  product: ProductItem | null;
  isOpen: boolean;
  onClose: () => void;
  isProgram?: boolean;
}

export function ProductDetailModal({
  product,
  isOpen,
  onClose,
  isProgram = false,
}: ProductDetailModalProps) {
  const stock = useProductStock(product ? product.id : null);
  const bomDetail = useProductBom(product && !isProgram ? product.id : null);

  const activeBom = bomDetail.data?.output_bom?.active_version;

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={`Detail ${isProgram ? 'Program' : 'Item'} — [${product?.code}] ${product?.name}`}
      description="Informasi lengkap identitas barang, status saldo stok gudang, dan struktur komponen."
      size="lg"
      footer={
        <Button variant="secondary" onClick={onClose}>
          Tutup
        </Button>
      }
    >
      <div className="space-y-4">
        {/* Identitas Utama */}
        <div className="grid grid--2 p-3 rounded bg-[var(--color-surface-subtle)] border border-[var(--color-border-subtle)] text-sm">
          <div>
            <span className="text-xs text-[var(--color-text-muted)]">Kode & Identitas:</span>
            <div className="font-mono font-bold text-base text-[var(--color-text-main)]">
              {product?.code}
            </div>
            {product?.legacy_code && (
              <div className="text-xs text-[var(--color-text-muted)] font-mono">
                Legacy: {product.legacy_code}
              </div>
            )}
          </div>
          <div>
            <span className="text-xs text-[var(--color-text-muted)]">Nama & Kategori:</span>
            <div className="font-semibold text-base text-[var(--color-text-main)]">
              {product?.name}
            </div>
            <div className="text-xs text-[var(--color-text-muted)] mt-0.5">
              {product?.category?.name ?? 'Tanpa Kategori'} &bull; Satuan: {product?.base_uom?.code}
            </div>
          </div>
        </div>

        {/* Ringkasan Parameter & Stok */}
        <div className="grid grid--3">
          <div className="p-3 rounded bg-[var(--color-surface-subtle)]">
            <span className="text-xs text-[var(--color-text-muted)]">Stok Tersedia (Pickable)</span>
            <div className="text-lg font-bold font-mono text-[var(--color-success)]">
              {formatNumber(product?.available_qty ?? '0')} {product?.base_uom?.code}
            </div>
            <div className="mt-1">
              <StatusBadge status={product?.stock_health ?? 'SAFE'} />
            </div>
          </div>

          <div className="p-3 rounded bg-[var(--color-surface-subtle)]">
            <span className="text-xs text-[var(--color-text-muted)]">Total Fisik On-Hand</span>
            <div className="text-lg font-bold font-mono">
              {formatNumber(product?.on_hand_qty ?? '0')} {product?.base_uom?.code}
            </div>
            <div className="text-xs text-[var(--color-text-muted)] mt-1">
              Safety Min: {formatNumber(product?.safety_stock_qty ?? '0')}
            </div>
          </div>

          <div className="p-3 rounded bg-[var(--color-surface-subtle)]">
            <span className="text-xs text-[var(--color-text-muted)]">Harga Referensi</span>
            <div className="text-lg font-bold font-mono">
              {product?.reference_purchase_price ? formatRupiah(product.reference_purchase_price) : '—'}
            </div>
            <div className="text-xs text-[var(--color-text-muted)] mt-1">
              Tracking: {product?.tracking_method}
            </div>
          </div>
        </div>

        {/* Jika Program: Tampilkan Komposisi Paket */}
        {isProgram && (
          <div className="card p-3 border border-[var(--color-border-subtle)]">
            <div className="flex items-center gap-2 mb-2 font-semibold text-sm">
              <Layers size={16} className="text-[var(--color-primary)]" />
              <span>Komposisi Paket Program (Finished Good & Layanan/Jasa):</span>
            </div>
            {!product?.components || product.components.length === 0 ? (
              <EmptyState
                title="Belum ada komponen di dalam program"
                description="Program ini belum memiliki rincian kit Finished Good atau sesi pelatihan/layanan."
              />
            ) : (
              <div className="table-wrap">
                <table className="table">
                  <thead>
                    <tr>
                      <th>Item Paket</th>
                      <th>Tipe</th>
                      <th>Kuantitas</th>
                      <th>Peran Item</th>
                    </tr>
                  </thead>
                  <tbody>
                    {product.components.map((c) => (
                      <tr key={c.id}>
                        <td>
                          <span className="font-mono font-medium">[{c.component_product?.code}]</span>{' '}
                          {c.component_product?.name}
                        </td>
                        <td>
                          <span className="chip chip--sm">
                            {c.component_product?.product_type === 'SERVICE' ? 'Layanan/Jasa' : 'Barang Jadi'}
                          </span>
                        </td>
                        <td className="font-mono font-bold">
                          {formatNumber(c.quantity)} {c.uom?.code}
                        </td>
                        <td>
                          <span className="badge badge--neutral">{c.component_role}</span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}

        {/* Jika Bukan Program: Tampilkan Rincian Stok Lokasi */}
        {!isProgram && (
          <div className="card p-3 border border-[var(--color-border-subtle)]">
            <div className="flex items-center gap-2 mb-2 font-semibold text-sm">
              <Warehouse size={16} className="text-[var(--color-primary)]" />
              <span>Rincian Saldo Stok per Lokasi Gudang:</span>
            </div>
            {stock.isLoading ? (
              <LoadingState />
            ) : !stock.data || stock.data.length === 0 ? (
              <p className="text-xs text-[var(--color-text-muted)] py-2">
                Belum ada saldo fisik di lokasi gudang.
              </p>
            ) : (
              <div className="table-wrap">
                <table className="table">
                  <thead>
                    <tr>
                      <th>Gudang & Lokasi</th>
                      <th>Status Stok</th>
                      <th>Batch / Serial</th>
                      <th>On-Hand</th>
                      <th>Tersedia</th>
                    </tr>
                  </thead>
                  <tbody>
                    {stock.data.map((row) => (
                      <tr key={row.id}>
                        <td>
                          <div className="font-medium text-xs">{row.warehouse?.name}</div>
                          <div className="font-mono text-xs text-[var(--color-text-muted)]">
                            {row.location?.code}
                          </div>
                        </td>
                        <td>
                          <StatusBadge status={row.stock_status} />
                        </td>
                        <td className="font-mono text-xs">
                          {row.serial_number ? `SN: ${row.serial_number}` : row.batch_number ? `Batch: ${row.batch_number}` : '—'}
                        </td>
                        <td className="font-mono font-semibold">{formatNumber(row.on_hand_qty)}</td>
                        <td className="font-mono font-bold text-[var(--color-success)]">
                          {formatNumber(row.available_qty)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}

        {/* Resep BOM Aktif (Jika ada dan bukan program) */}
        {!isProgram && activeBom && (
          <div className="card p-3 border border-[var(--color-border-subtle)]">
            <div className="flex items-center justify-between mb-2">
              <div className="flex items-center gap-2 font-semibold text-sm">
                <Network size={16} className="text-[var(--color-primary)]" />
                <span>Resep Perakitan BOM (Versi {activeBom.version_no} Aktif):</span>
              </div>
            </div>
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th>Komponen Input</th>
                    <th>Kebutuhan per Unit</th>
                    <th>Scrap Rate</th>
                  </tr>
                </thead>
                <tbody>
                  {activeBom.items.map((it) => (
                    <tr key={it.id}>
                      <td>
                        <span className="font-mono">[{it.component.code}]</span> {it.component.name}
                      </td>
                      <td className="font-mono font-semibold">
                        {formatNumber(it.quantity)} {it.uom.code}
                      </td>
                      <td className="font-mono text-xs text-[var(--color-text-muted)]">
                        {(Number(it.scrap_rate) * 100).toFixed(0)}%
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>
    </Modal>
  );
}
