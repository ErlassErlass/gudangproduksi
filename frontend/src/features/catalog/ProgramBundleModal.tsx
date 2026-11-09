import { useEffect, useState } from 'react';
import { Layers, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/Button';
import { Modal } from '@/components/Modal';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useProducts, useSyncComponents, useUoms, type ProductItem } from '@/features/master-data/api';

interface ProgramBundleModalProps {
  program: ProductItem | null;
  isOpen: boolean;
  onClose: () => void;
}

interface ComponentLineState {
  component_product_id: string;
  quantity: string;
  uom_id: string;
  component_role: string;
}

export function ProgramBundleModal({ program, isOpen, onClose }: ProgramBundleModalProps) {
  const { notify } = useToast();
  const uoms = useUoms();
  const candidateProducts = useProducts({ per_page: 100 });
  const syncMutation = useSyncComponents();

  const [items, setItems] = useState<ComponentLineState[]>([]);

  useEffect(() => {
    if (program?.components && program.components.length > 0) {
      setItems(
        program.components.map((c) => ({
          component_product_id: String(c.component_product_id),
          quantity: c.quantity,
          uom_id: String(c.uom_id),
          component_role: c.component_role || 'MEMBER',
        }))
      );
    } else {
      setItems([]);
    }
  }, [program]);

  const handleAdd = () => {
    const defaultUom = uoms.data?.[0]?.id ? String(uoms.data[0].id) : '1';
    setItems((prev) => [
      ...prev,
      {
        component_product_id: '',
        quantity: '1.0000',
        uom_id: defaultUom,
        component_role: 'FINISHED_GOOD',
      },
    ]);
  };

  const handleRemove = (index: number) => {
    setItems((prev) => prev.filter((_, idx) => idx !== index));
  };

  const handleChange = (index: number, field: keyof ComponentLineState, value: string) => {
    setItems((prev) => {
      const copy = [...prev];
      copy[index] = { ...copy[index], [field]: value };
      if (field === 'component_product_id') {
        const found = candidateProducts.data?.data.find((p) => String(p.id) === value);
        if (found) {
          copy[index].uom_id = String(found.base_uom_id);
          copy[index].component_role =
            found.product_type === 'SERVICE' ? 'SERVICE' : 'FINISHED_GOOD';
        }
      }
      return copy;
    });
  };

  const handleSave = async () => {
    if (!program) return;
    if (items.length === 0) {
      notify('Tambahkan minimal 1 item (Finished Good atau Layanan/Jasa) ke dalam program.', 'error');
      return;
    }
    for (const it of items) {
      if (!it.component_product_id || Number(it.quantity) <= 0) {
        notify('Pastikan semua baris item produk dan kuantitas valid.', 'error');
        return;
      }
    }

    try {
      await syncMutation.mutateAsync({
        productId: program.id,
        components: items.map((it) => ({
          component_product_id: Number(it.component_product_id),
          quantity: it.quantity,
          uom_id: Number(it.uom_id),
          component_role: it.component_role,
        })),
      });
      notify('Komposisi paket program berhasil disimpan!', 'success');
      onClose();
    } catch (err: any) {
      notify(err?.response?.data?.message ?? 'Gagal menyimpan paket program.', 'error');
    }
  };

  // Candidates for program bundle: finished goods (PRODUCT) and services (SERVICE)
  const availableItems = (candidateProducts.data?.data ?? []).filter(
    (p) => p.id !== program?.id && (p.product_type === 'SERVICE' || p.category?.code === 'FG' || p.is_sellable)
  );

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={`Kelola Komposisi Program — ${program?.name}`}
      description="Program merupakan gabungan dari produk service dan finished good atau salah satunya."
      size="lg"
      footer={
        <div className="flex gap-2 justify-end w-full">
          <Button variant="secondary" onClick={onClose}>
            Batal
          </Button>
          <Button
            variant="primary"
            icon={<Layers size={16} />}
            onClick={handleSave}
            loading={syncMutation.isPending}
          >
            Simpan Komposisi Program
          </Button>
        </div>
      }
    >
      <div className="p-3 mb-4 rounded bg-[var(--color-surface-subtle)] flex items-center justify-between text-sm">
        <div>
          <p className="font-semibold text-[var(--color-text-main)]">
            Program: [{program?.code}] {program?.name}
          </p>
          <p className="text-[var(--color-text-muted)] text-xs">
            Gabungkan kit barang jadi (Finished Good MLK, kit modul) dan sesi pelatihan/layanan instruktur.
          </p>
        </div>
        <Button variant="secondary" icon={<Plus size={14} />} onClick={handleAdd}>
          Tambah Item Paket
        </Button>
      </div>

      {candidateProducts.isLoading ? (
        <LoadingState />
      ) : items.length === 0 ? (
        <EmptyState
          title="Belum ada item di dalam program ini"
          description="Klik 'Tambah Item Paket' untuk memasukkan Finished Good atau Layanan/Jasa ke dalam program."
        />
      ) : (
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr>
                <th>Produk (Finished Good / Layanan)</th>
                <th style={{ width: '120px' }}>Kuantitas</th>
                <th style={{ width: '100px' }}>Satuan</th>
                <th style={{ width: '130px' }}>Peran Item</th>
                <th style={{ width: '50px' }}>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {items.map((row, idx) => (
                <tr key={idx}>
                  <td>
                    <select
                      className="select select--sm w-full"
                      value={row.component_product_id}
                      onChange={(e) => handleChange(idx, 'component_product_id', e.target.value)}
                    >
                      <option value="">Pilih item...</option>
                      {availableItems.map((p) => (
                        <option key={p.id} value={p.id}>
                          [{p.code}] {p.name} ({p.product_type === 'SERVICE' ? 'Layanan' : 'Barang Jadi'})
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
                      onChange={(e) => handleChange(idx, 'quantity', e.target.value)}
                    />
                  </td>
                  <td>
                    <select
                      className="select select--sm w-full"
                      value={row.uom_id}
                      onChange={(e) => handleChange(idx, 'uom_id', e.target.value)}
                    >
                      {uoms.data?.map((u) => (
                        <option key={u.id} value={u.id}>
                          {u.code}
                        </option>
                      ))}
                    </select>
                  </td>
                  <td>
                    <select
                      className="select select--sm w-full"
                      value={row.component_role}
                      onChange={(e) => handleChange(idx, 'component_role', e.target.value)}
                    >
                      <option value="FINISHED_GOOD">Finished Good</option>
                      <option value="SERVICE">Service / Layanan</option>
                      <option value="SUPPORTING">Pendukung</option>
                    </select>
                  </td>
                  <td>
                    <Button
                      variant="ghost"
                      icon={<Trash2 size={14} className="text-red-500" />}
                      onClick={() => handleRemove(idx)}
                    />
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
