import { useState, type FormEvent } from 'react';
import { ApiError } from '@/api/client';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { useToast } from '@/components/Toast';
import { useCreateCustomer, useUpdateCustomer, type PartnerItem } from './api';

interface CustomerFormModalProps {
  initialData?: PartnerItem | null;
  onClose: () => void;
  onSuccess?: () => void;
}

export function CustomerFormModal({ initialData, onClose, onSuccess }: CustomerFormModalProps) {
  const { notify } = useToast();
  const isEdit = Boolean(initialData);

  const [code, setCode] = useState(initialData?.code ?? '');
  const [name, setName] = useState(initialData?.name ?? '');
  const [salesName, setSalesName] = useState(initialData?.sales_name ?? '');
  const [customerType, setCustomerType] = useState<string>(initialData?.customer_type ?? 'SCHOOL');
  const [schoolName, setSchoolName] = useState(initialData?.school_name ?? '');
  const [contactName, setContactName] = useState(initialData?.contact_name ?? '');
  const [phone, setPhone] = useState(initialData?.phone ?? '');
  const [email, setEmail] = useState(initialData?.email ?? '');

  const createCustomer = useCreateCustomer();
  const updateCustomer = useUpdateCustomer();
  const isSaving = createCustomer.isPending || updateCustomer.isPending;
  const currentError = isEdit ? updateCustomer.error : createCustomer.error;
  const apiError = currentError instanceof ApiError ? currentError : null;

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!code.trim() || !name.trim()) {
      notify('Kode dan nama customer wajib diisi.', 'error');
      return;
    }

    try {
      const payload = {
        name: name.trim(),
        sales_name: salesName.trim() || null,
        customer_type: customerType,
        school_name: customerType === 'SCHOOL' ? (schoolName.trim() || name.trim()) : null,
        contact_name: contactName.trim() || null,
        phone: phone.trim() || null,
        email: email.trim() || null,
      };

      if (isEdit && initialData) {
        await updateCustomer.mutateAsync({
          id: initialData.id,
          payload,
        });
        notify(`Data customer ${code} berhasil diperbarui.`, 'success');
      } else {
        await createCustomer.mutateAsync({
          ...payload,
          code: code.trim().toUpperCase(),
        });
        notify(`Customer ${code} berhasil dibuat.`, 'success');
      }
      onSuccess?.();
      onClose();
    } catch (err) {
      if (!(err instanceof ApiError && err.isValidation)) {
        notify(err instanceof Error ? err.message : 'Gagal menyimpan customer.', 'error');
      }
    }
  };

  return (
    <Modal
      open
      title={isEdit ? `Edit Customer: ${initialData?.code}` : 'Tambah Customer Baru'}
      description="Manajemen pelanggan, sekolah mitra, atau instansi penyewa."
      onClose={onClose}
      footer={
        <>
          <Button onClick={onClose} disabled={isSaving}>
            Batal
          </Button>
          <Button variant="primary" type="submit" form="customer-form" loading={isSaving}>
            {isEdit ? 'Simpan Perubahan' : 'Buat Customer'}
          </Button>
        </>
      }
    >
      <form id="customer-form" onSubmit={handleSubmit} noValidate>
        <div className="grid grid--2">
          <FormField label="Kode Customer" htmlFor="cust-code" required error={apiError?.fieldError('code')}>
            <input
              id="cust-code"
              className="input"
              value={code}
              placeholder="Contoh: CUST-SMP-01"
              disabled={isEdit}
              onChange={(e) => setCode(e.target.value.toUpperCase())}
            />
          </FormField>

          <FormField label="Tipe Pelanggan" htmlFor="cust-type" required error={apiError?.fieldError('customer_type')}>
            <select
              id="cust-type"
              className="select"
              value={customerType}
              onChange={(e) => setCustomerType(e.target.value)}
            >
              <option value="SCHOOL">Sekolah / Institusi Pendidikan</option>
              <option value="ORGANIZATION">Organisasi / Perusahaan</option>
              <option value="INDIVIDUAL">Pribadi / Perorangan</option>
            </select>
          </FormField>
        </div>

        <FormField label="Nama Customer / Lembaga" htmlFor="cust-name" required error={apiError?.fieldError('name')}>
          <input
            id="cust-name"
            className="input"
            value={name}
            placeholder="Contoh: SMP Bina Insan Jakarta"
            onChange={(e) => setName(e.target.value)}
          />
        </FormField>

        {customerType === 'SCHOOL' && (
          <FormField label="Nama Sekolah Resmi" htmlFor="cust-school" error={apiError?.fieldError('school_name')}>
            <input
              id="cust-school"
              className="input"
              value={schoolName}
              placeholder="Contoh: SMP Negeri 1 Jakarta"
              onChange={(e) => setSchoolName(e.target.value)}
            />
          </FormField>
        )}

        <FormField label="Sales Penanggung Jawab" htmlFor="cust-sales" error={apiError?.fieldError('sales_name')}>
          <input
            id="cust-sales"
            className="input"
            value={salesName}
            placeholder="Pilih atau ketik nama sales (mis. Andi Wijaya)"
            list="sales-suggestions"
            onChange={(e) => setSalesName(e.target.value)}
          />
          <datalist id="sales-suggestions">
            <option value="Andi Wijaya" />
            <option value="Rina Marlina" />
            <option value="Dimas Pratama" />
            <option value="Siti Rahma" />
            <option value="Fajar Hidayat" />
            <option value="Bambang Pamungkas" />
            <option value="Bayu Setiawan" />
            <option value="Citra Lestari" />
            <option value="Eko Prasetyo" />
          </datalist>
        </FormField>

        <div className="grid grid--2">
          <FormField label="Kontak Person (PIC)" htmlFor="cust-pic" error={apiError?.fieldError('contact_name')}>
            <input
              id="cust-pic"
              className="input"
              value={contactName}
              placeholder="Nama penanggung jawab"
              onChange={(e) => setContactName(e.target.value)}
            />
          </FormField>

          <FormField label="Nomor Telepon / WA" htmlFor="cust-phone" error={apiError?.fieldError('phone')}>
            <input
              id="cust-phone"
              className="input"
              value={phone}
              placeholder="0812xxxxxxx"
              onChange={(e) => setPhone(e.target.value)}
            />
          </FormField>
        </div>

        <FormField label="Email" htmlFor="cust-email" error={apiError?.fieldError('email')}>
          <input
            id="cust-email"
            type="email"
            className="input"
            value={email}
            placeholder="kontak@sekolah.sch.id"
            onChange={(e) => setEmail(e.target.value)}
          />
        </FormField>
      </form>
    </Modal>
  );
}
