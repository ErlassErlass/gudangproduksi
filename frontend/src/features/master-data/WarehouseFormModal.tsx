import { useState, type FormEvent } from 'react';
import { ApiError } from '@/api/client';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { useToast } from '@/components/Toast';
import { useCreateWarehouse, useUpdateWarehouse, type WarehouseItem } from './api';

interface WarehouseFormModalProps {
  initialData?: WarehouseItem | null;
  onClose: () => void;
  onSuccess?: () => void;
}

export function WarehouseFormModal({ initialData, onClose, onSuccess }: WarehouseFormModalProps) {
  const { notify } = useToast();
  const isEdit = Boolean(initialData);

  const [code, setCode] = useState(initialData?.code ?? '');
  const [name, setName] = useState(initialData?.name ?? '');
  const [warehouseType, setWarehouseType] = useState<'INTERNAL' | 'EXTERNAL_VENDOR' | 'CUSTOMER'>(
    initialData?.warehouse_type ?? 'INTERNAL'
  );
  const [address, setAddress] = useState(initialData?.address ?? '');
  const [isActive, setIsActive] = useState<boolean>(initialData?.is_active ?? true);

  const createWarehouse = useCreateWarehouse();
  const updateWarehouse = useUpdateWarehouse();
  const isSaving = createWarehouse.isPending || updateWarehouse.isPending;
  const currentError = isEdit ? updateWarehouse.error : createWarehouse.error;
  const apiError = currentError instanceof ApiError ? currentError : null;

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!code.trim() || !name.trim()) {
      notify('Kode dan nama gedung wajib diisi.', 'error');
      return;
    }

    try {
      if (isEdit && initialData) {
        await updateWarehouse.mutateAsync({
          id: initialData.id,
          payload: {
            name,
            warehouse_type: warehouseType,
            address: address.trim() || null,
            is_active: isActive,
          },
        });
        notify(`Gedung ${code} berhasil diperbarui.`, 'success');
      } else {
        await createWarehouse.mutateAsync({
          code: code.trim().toUpperCase(),
          name: name.trim(),
          warehouse_type: warehouseType,
          address: address.trim() || null,
        });
        notify(`Gedung ${code} berhasil dibuat.`, 'success');
      }
      onSuccess?.();
      onClose();
    } catch (err) {
      if (!(err instanceof ApiError && err.isValidation)) {
        notify(err instanceof Error ? err.message : 'Gagal menyimpan gedung.', 'error');
      }
    }
  };

  return (
    <Modal
      open
      title={isEdit ? `Edit Gedung: ${initialData?.code}` : 'Tambah Gedung / Warehouse'}
      description="Gedung merupakan wadah level tertinggi dalam hierarki lokasi gudang."
      onClose={onClose}
      footer={
        <>
          <Button onClick={onClose} disabled={isSaving}>
            Batal
          </Button>
          <Button variant="primary" type="submit" form="warehouse-form" loading={isSaving}>
            {isEdit ? 'Simpan Perubahan' : 'Buat Gedung'}
          </Button>
        </>
      }
    >
      <form id="warehouse-form" onSubmit={handleSubmit} noValidate>
        <div className="grid grid--2">
          <FormField label="Kode Gedung" htmlFor="wh-code" required error={apiError?.fieldError('code')}>
            <input
              id="wh-code"
              className="input"
              value={code}
              placeholder="Contoh: WH-POP"
              disabled={isEdit}
              onChange={(e) => setCode(e.target.value.toUpperCase())}
            />
          </FormField>

          <FormField label="Tipe Gedung" htmlFor="wh-type" required error={apiError?.fieldError('warehouse_type')}>
            <select
              id="wh-type"
              className="select"
              value={warehouseType}
              onChange={(e) => setWarehouseType(e.target.value as any)}
            >
              <option value="INTERNAL">Internal Perusahaan</option>
              <option value="EXTERNAL_VENDOR">Vendor Eksternal</option>
              <option value="CUSTOMER">Customer / Konsinyasi</option>
            </select>
          </FormField>
        </div>

        <FormField label="Nama Gedung" htmlFor="wh-name" required error={apiError?.fieldError('name')}>
          <input
            id="wh-name"
            className="input"
            value={name}
            placeholder="Contoh: Gedung Gudang Pusat ERLASS"
            onChange={(e) => setName(e.target.value)}
          />
        </FormField>

        <FormField label="Alamat / Keterangan Lokasi" htmlFor="wh-address" error={apiError?.fieldError('address')}>
          <textarea
            id="wh-address"
            className="input"
            rows={3}
            value={address}
            placeholder="Alamat lengkap gedung atau catatan posisi"
            onChange={(e) => setAddress(e.target.value)}
          />
        </FormField>

        {isEdit && (
          <FormField label="Status Gedung" htmlFor="wh-status">
            <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', marginTop: 4 }}>
              <input
                id="wh-status"
                type="checkbox"
                checked={isActive}
                onChange={(e) => setIsActive(e.target.checked)}
              />
              <span>Gedung Aktif Digunakan</span>
            </label>
          </FormField>
        )}
      </form>
    </Modal>
  );
}
