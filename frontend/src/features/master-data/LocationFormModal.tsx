import { useState, type FormEvent } from 'react';
import { ApiError } from '@/api/client';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { useToast } from '@/components/Toast';
import {
  useCreateLocation,
  useLocations,
  useUpdateLocation,
  useWarehouses,
  type LocationItem,
} from './api';

interface LocationFormModalProps {
  initialData?: LocationItem | null;
  defaultWarehouseId?: number;
  defaultParentId?: number | null;
  defaultType?: string;
  onClose: () => void;
  onSuccess?: () => void;
}

export function LocationFormModal({
  initialData,
  defaultWarehouseId,
  defaultParentId,
  defaultType,
  onClose,
  onSuccess,
}: LocationFormModalProps) {
  const { notify } = useToast();
  const isEdit = Boolean(initialData);
  const warehouses = useWarehouses();
  const allLocations = useLocations();

  const [warehouseId, setWarehouseId] = useState<number | ''>(
    initialData?.warehouse_id ?? defaultWarehouseId ?? (warehouses.data?.[0]?.id ?? '')
  );
  const [locationType, setLocationType] = useState<string>(
    initialData?.location_type ?? defaultType ?? 'RACK'
  );
  const [parentId, setParentId] = useState<number | ''>(
    initialData?.parent_id ?? defaultParentId ?? ''
  );
  const [code, setCode] = useState(initialData?.code ?? '');
  const [name, setName] = useState(initialData?.name ?? '');
  const [barcode, setBarcode] = useState(initialData?.barcode ?? '');
  const [isActive, setIsActive] = useState<boolean>(initialData?.is_active ?? true);

  const createLocation = useCreateLocation();
  const updateLocation = useUpdateLocation();
  const isSaving = createLocation.isPending || updateLocation.isPending;
  const currentError = isEdit ? updateLocation.error : createLocation.error;
  const apiError = currentError instanceof ApiError ? currentError : null;

  // Filter possible parents based on selected warehouse and hierarchy level
  const possibleParents = (allLocations.data ?? []).filter((loc) => {
    if (warehouseId && loc.warehouse_id !== Number(warehouseId)) return false;
    if (initialData && loc.id === initialData.id) return false;
    if (locationType === 'FLOOR') {
      return loc.location_type === 'BUILDING';
    }
    if (locationType === 'ROOM') {
      return loc.location_type === 'FLOOR' || loc.location_type === 'BUILDING';
    }
    if (locationType === 'RACK' || locationType === 'BIN') {
      return loc.location_type === 'ROOM' || loc.location_type === 'FLOOR';
    }
    return true;
  });

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!warehouseId) {
      notify('Pilih gedung / warehouse terlebih dahulu.', 'error');
      return;
    }
    if (!code.trim() || !name.trim()) {
      notify('Kode dan nama lokasi wajib diisi.', 'error');
      return;
    }

    try {
      if (isEdit && initialData) {
        await updateLocation.mutateAsync({
          id: initialData.id,
          payload: {
            parent_id: parentId ? Number(parentId) : null,
            code,
            name,
            barcode: barcode.trim() || null,
            location_type: locationType,
            is_active: isActive,
          },
        });
        notify(`Lokasi ${code} berhasil diperbarui.`, 'success');
      } else {
        await createLocation.mutateAsync({
          warehouse_id: Number(warehouseId),
          parent_id: parentId ? Number(parentId) : null,
          code,
          name,
          barcode: barcode.trim() || null,
          location_type: locationType,
          is_active: isActive,
        });
        notify(`Lokasi ${code} berhasil dibuat.`, 'success');
      }
      onSuccess?.();
      onClose();
    } catch (err) {
      if (!(err instanceof ApiError && err.isValidation)) {
        notify(err instanceof Error ? err.message : 'Gagal menyimpan data lokasi.', 'error');
      }
    }
  };

  return (
    <Modal
      open
      title={isEdit ? `Edit Lokasi: ${initialData?.code}` : 'Tambah Lokasi Baru'}
      description="Kelola hierarki lokasi fisik secara dinamis: Gedung, Lantai, Ruangan, dan Rak/Bin."
      onClose={onClose}
      footer={
        <>
          <Button onClick={onClose} disabled={isSaving}>
            Batal
          </Button>
          <Button variant="primary" type="submit" form="location-form" loading={isSaving}>
            {isEdit ? 'Simpan Perubahan' : 'Buat Lokasi'}
          </Button>
        </>
      }
    >
      <form id="location-form" onSubmit={handleSubmit} noValidate>
        <div className="grid grid--2">
          <FormField label="Gedung / Warehouse" htmlFor="loc-wh" required error={apiError?.fieldError('warehouse_id')}>
            <select
              id="loc-wh"
              className="select"
              value={warehouseId}
              disabled={isEdit}
              onChange={(e) => {
                const val = e.target.value ? Number(e.target.value) : '';
                setWarehouseId(val);
                setParentId('');
              }}
            >
              <option value="">Pilih Gedung</option>
              {warehouses.data?.map((wh) => (
                <option key={wh.id} value={wh.id}>
                  {wh.code} — {wh.name}
                </option>
              ))}
            </select>
          </FormField>

          <FormField label="Tingkat / Level Lokasi" htmlFor="loc-type" required error={apiError?.fieldError('location_type')}>
            <select
              id="loc-type"
              className="select"
              value={locationType}
              onChange={(e) => {
                setLocationType(e.target.value);
                setParentId('');
              }}
            >
              <option value="FLOOR">Lantai (FLOOR)</option>
              <option value="ROOM">Ruangan (ROOM)</option>
              <option value="RACK">Rak (RACK)</option>
              <option value="BIN">Kompartemen Rak (BIN)</option>
              <option value="BUILDING">Gedung (BUILDING)</option>
            </select>
          </FormField>
        </div>

        <FormField
          label="Lokasi Induk (Parent)"
          htmlFor="loc-parent"
          description="Pilih lokasi di atasnya dalam hierarki gedung"
          error={apiError?.fieldError('parent_id')}
        >
          <select
            id="loc-parent"
            className="select"
            value={parentId}
            onChange={(e) => setParentId(e.target.value ? Number(e.target.value) : '')}
          >
            <option value="">(Tanpa Induk / Tingkat Teratas di Gedung)</option>
            {possibleParents.map((p) => (
              <option key={p.id} value={p.id}>
                [{p.location_type}] {p.code} — {p.name}
              </option>
            ))}
          </select>
        </FormField>

        <div className="grid grid--2">
          <FormField label="Kode Lokasi" htmlFor="loc-code" required error={apiError?.fieldError('code')}>
            <input
              id="loc-code"
              className="input"
              value={code}
              placeholder="Contoh: POP.L3R3A1"
              disabled={isEdit}
              onChange={(e) => setCode(e.target.value.toUpperCase())}
            />
          </FormField>

          <FormField label="Barcode / Scan Code" htmlFor="loc-barcode" error={apiError?.fieldError('barcode')}>
            <input
              id="loc-barcode"
              className="input"
              value={barcode}
              placeholder="Contoh: LOC-POP-L3R3A1"
              onChange={(e) => setBarcode(e.target.value)}
            />
          </FormField>
        </div>

        <FormField label="Nama Lokasi" htmlFor="loc-name" required error={apiError?.fieldError('name')}>
          <input
            id="loc-name"
            className="input"
            value={name}
            placeholder="Contoh: Rak Komponen A Baris 1"
            onChange={(e) => setName(e.target.value)}
          />
        </FormField>

        <FormField label="Status Operasional" htmlFor="loc-status">
          <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', marginTop: 4 }}>
            <input
              id="loc-status"
              type="checkbox"
              checked={isActive}
              onChange={(e) => setIsActive(e.target.checked)}
            />
            <span>Lokasi Aktif & Siap Menerima Stok Fisik</span>
          </label>
        </FormField>
      </form>
    </Modal>
  );
}
