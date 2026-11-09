import { useEffect, useState } from 'react';
import { ArrowRight, Network, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/Button';
import { Modal } from '@/components/Modal';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useProducts, useProductBom, useQuickSaveBom, useUoms, type ProductItem } from '@/features/master-data/api';
import { formatNumber } from '@/lib/format';

interface BomStageModalProps {
  product: ProductItem | null;
  isOpen: boolean;
  onClose: () => void;
  nextStageTitle?: string;
}

interface BomLineState {
  component_product_id: string;
  quantity: string;
  uom_id: string;
  scrap_rate: string;
  notes: string;
}

export function BomStageModal({ product, isOpen, onClose, nextStageTitle = 'Tahap Selanjutnya' }: BomStageModalProps) {
  const { notify } = useToast();
  const bomDetail = useProductBom(product ? product.id : null);
  const uoms = useUoms();
  const allProducts = useProducts({ per_page: 100 });
  const quickSave = useQuickSaveBom();

  const [activeTab, setActiveTab] = useState<'recipe' | 'usage'>('recipe');
  const [items, setItems] = useState<BomLineState[]>([]);

  // When product or bomDetail changes, initialize items
  useEffect(() => {
    if (bomDetail.data?.output_bom?.active_version?.items) {
      setItems(
        bomDetail.data.output_bom.active_version.items.map((it) => ({
          component_product_id: String(it.component_product_id),
          quantity: it.quantity,
          uom_id: String(it.uom.id),
          scrap_rate: it.scrap_rate,
          notes: it.notes ?? '',
        }))
      );
    } else {
      setItems([]);
    }
  }, [bomDetail.data]);

  const handleAddItem = () => {
    const defaultUom = uoms.data?.[0]?.id ? String(uoms.data[0].id) : '1';
    setItems((prev) => [
      ...prev,
      {
        component_product_id: '',
        quantity: '1.0000',
        uom_id: defaultUom,
        scrap_rate: '0',
        notes: '',
      },
    ]);
  };

  const handleRemoveItem = (index: number) => {
    setItems((prev) => prev.filter((_, idx) => idx !== index));
  };

  const handleItemChange = (index: number, field: keyof BomLineState, value: string) => {
    setItems((prev) => {
      const copy = [...prev];
      copy[index] = { ...copy[index], [field]: value };
      if (field === 'component_product_id') {
        const found = allProducts.data?.data.find((p) => String(p.id) === value);
        if (found) {
          copy[index].uom_id = String(found.base_uom_id);
        }
      }
      return copy;
    });
  };

  const handleSave = async () => {
    if (!product) return;
    if (items.length === 0) {
      notify('Tambahkan minimal 1 komponen ke dalam resep BOM.', 'error');
      return;
    }
    for (const it of items) {
      if (!it.component_product_id || Number(it.quantity) <= 0) {
        notify('Pastikan semua baris komponen memiliki produk dan kuantitas valid.', 'error');
        return;
      }
    }

    try {
      await quickSave.mutateAsync({
        product_id: product.id,
        items: items.map((it) => ({
          component_product_id: Number(it.component_product_id),
          quantity: it.quantity,
          uom_id: Number(it.uom_id),
          scrap_rate: Number(it.scrap_rate) || 0,
          notes: it.notes || undefined,
        })),
      });
      notify('Struktur BOM berhasil diperbarui dan versi aktif disimpan!', 'success');
      bomDetail.refetch();
      onClose();
    } catch (err: any) {
      notify(err?.response?.data?.message ?? 'Gagal menyimpan struktur BOM.', 'error');
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={`Struktur BOM — ${product?.code} (${product?.name})`}
      description="Kelola dan ubah struktur BOM sewaktu-waktu untuk proses ke tahap selanjutnya."
      size="xl"
      footer={
        <div className="flex gap-2 justify-end w-full">
          <Button variant="secondary" onClick={onClose}>
            Tutup
          </Button>
          {activeTab === 'recipe' && (
            <Button
              variant="primary"
              icon={<Network size={16} />}
              onClick={handleSave}
              loading={quickSave.isPending}
            >
              Simpan Perubahan BOM
            </Button>
          )}
        </div>
      }
    >
      <div className="flex gap-2 mb-4 border-b border-[var(--color-border-subtle)] pb-2">
        <button
          type="button"
          className={`tab-pill ${activeTab === 'recipe' ? 'tab-pill--active' : ''}`}
          onClick={() => setActiveTab('recipe')}
        >
          Resep Perakitan ({bomDetail.data?.output_bom ? 'Ada BOM' : 'Belum Ada BOM'})
        </button>
        <button
          type="button"
          className={`tab-pill ${activeTab === 'usage' ? 'tab-pill--active' : ''}`}
          onClick={() => setActiveTab('usage')}
        >
          Penggunaan di {nextStageTitle} ({bomDetail.data?.used_in_next_stages.length ?? 0})
        </button>
      </div>

      {bomDetail.isLoading ? (
        <LoadingState />
      ) : activeTab === 'recipe' ? (
        <div>
          <div className="p-3 mb-4 rounded bg-[var(--color-surface-subtle)] flex items-center justify-between text-sm">
            <div>
              <p className="font-semibold text-[var(--color-text-main)]">
                Output: [{product?.code}] {product?.name}
              </p>
              <p className="text-[var(--color-text-muted)] text-xs">
                Versi Aktif:{' '}
                {bomDetail.data?.output_bom?.active_version
                  ? `v${bomDetail.data.output_bom.active_version.version_no} (Aktif)`
                  : 'Belum ditentukan (akan dibuat versi 1)'}
              </p>
            </div>
            <Button variant="secondary" icon={<Plus size={14} />} onClick={handleAddItem}>
              Tambah Komponen
            </Button>
          </div>

          {items.length === 0 ? (
            <EmptyState
              title="Belum ada komponen penyusun"
              description="Klik tombol 'Tambah Komponen' untuk merancang resep bahan baku perakitan produk ini."
            />
          ) : (
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th>Komponen / Input</th>
                    <th style={{ width: '130px' }}>Kuantitas</th>
                    <th style={{ width: '110px' }}>Satuan</th>
                    <th style={{ width: '100px' }}>Scrap Rate</th>
                    <th>Catatan</th>
                    <th style={{ width: '60px' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((row, idx) => (
                    <tr key={idx}>
                      <td>
                        <select
                          className="select select--sm w-full"
                          value={row.component_product_id}
                          onChange={(e) => handleItemChange(idx, 'component_product_id', e.target.value)}
                        >
                          <option value="">Pilih komponen...</option>
                          {allProducts.data?.data
                            .filter((p) => p.id !== product?.id)
                            .map((p) => (
                              <option key={p.id} value={p.id}>
                                [{p.code}] {p.name}
                              </option>
                            ))}
                        </select>
                      </td>
                      <td>
                        <input
                          type="number"
                          step="0.0001"
                          min="0.0001"
                          className="input input--sm w-full font-mono"
                          value={row.quantity}
                          onChange={(e) => handleItemChange(idx, 'quantity', e.target.value)}
                        />
                      </td>
                      <td>
                        <select
                          className="select select--sm w-full"
                          value={row.uom_id}
                          onChange={(e) => handleItemChange(idx, 'uom_id', e.target.value)}
                        >
                          {uoms.data?.map((u) => (
                            <option key={u.id} value={u.id}>
                              {u.code}
                            </option>
                          ))}
                        </select>
                      </td>
                      <td>
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          max="1"
                          className="input input--sm w-full font-mono"
                          value={row.scrap_rate}
                          onChange={(e) => handleItemChange(idx, 'scrap_rate', e.target.value)}
                          placeholder="0"
                        />
                      </td>
                      <td>
                        <input
                          type="text"
                          className="input input--sm w-full"
                          value={row.notes}
                          onChange={(e) => handleItemChange(idx, 'notes', e.target.value)}
                          placeholder="Opsional"
                        />
                      </td>
                      <td>
                        <Button
                          variant="ghost"
                          icon={<Trash2 size={14} className="text-red-500" />}
                          onClick={() => handleRemoveItem(idx)}
                        />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      ) : (
        <div>
          <div className="p-3 mb-4 rounded bg-[var(--color-surface-subtle)] text-sm">
            <p className="font-semibold text-[var(--color-text-main)]">
              Daftar Perakitan yang Membutuhkan Item Ini:
            </p>
            <p className="text-[var(--color-text-muted)] text-xs">
              Melihat di produk mana saja [{product?.code}] dikonsumsi sebagai komponen/subassembly tahap berikutnya.
            </p>
          </div>

          {!bomDetail.data?.used_in_next_stages || bomDetail.data.used_in_next_stages.length === 0 ? (
            <EmptyState
              title="Belum digunakan di tahap lanjutan"
              description="Item ini belum terdaftar di dalam BOM aktif produk rakitan lainnya."
            />
          ) : (
            <div className="table-wrap">
              <table className="table">
                <thead>
                  <tr>
                    <th>Target Produk Lanjutan</th>
                    <th>Tahap / Template</th>
                    <th>Nomor BOM Induk</th>
                    <th>Kebutuhan per Unit</th>
                    <th>Scrap Rate</th>
                  </tr>
                </thead>
                <tbody>
                  {bomDetail.data.used_in_next_stages.map((stage) => (
                    <tr key={stage.id}>
                      <td>
                        <div className="flex items-center gap-2">
                          <ArrowRight size={14} className="text-[var(--color-primary)]" />
                          <span className="font-medium">
                            [{stage.target_product.code}] {stage.target_product.name}
                          </span>
                        </div>
                      </td>
                      <td>{stage.stage_name}</td>
                      <td className="font-mono text-xs">{stage.parent_bom_code}</td>
                      <td className="font-mono">
                        {formatNumber(stage.quantity)} {stage.uom.code}
                      </td>
                      <td className="font-mono">
                        {(Number(stage.scrap_rate) * 100).toFixed(0)}%
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </Modal>
  );
}
