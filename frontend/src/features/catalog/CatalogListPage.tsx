import { useState } from 'react';
import {
  AlertTriangle,
  Boxes,
  Edit2,
  Eye,
  Filter,
  Hammer,
  Package,
  Plus,
  Printer,
  RefreshCw,
  ScanBarcode,
  Search,
  Trash2,
} from 'lucide-react';
import type { ListParams } from '@/api/types';
import { Button } from '@/components/Button';
import { ConfirmationDialog } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { StatCard } from '@/components/StatCard';
import { StatusBadge } from '@/components/StatusBadge';
import { EmptyState, ErrorState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import {
  useDeleteProduct,
  useProducts,
  type ProductItem,
} from '@/features/master-data/api';
import { formatNumber } from '@/lib/format';
import { AssembleStageModal } from './AssembleStageModal';
import { BarcodeScanModal } from './BarcodeScanModal';
import { ProductDetailModal } from './ProductDetailModal';
import { ProductFormModal } from './ProductFormModal';
import { StockMovementPrintModal } from './StockMovementPrintModal';

export interface CatalogListPageProps {
  eyebrow: string;
  title: string;
  description: string;
  itemGroup: 'component' | 'module' | 'box' | 'finished_good' | 'program';
  categoryCode?: string;
  defaultCategoryCode?: string;
  defaultProductType?: 'PRODUCT' | 'SERVICE' | 'PROGRAM';
  defaultPhysicalType?: 'PHYSICAL' | 'DIGITAL' | 'NON_INVENTORY';
  defaultTrackingMethod?: 'QUANTITY' | 'SERIALIZED' | 'BATCH' | 'NONE';
  nextStageTitle: string;
  previousStageTitle?: string;
  isProgram?: boolean;
}

export function CatalogListPage({
  eyebrow,
  title,
  description,
  itemGroup,
  categoryCode,
  defaultCategoryCode,
  defaultProductType = 'PRODUCT',
  defaultPhysicalType = 'PHYSICAL',
  defaultTrackingMethod = 'QUANTITY',
  nextStageTitle: _nextStageTitle,
  previousStageTitle = 'Tahap Sebelumnya',
  isProgram = false,
}: CatalogListPageProps) {
  const { can } = useAuth();
  const canManage = can('master_data.manage');
  const { notify } = useToast();

  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [stockHealthFilter, setStockHealthFilter] = useState<string>('ALL');

  // Modals state
  const [selectedProductForDetail, setSelectedProductForDetail] = useState<ProductItem | null>(null);
  const [selectedProductForMovement, setSelectedProductForMovement] = useState<ProductItem | null>(null);
  const [editingProduct, setEditingProduct] = useState<ProductItem | null>(null);
  const [deletingProduct, setDeletingProduct] = useState<ProductItem | null>(null);
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isAssembleOpen, setIsAssembleOpen] = useState(false);
  const [isScanOpen, setIsScanOpen] = useState(false);

  const deleteMutation = useDeleteProduct();

  const queryParams: ListParams = {
    page,
    per_page: 20,
    search: search.trim() || undefined,
    item_group: itemGroup,
  };
  if (categoryCode) {
    queryParams.category_code = categoryCode;
  }

  const { data, isLoading, error, refetch, isFetching } = useProducts(queryParams);

  const rawItems = data?.data ?? [];
  const items = rawItems.filter((it) => {
    if (stockHealthFilter === 'ALL') return true;
    return it.stock_health === stockHealthFilter;
  });

  const totalItems = data?.meta.total ?? rawItems.length;
  const totalAvailable = rawItems.reduce((acc, it) => acc + Number(it.available_qty || 0), 0);
  const lowStockCount = rawItems.filter((it) => it.stock_health === 'LOW_STOCK' || it.stock_health === 'OUT_OF_STOCK').length;

  const handleDeleteConfirm = async () => {
    if (!deletingProduct) return;
    try {
      await deleteMutation.mutateAsync(deletingProduct.id);
      notify(`Item [${deletingProduct.code}] berhasil dihapus.`, 'success');
      setDeletingProduct(null);
      refetch();
    } catch (err: any) {
      notify(err?.response?.data?.message ?? 'Gagal menghapus item.', 'error');
    }
  };

  return (
    <>
      <PageHeader
        eyebrow={eyebrow}
        title={title}
        description={description}
        actions={
          <div className="catalog-actions">
            {canManage && (
              <Button
                variant="primary"
                className="catalog-actions__btn-primary"
                icon={<Plus size={16} />}
                onClick={() => setIsCreateOpen(true)}
              >
                Tambah {isProgram ? 'Program Baru' : 'Item Baru'}
              </Button>
            )}

            {/* Fitur Produksi Tahap Sesuai Halaman (Modul / Box / Finished Good) */}
            {!isProgram && itemGroup !== 'component' && Boolean(previousStageTitle) && (
              <Button
                variant="secondary"
                icon={<Hammer size={16} />}
                onClick={() => setIsAssembleOpen(true)}
                title={`Perakitan dari ${previousStageTitle} ke tahap ini (akan menambah stok tahap ini)`}
              >
                {itemGroup === 'module'
                  ? 'Produksi Modul'
                  : itemGroup === 'box'
                    ? 'Produksi Box'
                    : itemGroup === 'finished_good'
                      ? 'Produksi Finished Good'
                      : `Produksi ${title}`}
              </Button>
            )}

            {/* Tombol Scan Barcode (Khusus Finished Good) */}
            {itemGroup === 'finished_good' && (
              <Button
                variant="secondary"
                icon={<ScanBarcode size={16} />}
                onClick={() => setIsScanOpen(true)}
                title="Scan barcode untuk langsung mendeteksi produk Finished Good"
              >
                Scan Barcode
              </Button>
            )}

            <Button
              variant="secondary"
              icon={<RefreshCw size={16} />}
              onClick={() => refetch()}
              loading={isFetching}
            >
              Muat Ulang
            </Button>
          </div>
        }
      />

      {/* Stats Summary: Row pada mode desktop dan Col pada mode mobile */}
      <div className="summary-cards-row">
        <StatCard
          label="Total Jenis Item"
          value={totalItems}
          hint={`Terdaftar di kelompok ${title}`}
          icon={<Package size={18} />}
        />
        <StatCard
          label="Stok Tersedia"
          value={formatNumber(totalAvailable)}
          hint="Stok siap digunakan / dialokasikan"
          icon={<Boxes size={18} />}
          accent
        />
        <StatCard
          label="Peringatan Stok Kurang"
          value={lowStockCount}
          hint="Di bawah batas safety stock"
          icon={<AlertTriangle size={18} />}
        />
      </div>

      {/* Form Search & Filter Konsisten dengan Design System */}
      <div className="card mb-6">
        <div
          style={{
            display: 'flex',
            flexWrap: 'wrap',
            alignItems: 'center',
            justifyContent: 'space-between',
            gap: 12,
            padding: '14px 20px',
            backgroundColor: 'var(--color-surface, #ffffff)',
            borderRadius: 'var(--radius-lg, 16px)',
          }}
        >
          {/* Kolom Pencarian */}
          <div style={{ position: 'relative', flex: '1 1 280px', maxWidth: '420px' }}>
            <Search
              size={16}
              style={{
                position: 'absolute',
                left: 12,
                top: '50%',
                transform: 'translateY(-50%)',
                color: 'var(--color-text-secondary, #8191b1)',
                pointerEvents: 'none',
              }}
            />
            <input
              type="text"
              className="input"
              placeholder={`Cari kode atau nama ${title.toLowerCase()}...`}
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
                setPage(1);
              }}
              style={{
                paddingLeft: 38,
                paddingRight: search ? 32 : 12,
                width: '100%',
                height: 38,
                fontSize: '0.85rem',
              }}
            />
            {search && (
              <button
                type="button"
                onClick={() => {
                  setSearch('');
                  setPage(1);
                }}
                style={{
                  position: 'absolute',
                  right: 10,
                  top: '50%',
                  transform: 'translateY(-50%)',
                  background: 'none',
                  border: 'none',
                  cursor: 'pointer',
                  color: '#94a3b8',
                  fontSize: '1rem',
                  lineHeight: 1,
                  padding: 2,
                }}
                title="Hapus pencarian"
              >
                &times;
              </button>
            )}
          </div>

          {/* Kolom Filter Kondisi Stok */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 10, flexWrap: 'wrap' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <Filter size={15} style={{ color: 'var(--color-primary, #3859ac)' }} />
              <label
                htmlFor="stock-health-select"
                style={{
                  fontSize: '0.825rem',
                  fontWeight: 600,
                  color: 'var(--color-text-secondary, #64748b)',
                  whiteSpace: 'nowrap',
                }}
              >
                Kondisi Stok:
              </label>
              <select
                id="stock-health-select"
                className="select"
                value={stockHealthFilter}
                onChange={(e) => setStockHealthFilter(e.target.value)}
                style={{ height: 38, minWidth: 160, fontSize: '0.825rem', fontWeight: 500 }}
              >
                <option value="ALL">Semua Kondisi</option>
                <option value="SAFE">Aman (Stok Cukup)</option>
                <option value="LOW_STOCK">Stok Rendah (Di Bawah Safety)</option>
                <option value="OUT_OF_STOCK">Habis (Kosong)</option>
                <option value="NON_STOCK">Non-Stok / Jasa</option>
              </select>
            </div>

            {/* Tombol Reset Filter jika aktif */}
            {(search || stockHealthFilter !== 'ALL') && (
              <Button
                size="sm"
                variant="ghost"
                onClick={() => {
                  setSearch('');
                  setStockHealthFilter('ALL');
                  setPage(1);
                }}
                style={{ fontSize: '0.78rem', height: 38 }}
              >
                Reset Filter
              </Button>
            )}
          </div>
        </div>
      </div>

      {/* Main Table: Menampilkan field: kode, nama, stok, status dan tombol aksi (edit, detail & delete) */}
      {isLoading ? (
        <LoadingState />
      ) : error ? (
        <ErrorState error={error} onRetry={() => refetch()} />
      ) : items.length === 0 ? (
        <section className="card">
          <EmptyState
            title={`Belum ada data ${title.toLowerCase()}`}
            description="Tidak ditemukan item yang cocok dengan pencarian atau filter yang dipilih."
            action={
              canManage ? (
                <Button variant="primary" icon={<Plus size={16} />} onClick={() => setIsCreateOpen(true)}>
                  Tambah {isProgram ? 'Program Pertama' : 'Item Pertama'}
                </Button>
              ) : undefined
            }
          />
        </section>
      ) : (
        <section className="card">
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th style={{ width: '160px' }}>Kode</th>
                  <th>Nama</th>
                  {itemGroup === 'finished_good' && <th style={{ width: '150px' }}>Kategori / Tipe</th>}
                  <th style={{ width: '140px' }}>Stok</th>
                  <th style={{ width: '110px' }}>Status</th>
                  <th style={{ width: '160px', textAlign: 'center' }}>Aksi</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.id}>
                    <td>
                      <div>
                        <span className="font-mono font-bold text-[var(--color-text-main)]">
                          {item.code}
                        </span>
                        {item.legacy_code && (
                          <div className="text-xs text-[var(--color-text-muted)] font-mono">
                            Legacy: {item.legacy_code}
                          </div>
                        )}
                      </div>
                    </td>
                    <td>
                      <span className="font-medium text-[var(--color-text-main)]">{item.name}</span>
                    </td>
                    {itemGroup === 'finished_good' && (
                      <td>
                        {item.product_type === 'SERVICE' ? (
                          <span className="chip" style={{ background: '#f3e8ff', color: '#7e22ce' }}>Jasa / Layanan</span>
                        ) : (
                          <span className="chip" style={{ background: '#dbeafe', color: '#1d4ed8' }}>Finished Good</span>
                        )}
                      </td>
                    )}
                    <td>
                      {item.stock_health === 'NON_STOCK' ? (
                        <span className="text-xs text-[var(--color-text-muted)] font-medium">Non-Stok Fisik</span>
                      ) : (
                        <span className="font-mono font-bold text-[var(--color-success)]" style={{ fontSize: '0.925rem' }}>
                          {formatNumber(item.available_qty ?? '0')} {item.base_uom?.code}
                        </span>
                      )}
                    </td>
                    <td>
                      <StatusBadge status={item.status} />
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      <div style={{ display: 'inline-flex', gap: 3, alignItems: 'center', justifyContent: 'center' }}>
                        {/* Tombol Detail */}
                        <Button
                          variant="ghost"
                          size="sm"
                          iconOnly
                          icon={<Eye size={15} />}
                          onClick={() => setSelectedProductForDetail(item)}
                          aria-label={`Detail ${item.name}`}
                        />

                        {/* Tombol Cetak Riwayat Keluar Masuk */}
                        <Button
                          variant="ghost"
                          size="sm"
                          iconOnly
                          icon={<Printer size={15} />}
                          onClick={() => setSelectedProductForMovement(item)}
                          aria-label={`Cetak riwayat keluar masuk ${item.name}`}
                        />

                        {/* Tombol Edit */}
                        {canManage && (
                          <Button
                            variant="ghost"
                            size="sm"
                            iconOnly
                            icon={<Edit2 size={15} />}
                            onClick={() => setEditingProduct(item)}
                            aria-label={`Ubah ${item.name}`}
                          />
                        )}

                        {/* Tombol Delete */}
                        {canManage && (
                          <Button
                            variant="ghost"
                            size="sm"
                            iconOnly
                            icon={<Trash2 size={15} color="var(--color-danger, #d96a36)" />}
                            onClick={() => setDeletingProduct(item)}
                            aria-label={`Hapus ${item.name}`}
                          />
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          {data && data.meta.last_page > 1 && (
            <div className="card__footer flex justify-between items-center p-3 border-t border-[var(--color-border-subtle)] text-sm">
              <span className="text-[var(--color-text-muted)]">
                Menampilkan halaman {data.meta.current_page} dari {data.meta.last_page} ({data.meta.total} item)
              </span>
              <div className="flex gap-2">
                <Button
                  variant="secondary"
                  size="sm"
                  disabled={data.meta.current_page <= 1}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                >
                  Sebelumnya
                </Button>
                <Button
                  variant="secondary"
                  size="sm"
                  disabled={data.meta.current_page >= data.meta.last_page}
                  onClick={() => setPage((p) => p + 1)}
                >
                  Berikutnya
                </Button>
              </div>
            </div>
          )}
        </section>
      )}

      {/* Modal Detail Item / Program */}
      <ProductDetailModal
        product={selectedProductForDetail}
        isOpen={Boolean(selectedProductForDetail)}
        onClose={() => setSelectedProductForDetail(null)}
        isProgram={isProgram}
      />

      {/* Modal Edit / Tambah Item */}
      <ProductFormModal
        product={editingProduct}
        isOpen={Boolean(editingProduct) || isCreateOpen}
        onClose={() => {
          setEditingProduct(null);
          setIsCreateOpen(false);
        }}
        defaultCategoryCode={defaultCategoryCode}
        defaultProductType={defaultProductType}
        defaultPhysicalType={defaultPhysicalType}
        defaultTrackingMethod={defaultTrackingMethod}
        categoryTitle={title}
      />

      {/* Modal Perakitan BOM dari Tahap Sebelumnya (Mempengaruhi stok tahap ini) */}
      {!isProgram && (
        <AssembleStageModal
          isOpen={isAssembleOpen}
          onClose={() => setIsAssembleOpen(false)}
          products={rawItems}
          stageTitle={title}
          previousStageTitle={previousStageTitle}
          onSuccessAssemble={() => refetch()}
        />
      )}

      {/* Dialog Konfirmasi Hapus */}
      <ConfirmationDialog
        open={Boolean(deletingProduct)}
        title={`Hapus ${isProgram ? 'Program' : 'Item'}?`}
        message={`Apakah Anda yakin ingin menghapus [${deletingProduct?.code}] ${deletingProduct?.name}? Data yang belum memiliki transaksi mutasi stok dapat dihapus secara permanen.`}
        confirmLabel="Ya, Hapus"
        variant="danger"
        loading={deleteMutation.isPending}
        onConfirm={handleDeleteConfirm}
        onCancel={() => setDeletingProduct(null)}
      />

      {/* Modal Cetak Riwayat Keluar Masuk Barang */}
      {selectedProductForMovement && (
        <StockMovementPrintModal
          product={selectedProductForMovement}
          isOpen={Boolean(selectedProductForMovement)}
          onClose={() => setSelectedProductForMovement(null)}
        />
      )}

      {/* Modal Scan Barcode (Khusus Finished Good) */}
      <BarcodeScanModal
        isOpen={isScanOpen}
        onClose={() => setIsScanOpen(false)}
        onOpenStockMovement={(prod) => setSelectedProductForMovement(prod)}
      />
    </>
  );
}
