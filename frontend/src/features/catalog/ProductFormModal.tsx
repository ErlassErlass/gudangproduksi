import { type FormEvent, useEffect, useState } from 'react';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { useToast } from '@/components/Toast';
import {
  useCategories,
  useCreateProduct,
  useUpdateProduct,
  useUoms,
  type ProductItem,
} from '@/features/master-data/api';

interface ProductFormModalProps {
  product: ProductItem | null;
  isOpen: boolean;
  onClose: () => void;
  defaultCategoryCode?: string;
  defaultProductType?: 'PRODUCT' | 'SERVICE' | 'PROGRAM';
  defaultPhysicalType?: 'PHYSICAL' | 'DIGITAL' | 'NON_INVENTORY';
  defaultTrackingMethod?: 'QUANTITY' | 'SERIALIZED' | 'BATCH' | 'NONE';
  categoryTitle: string;
}

export function ProductFormModal({
  product,
  isOpen,
  onClose,
  defaultCategoryCode,
  defaultProductType = 'PRODUCT',
  defaultPhysicalType = 'PHYSICAL',
  defaultTrackingMethod = 'QUANTITY',
  categoryTitle,
}: ProductFormModalProps) {
  const { notify } = useToast();
  const categories = useCategories();
  const uoms = useUoms();
  const createProduct = useCreateProduct();
  const updateProduct = useUpdateProduct();

  const isEdit = Boolean(product);

  const [form, setForm] = useState({
    code: '',
    legacy_code: '',
    name: '',
    description: '',
    category_id: '',
    product_type: defaultProductType,
    physical_type: defaultPhysicalType,
    fulfillment_type: 'SELL',
    tracking_method: defaultTrackingMethod,
    base_uom_id: '',
    safety_stock_qty: '0',
    reference_purchase_price: '',
    is_manufactured: false,
    is_purchasable: true,
    is_sellable: false,
    is_rentable: false,
    status: 'ACTIVE',
  });

  useEffect(() => {
    if (product) {
      setForm({
        code: product.code,
        legacy_code: product.legacy_code ?? '',
        name: product.name,
        description: product.description ?? '',
        category_id: product.category_id ? String(product.category_id) : '',
        product_type: product.product_type,
        physical_type: product.physical_type,
        fulfillment_type: product.fulfillment_type,
        tracking_method: product.tracking_method,
        base_uom_id: String(product.base_uom_id),
        safety_stock_qty: product.safety_stock_qty ?? '0',
        reference_purchase_price: product.reference_purchase_price ?? '',
        is_manufactured: product.is_manufactured,
        is_purchasable: product.is_purchasable,
        is_sellable: product.is_sellable,
        is_rentable: product.is_rentable,
        status: product.status ?? 'ACTIVE',
      });
    } else {
      // Find matching default category id
      const matchedCat = defaultCategoryCode
        ? categories.data?.find((c) => c.code === defaultCategoryCode)
        : undefined;

      const firstUom = uoms.data?.[0]?.id ? String(uoms.data[0].id) : '';

      setForm({
        code: '',
        legacy_code: '',
        name: '',
        description: '',
        category_id: matchedCat ? String(matchedCat.id) : '',
        product_type: defaultProductType,
        physical_type: defaultPhysicalType,
        fulfillment_type: defaultProductType === 'PROGRAM' ? 'SELL' : 'SELL',
        tracking_method: defaultTrackingMethod,
        base_uom_id: firstUom,
        safety_stock_qty: '0',
        reference_purchase_price: '',
        is_manufactured: defaultCategoryCode === 'WIP-MOD' || defaultCategoryCode === 'WIP-BOX' || defaultCategoryCode === 'FG',
        is_purchasable: defaultCategoryCode === 'COMP',
        is_sellable: defaultCategoryCode === 'FG' || defaultProductType === 'PROGRAM' || defaultProductType === 'SERVICE',
        is_rentable: defaultCategoryCode === 'FG',
        status: 'ACTIVE',
      });
    }
  }, [product, isOpen, categories.data, uoms.data, defaultCategoryCode, defaultProductType, defaultPhysicalType, defaultTrackingMethod]);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!form.code || !form.name || !form.base_uom_id) {
      notify('Kode, nama barang, dan satuan wajib diisi.', 'error');
      return;
    }

    const payload: Record<string, unknown> = {
      code: form.code.trim(),
      legacy_code: form.legacy_code.trim() || null,
      name: form.name.trim(),
      description: form.description.trim() || null,
      category_id: form.category_id ? Number(form.category_id) : null,
      product_type: form.product_type,
      physical_type: form.physical_type,
      fulfillment_type: form.fulfillment_type,
      tracking_method: form.tracking_method,
      base_uom_id: Number(form.base_uom_id),
      safety_stock_qty: form.safety_stock_qty || '0',
      reference_purchase_price: form.reference_purchase_price || null,
      is_manufactured: form.is_manufactured,
      is_purchasable: form.is_purchasable,
      is_sellable: form.is_sellable,
      is_rentable: form.is_rentable,
      status: form.status,
    };

    try {
      if (isEdit && product) {
        await updateProduct.mutateAsync({ id: product.id, payload });
        notify(`Data [${form.code}] berhasil diperbarui.`, 'success');
      } else {
        await createProduct.mutateAsync(payload);
        notify(`Item baru [${form.code}] berhasil didaftarkan.`, 'success');
      }
      onClose();
    } catch (err: any) {
      notify(err?.response?.data?.message ?? 'Gagal menyimpan data barang.', 'error');
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEdit ? `Ubah Data — [${product?.code}] ${product?.name}` : `Tambah ${categoryTitle} Baru`}
      description="Lengkapi identitas katalog, metode tracking persediaan, dan harga referensi."
      size="lg"
      footer={
        <div className="flex gap-2 justify-end w-full">
          <Button variant="secondary" onClick={onClose}>
            Batal
          </Button>
          <Button
            variant="primary"
            onClick={handleSubmit}
            loading={createProduct.isPending || updateProduct.isPending}
          >
            {isEdit ? 'Simpan Perubahan' : 'Buat Item'}
          </Button>
        </div>
      }
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="grid grid--2">
          <FormField label="Kode Produk / SKU" htmlFor="prod-code" required>
            <input
              id="prod-code"
              type="text"
              className="input font-mono"
              placeholder="Mis. SP.PART.ER.019 / M02"
              value={form.code}
              onChange={(e) => setForm({ ...form, code: e.target.value })}
              required
            />
          </FormField>
          <FormField label="Kode Lama (Legacy Code)" htmlFor="prod-legacy">
            <input
              id="prod-legacy"
              type="text"
              className="input font-mono"
              placeholder="Mis. MLK-STD-01"
              value={form.legacy_code}
              onChange={(e) => setForm({ ...form, legacy_code: e.target.value })}
            />
          </FormField>
        </div>

        <FormField label="Nama Item / Deskripsi Singkat" htmlFor="prod-name" required>
          <input
            id="prod-name"
            type="text"
            className="input"
            placeholder="Mis. Sensor Ultrasonik HC-SR04"
            value={form.name}
            onChange={(e) => setForm({ ...form, name: e.target.value })}
            required
          />
        </FormField>

        <div className="grid grid--2">
          <FormField label="Kategori" htmlFor="prod-cat">
            <select
              id="prod-cat"
              className="select"
              value={form.category_id}
              onChange={(e) => setForm({ ...form, category_id: e.target.value })}
            >
              <option value="">Pilih Kategori...</option>
              {categories.data?.map((cat) => (
                <option key={cat.id} value={cat.id}>
                  [{cat.code}] {cat.name}
                </option>
              ))}
            </select>
          </FormField>
          <FormField label="Satuan Dasar (Base UOM)" htmlFor="prod-uom" required>
            <select
              id="prod-uom"
              className="select"
              value={form.base_uom_id}
              onChange={(e) => setForm({ ...form, base_uom_id: e.target.value })}
              required
            >
              <option value="">Pilih Satuan...</option>
              {uoms.data?.map((u) => (
                <option key={u.id} value={u.id}>
                  {u.code} — {u.name}
                </option>
              ))}
            </select>
          </FormField>
        </div>

        <div className="grid grid--3">
          <FormField label="Tipe Produk" htmlFor="prod-type" required>
            <select
              id="prod-type"
              className="select"
              value={form.product_type}
              onChange={(e) => {
                const val = e.target.value as any;
                setForm({
                  ...form,
                  product_type: val,
                  physical_type: val === 'SERVICE' || val === 'PROGRAM' ? 'NON_INVENTORY' : 'PHYSICAL',
                  tracking_method: val === 'SERVICE' || val === 'PROGRAM' ? 'NONE' : 'QUANTITY',
                });
              }}
            >
              <option value="PRODUCT">PRODUCT (Barang Fisik)</option>
              <option value="SERVICE">SERVICE (Jasa / Layanan)</option>
              <option value="PROGRAM">PROGRAM (Paket Edukasi)</option>
            </select>
          </FormField>

          <FormField label="Metode Tracking Stok" htmlFor="prod-track" required>
            <select
              id="prod-track"
              className="select"
              value={form.tracking_method}
              disabled={form.product_type === 'SERVICE' || form.product_type === 'PROGRAM'}
              onChange={(e) => setForm({ ...form, tracking_method: e.target.value as any })}
            >
              <option value="QUANTITY">QUANTITY (Kuantitas Angka)</option>
              <option value="SERIALIZED">SERIALIZED (Nomor Seri Unik)</option>
              <option value="BATCH">BATCH (Lot / Batch)</option>
              <option value="NONE">NONE (Non-Stok)</option>
            </select>
          </FormField>

          <FormField label="Status" htmlFor="prod-status">
            <select
              id="prod-status"
              className="select"
              value={form.status}
              onChange={(e) => setForm({ ...form, status: e.target.value })}
            >
              <option value="ACTIVE">ACTIVE</option>
              <option value="INACTIVE">INACTIVE</option>
            </select>
          </FormField>
        </div>

        <div className="grid grid--2">
          <FormField label="Safety Stock Min. (Jumlah Cadangan Aman)" htmlFor="prod-safety">
            <input
              id="prod-safety"
              type="number"
              step="0.0001"
              min="0"
              className="input font-mono"
              value={form.safety_stock_qty}
              onChange={(e) => setForm({ ...form, safety_stock_qty: e.target.value })}
            />
          </FormField>
          <FormField label="Harga Referensi Beli (IDR)" htmlFor="prod-price">
            <input
              id="prod-price"
              type="number"
              min="0"
              className="input font-mono"
              placeholder="Mis. 25000"
              value={form.reference_purchase_price}
              onChange={(e) => setForm({ ...form, reference_purchase_price: e.target.value })}
            />
          </FormField>
        </div>

        <div className="flex gap-4 pt-2 border-t border-[var(--color-border-subtle)] text-sm">
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={form.is_manufactured}
              onChange={(e) => setForm({ ...form, is_manufactured: e.target.checked })}
            />
            <span>Diproduksi / Dirakit Sendiri</span>
          </label>
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={form.is_purchasable}
              onChange={(e) => setForm({ ...form, is_purchasable: e.target.checked })}
            />
            <span>Dapat Dibeli (PO)</span>
          </label>
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={form.is_sellable}
              onChange={(e) => setForm({ ...form, is_sellable: e.target.checked })}
            />
            <span>Dapat Dijual</span>
          </label>
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={form.is_rentable}
              onChange={(e) => setForm({ ...form, is_rentable: e.target.checked })}
            />
            <span>Dapat Disewakan / Dipinjam</span>
          </label>
        </div>
      </form>
    </Modal>
  );
}
