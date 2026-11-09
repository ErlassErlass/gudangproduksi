import { Warehouse } from 'lucide-react';
import { Button } from '@/components/Button';
import { Modal } from '@/components/Modal';
import { StatusBadge } from '@/components/StatusBadge';
import { EmptyState, LoadingState } from '@/components/States';
import { useProductStock, type ProductItem } from '@/features/master-data/api';
import { formatNumber, formatRupiah } from '@/lib/format';

interface StockDetailModalProps {
  product: ProductItem | null;
  isOpen: boolean;
  onClose: () => void;
}

export function StockDetailModal({ product, isOpen, onClose }: StockDetailModalProps) {
  const stock = useProductStock(product ? product.id : null);

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={`Rincian Stok — [${product?.code}] ${product?.name}`}
      description="Posisi stok aktual per lokasi gudang, status persediaan, nomor batch, dan serial."
      size="lg"
      footer={
        <Button variant="secondary" onClick={onClose}>
          Tutup
        </Button>
      }
    >
      <div className="grid grid--3 mb-4">
        <div className="p-3 rounded bg-[var(--color-surface-subtle)]">
          <p className="text-xs text-[var(--color-text-muted)]">Total On-Hand</p>
          <p className="text-lg font-bold font-mono">
            {formatNumber(product?.on_hand_qty ?? '0')} {product?.base_uom?.code}
          </p>
        </div>
        <div className="p-3 rounded bg-[var(--color-surface-subtle)]">
          <p className="text-xs text-[var(--color-text-muted)]">Stok Tersedia (Pickable)</p>
          <p className="text-lg font-bold font-mono text-[var(--color-success)]">
            {formatNumber(product?.available_qty ?? '0')} {product?.base_uom?.code}
          </p>
        </div>
        <div className="p-3 rounded bg-[var(--color-surface-subtle)]">
          <p className="text-xs text-[var(--color-text-muted)]">Safety Stock Minimal</p>
          <p className="text-lg font-bold font-mono">
            {formatNumber(product?.safety_stock_qty ?? '0')} {product?.base_uom?.code}
          </p>
        </div>
      </div>

      {stock.isLoading ? (
        <LoadingState />
      ) : !stock.data || stock.data.length === 0 ? (
        <EmptyState
          title="Belum ada saldo fisik di gudang"
          description="Barang ini belum pernah dilakukan penerimaan stok (opening balance atau GRN)."
        />
      ) : (
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr>
                <th>Gudang & Lokasi</th>
                <th>Status Stok</th>
                <th>Batch / Serial</th>
                <th>On-Hand</th>
                <th>Reservasi</th>
                <th>Tersedia</th>
                <th>Biaya Rata-rata</th>
              </tr>
            </thead>
            <tbody>
              {stock.data.map((row) => (
                <tr key={row.id}>
                  <td>
                    <div className="flex items-center gap-2">
                      <Warehouse size={14} className="text-[var(--color-text-muted)]" />
                      <div>
                        <div className="font-medium">{row.warehouse?.name ?? 'Gudang'}</div>
                        <div className="text-xs text-[var(--color-text-muted)] font-mono">
                          {row.location?.code ?? '-'}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <StatusBadge status={row.stock_status} />
                  </td>
                  <td className="font-mono text-xs">
                    {row.serial_number ? (
                      <span className="badge badge--neutral">SN: {row.serial_number}</span>
                    ) : row.batch_number ? (
                      <span className="badge badge--neutral">Batch: {row.batch_number}</span>
                    ) : (
                      <span className="text-[var(--color-text-muted)]">-</span>
                    )}
                  </td>
                  <td className="font-mono font-semibold">{formatNumber(row.on_hand_qty)}</td>
                  <td className="font-mono text-[var(--color-text-muted)]">
                    {formatNumber(row.reserved_qty)}
                  </td>
                  <td className="font-mono font-semibold text-[var(--color-success)]">
                    {formatNumber(row.available_qty)}
                  </td>
                  <td className="font-mono text-xs">
                    {row.avg_unit_cost ? formatRupiah(row.avg_unit_cost) : '-'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </Modal>
  );
}
