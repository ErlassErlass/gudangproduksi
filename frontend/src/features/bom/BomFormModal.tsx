import { useState, useEffect, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Plus, Trash2 } from 'lucide-react';
import { http } from '@/api/client';
import type { ResourceResponse } from '@/api/types';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { useToast } from '@/components/Toast';
import { useProducts, useUoms } from '@/features/master-data/api';

interface BomItemRow {
  component_product_id: number | '';
  quantity: string;
  uom_id: number | '';
  scrap_rate: string;
  notes: string;
}

interface BomFormModalProps {
  initialBom?: {
    id: number;
    code: string;
    name: string;
    product_id: number;
    bom_type: 'STANDARD_MLK' | 'FLEXIBLE';
    active_version?: {
      items: {
        component_product_id: number;
        quantity: string;
        uom_id: number;
        scrap_rate: string;
        notes?: string | null;
      }[];
    } | null;
  } | null;
  isOpen: boolean;
  onClose: () => void;
  onSuccess?: () => void;
}

export function BomFormModal({ initialBom, isOpen, onClose, onSuccess }: BomFormModalProps) {
  const { notify } = useToast();
  const qc = useQueryClient();
  const isEdit = Boolean(initialBom);

  const productsQuery = useProducts({ per_page: 100 });
  const uomsQuery = useUoms();

  const [code, setCode] = useState(initialBom?.code ?? '');
  const [name, setName] = useState(initialBom?.name ?? '');
  const [productId, setProductId] = useState<number | ''>(initialBom?.product_id ?? '');
  const [bomType, setBomType] = useState<'STANDARD_MLK' | 'FLEXIBLE'>(initialBom?.bom_type ?? 'STANDARD_MLK');

  const [items, setItems] = useState<BomItemRow[]>([
    { component_product_id: '', quantity: '1', uom_id: '', scrap_rate: '0', notes: '' },
  ]);

  // If editing, load active version items
  const bomDetailQuery = useQuery({
    queryKey: ['boms', initialBom?.id],
    enabled: isEdit && Boolean(initialBom?.id),
    queryFn: async () => (await http.get<ResourceResponse<any>>(`/boms/${initialBom?.id}`)).data.data,
  });

  useEffect(() => {
    if (bomDetailQuery.data) {
      const b = bomDetailQuery.data;
      setCode(b.code);
      setName(b.name);
      setProductId(b.product_id);
      setBomType(b.bom_type);

      const activeVer = b.versions?.find((v: any) => v.status === 'ACTIVE') ?? b.versions?.[0];
      if (activeVer?.items && activeVer.items.length > 0) {
        setItems(
          activeVer.items.map((it: any) => ({
            component_product_id: it.component_product_id,
            quantity: String(Number(it.quantity)),
            uom_id: it.uom_id,
            scrap_rate: String(Number(it.scrap_rate || 0)),
            notes: it.notes ?? '',
          }))
        );
      }
    }
  }, [bomDetailQuery.data]);

  const saveMutation = useMutation({
    mutationFn: async () => {
      const payloadItems = items.map((it) => ({
        component_product_id: Number(it.component_product_id),
        quantity: Number(it.quantity),
        uom_id: Number(it.uom_id),
        scrap_rate: Number(it.scrap_rate || 0),
        notes: it.notes || undefined,
      }));

      if (isEdit && initialBom) {
        return (
          await http.put(`/boms/${initialBom.id}`, {
            name,
            bom_type: bomType,
            items: payloadItems,
          })
        ).data;
      } else {
        return (
          await http.post('/boms', {
            code,
            name,
            product_id: Number(productId),
            bom_type: bomType,
            items: payloadItems,
          })
        ).data;
      }
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['boms'] });
      notify(`Resep BOM ${code} berhasil ${isEdit ? 'diperbarui' : 'disimpan'}.`, 'success');
      onSuccess?.();
      onClose();
    },
    onError: (err: any) => {
      notify(err?.response?.data?.message ?? err.message ?? 'Gagal menyimpan resep BOM.', 'error');
    },
  });

  const handleAddItem = () => {
    setItems([
      ...items,
      { component_product_id: '', quantity: '1', uom_id: uomsQuery.data?.[0]?.id ?? '', scrap_rate: '0', notes: '' },
    ]);
  };

  const handleRemoveItem = (index: number) => {
    if (items.length <= 1) {
      notify('Resep BOM harus memiliki minimal 1 bahan masukan.', 'error');
      return;
    }
    setItems(items.filter((_, i) => i !== index));
  };

  const handleItemChange = (index: number, field: keyof BomItemRow, value: any) => {
    const updated = [...items];
    updated[index] = { ...updated[index], [field]: value };

    // If component product changed, auto-fill UOM from product's base_uom_id
    if (field === 'component_product_id' && value) {
      const prod = productsQuery.data?.data?.find((p) => p.id === Number(value));
      if (prod?.base_uom_id) {
        updated[index].uom_id = prod.base_uom_id;
      }
    }

    setItems(updated);
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    if (!name.trim()) {
      notify('Nama resep BOM wajib diisi.', 'error');
      return;
    }
    if (!isEdit && (!code.trim() || !productId)) {
      notify('Kode BOM dan produk output wajib diisi.', 'error');
      return;
    }
    for (const it of items) {
      if (!it.component_product_id || Number(it.quantity) <= 0 || !it.uom_id) {
        notify('Pastikan semua bahan masukan memiliki komponen, kuantitas > 0, dan satuan UOM.', 'error');
        return;
      }
    }
    saveMutation.mutate();
  };

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      title={isEdit ? `Edit Resep BOM: ${initialBom?.code}` : 'Tambah Resep BOM Baru'}
      description="Konfigurasi formula bahan masukan untuk merakit Modul, Box, atau Finished Good."
      size="lg"
      footer={
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, width: '100%' }}>
          <Button variant="secondary" onClick={onClose} disabled={saveMutation.isPending}>
            Batal
          </Button>
          <Button variant="primary" type="submit" form="bom-form" loading={saveMutation.isPending}>
            {isEdit ? 'Simpan Perubahan BOM' : 'Simpan Resep BOM'}
          </Button>
        </div>
      }
    >
      <form id="bom-form" onSubmit={handleSubmit} noValidate style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        <div className="grid grid--2">
          <FormField label="Kode BOM" htmlFor="bom-code" required>
            <input
              id="bom-code"
              className="input font-mono"
              placeholder="Contoh: BOM-MLK-1001"
              value={code}
              disabled={isEdit}
              onChange={(e) => setCode(e.target.value.toUpperCase())}
            />
          </FormField>

          <FormField label="Tipe Formula BOM" htmlFor="bom-type" required>
            <select
              id="bom-type"
              className="select"
              value={bomType}
              onChange={(e) => setBomType(e.target.value as any)}
            >
              <option value="STANDARD_MLK">Standard MLK (Fixed Structure)</option>
              <option value="FLEXIBLE">Fleksibel (Custom / Dynamic)</option>
            </select>
          </FormField>
        </div>

        <div className="grid grid--2">
          <FormField label="Produk Output yang Dihasilkan" htmlFor="bom-prod" required>
            <select
              id="bom-prod"
              className="select"
              value={productId}
              disabled={isEdit}
              onChange={(e) => setProductId(e.target.value ? Number(e.target.value) : '')}
            >
              <option value="">Pilih Produk Output</option>
              {productsQuery.data?.data
                ?.filter((p) => p.physical_type === 'PHYSICAL')
                .map((p) => (
                  <option key={p.id} value={p.id}>
                    [{p.code}] {p.name}
                  </option>
                ))}
            </select>
          </FormField>

          <FormField label="Nama Resep BOM" htmlFor="bom-name" required>
            <input
              id="bom-name"
              className="input"
              placeholder="Contoh: Resep BOM Standar Mobile Learning Kit 1001"
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </FormField>
        </div>

        {/* Bahan Baku Masukan Table */}
        <div className="card" style={{ padding: 14 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
            <span style={{ fontWeight: 700, fontSize: '0.85rem', textTransform: 'uppercase', color: 'var(--color-primary-dark, #263f7d)' }}>
              Daftar Bahan Masukan (Components / Subassemblies):
            </span>
            <Button size="sm" variant="secondary" icon={<Plus size={14} />} onClick={handleAddItem} type="button">
              + Tambah Bahan
            </Button>
          </div>

          <div className="table-wrap">
            <table className="table" style={{ fontSize: '0.825rem' }}>
              <thead>
                <tr>
                  <th style={{ width: '40%' }}>Komponen Bahan</th>
                  <th style={{ width: '20%' }}>Kuantitas/Unit</th>
                  <th style={{ width: '20%' }}>Satuan (UOM)</th>
                  <th style={{ width: '15%' }}>Scrap (0-1)</th>
                  <th style={{ width: '5%', textAlign: 'center' }}>Aksi</th>
                </tr>
              </thead>
              <tbody>
                {items.map((row, idx) => (
                  <tr key={idx}>
                    <td>
                      <select
                        className="select select--sm"
                        style={{ width: '100%' }}
                        value={row.component_product_id}
                        onChange={(e) => handleItemChange(idx, 'component_product_id', e.target.value ? Number(e.target.value) : '')}
                      >
                        <option value="">Pilih Komponen</option>
                        {productsQuery.data?.data
                          ?.filter((p) => p.id !== Number(productId))
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
                        className="input select--sm font-mono font-bold"
                        value={row.quantity}
                        onChange={(e) => handleItemChange(idx, 'quantity', e.target.value)}
                      />
                    </td>
                    <td>
                      <select
                        className="select select--sm"
                        style={{ width: '100%' }}
                        value={row.uom_id}
                        onChange={(e) => handleItemChange(idx, 'uom_id', e.target.value ? Number(e.target.value) : '')}
                      >
                        <option value="">Pilih UOM</option>
                        {uomsQuery.data?.map((u) => (
                          <option key={u.id} value={u.id}>
                            {u.code} &mdash; {u.name}
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
                        className="input select--sm font-mono"
                        placeholder="0.00"
                        value={row.scrap_rate}
                        onChange={(e) => handleItemChange(idx, 'scrap_rate', e.target.value)}
                      />
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      <Button
                        size="sm"
                        variant="ghost"
                        iconOnly
                        type="button"
                        icon={<Trash2 size={14} color="var(--color-danger, #d96a36)" />}
                        onClick={() => handleRemoveItem(idx)}
                        aria-label="Hapus baris bahan"
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </form>
    </Modal>
  );
}
