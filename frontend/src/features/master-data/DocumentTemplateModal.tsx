import { useState, type FormEvent } from 'react';
import { ApiError } from '@/api/client';
import { Button } from '@/components/Button';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { useToast } from '@/components/Toast';
import {
  useCreateDocumentTemplate,
  useUpdateDocumentTemplate,
  type DocumentTemplateItem,
} from './api';

interface DocumentTemplateModalProps {
  initialData?: DocumentTemplateItem | null;
  mode?: 'edit' | 'preview';
  onClose: () => void;
  onSuccess?: () => void;
}

export function DocumentTemplateModal({
  initialData,
  mode = 'edit',
  onClose,
  onSuccess,
}: DocumentTemplateModalProps) {
  const { notify } = useToast();
  const isEdit = Boolean(initialData) && mode === 'edit';
  const isPreview = mode === 'preview';

  const [code, setCode] = useState(initialData?.code ?? '');
  const [name, setName] = useState(initialData?.name ?? '');
  const [documentType, setDocumentType] = useState(initialData?.document_type ?? 'SURAT_JALAN');
  const [templateBody, setTemplateBody] = useState(
    initialData?.template_body ??
      `ERLASS Prokreatif Indonesia
SURAT JALAN / DOKUMEN OPERASIONAL
=======================================
Nomor: {{doc_no}}
Tanggal: {{doc_date}}
Penerima: {{recipient_name}}
Alamat: {{recipient_address}}

Daftar Barang / Material:
{{items_table}}

Catatan:
{{notes}}

Petugas Pengirim: {{sender_name}}
Penerima: {{recipient_signature}}`
  );
  const [isActive, setIsActive] = useState<boolean>(initialData?.is_active ?? true);

  const createTemplate = useCreateDocumentTemplate();
  const updateTemplate = useUpdateDocumentTemplate();
  const isSaving = createTemplate.isPending || updateTemplate.isPending;
  const currentError = isEdit ? updateTemplate.error : createTemplate.error;
  const apiError = currentError instanceof ApiError ? currentError : null;

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!code.trim() || !name.trim() || !templateBody.trim()) {
      notify('Kode, nama, dan isi template surat wajib diisi.', 'error');
      return;
    }

    try {
      if (isEdit && initialData) {
        await updateTemplate.mutateAsync({
          id: initialData.id,
          payload: {
            name: name.trim(),
            document_type: documentType,
            template_body: templateBody,
            is_active: isActive,
          },
        });
        notify(`Template surat ${code} berhasil diperbarui.`, 'success');
      } else {
        await createTemplate.mutateAsync({
          code: code.trim().toUpperCase(),
          name: name.trim(),
          document_type: documentType,
          template_body: templateBody,
          is_active: isActive,
        });
        notify(`Template surat ${code} berhasil dibuat.`, 'success');
      }
      onSuccess?.();
      onClose();
    } catch (err) {
      if (!(err instanceof ApiError && err.isValidation)) {
        notify(err instanceof Error ? err.message : 'Gagal menyimpan template surat.', 'error');
      }
    }
  };

  if (isPreview && initialData) {
    return (
      <Modal
        open
        title={`Preview Template: ${initialData.name} (${initialData.code})`}
        description={`Tipe Dokumen: ${initialData.document_type} · Versi: v${initialData.version}`}
        onClose={onClose}
        footer={<Button onClick={onClose}>Tutup</Button>}
      >
        <div
          style={{
            fontFamily: 'monospace',
            backgroundColor: 'var(--color-bg-subtle, #f8fafc)',
            padding: 16,
            borderRadius: 6,
            border: '1px solid var(--color-border, #cbd5e1)',
            whiteSpace: 'pre-wrap',
            fontSize: '0.875rem',
            lineHeight: 1.6,
          }}
        >
          {initialData.template_body}
        </div>
      </Modal>
    );
  }

  return (
    <Modal
      open
      title={isEdit ? `Edit Template: ${initialData?.code}` : 'Tambah Template Surat'}
      description="Konfigurasi template format cetak untuk Surat Jalan, BAST, SPK, dan Berita Acara."
      onClose={onClose}
      footer={
        <>
          <Button onClick={onClose} disabled={isSaving}>
            Batal
          </Button>
          <Button variant="primary" type="submit" form="template-form" loading={isSaving}>
            {isEdit ? 'Simpan Perubahan' : 'Buat Template'}
          </Button>
        </>
      }
    >
      <form id="template-form" onSubmit={handleSubmit} noValidate>
        <div className="grid grid--2">
          <FormField label="Kode Template" htmlFor="tpl-code" required error={apiError?.fieldError('code')}>
            <input
              id="tpl-code"
              className="input"
              value={code}
              placeholder="Contoh: TPL-SJ-01"
              disabled={isEdit}
              onChange={(e) => setCode(e.target.value.toUpperCase())}
            />
          </FormField>

          <FormField label="Jenis Dokumen" htmlFor="tpl-type" required error={apiError?.fieldError('document_type')}>
            <select
              id="tpl-type"
              className="select"
              value={documentType}
              onChange={(e) => setDocumentType(e.target.value)}
            >
              <option value="SURAT_JALAN">Surat Jalan (Delivery Order)</option>
              <option value="BAST">BAST (Berita Acara Serah Terima)</option>
              <option value="SPK">SPK (Surat Perintah Kerja Produksi)</option>
              <option value="INVOICE">Kwitansi / Invoice</option>
              <option value="OTHER">Dokumen Lainnya</option>
            </select>
          </FormField>
        </div>

        <FormField label="Nama Template" htmlFor="tpl-name" required error={apiError?.fieldError('name')}>
          <input
            id="tpl-name"
            className="input"
            value={name}
            placeholder="Contoh: Template Standar Surat Jalan Distribusi"
            onChange={(e) => setName(e.target.value)}
          />
        </FormField>

        <FormField
          label="Isi / Layout Template Surat"
          htmlFor="tpl-body"
          description="Gunakan placeholder: {{doc_no}}, {{doc_date}}, {{recipient_name}}, {{recipient_address}}, {{items_table}}, {{notes}}"
          required
          error={apiError?.fieldError('template_body')}
        >
          <textarea
            id="tpl-body"
            className="input"
            rows={10}
            style={{ fontFamily: 'monospace', fontSize: '0.85rem' }}
            value={templateBody}
            onChange={(e) => setTemplateBody(e.target.value)}
          />
        </FormField>

        <FormField label="Status Template" htmlFor="tpl-status">
          <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer', marginTop: 4 }}>
            <input
              id="tpl-status"
              type="checkbox"
              checked={isActive}
              onChange={(e) => setIsActive(e.target.checked)}
            />
            <span>Template Aktif & Siap Digunakan dalam Cetak Dokumen</span>
          </label>
        </FormField>
      </form>
    </Modal>
  );
}
