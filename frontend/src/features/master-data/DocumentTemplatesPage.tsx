import { useState, useMemo } from 'react';
import {
  Edit2,
  Eye,
  FileText,
  Plus,
  RefreshCw,
  Trash2,
} from 'lucide-react';
import { Button } from '@/components/Button';
import { ConfirmationDialog } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { SearchBar } from '@/components/SearchBar';
import { StatusBadge } from '@/components/StatusBadge';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import {
  useDeleteDocumentTemplate,
  useDocumentTemplates,
  type DocumentTemplateItem,
} from './api';
import { DocumentTemplateModal } from './DocumentTemplateModal';

export function DocumentTemplatesPage() {
  const { can } = useAuth();
  const canManage = can('master_data.manage');
  const { notify } = useToast();

  const templatesQuery = useDocumentTemplates();
  const deleteTemplate = useDeleteDocumentTemplate();

  const [search, setSearch] = useState('');
  const [modalState, setModalState] = useState<{
    open: boolean;
    mode: 'edit' | 'preview';
    data?: DocumentTemplateItem | null;
  }>({ open: false, mode: 'preview' });

  const [deletingTemplate, setDeletingTemplate] = useState<DocumentTemplateItem | null>(null);

  const filteredTemplates = useMemo(() => {
    return (templatesQuery.data ?? []).filter((tpl) => {
      if (search.trim()) {
        const q = search.toLowerCase();
        return (
          tpl.code.toLowerCase().includes(q) ||
          tpl.name.toLowerCase().includes(q) ||
          tpl.document_type.toLowerCase().includes(q)
        );
      }
      return true;
    });
  }, [templatesQuery.data, search]);

  const handleDelete = async () => {
    if (!deletingTemplate) return;
    try {
      await deleteTemplate.mutateAsync(deletingTemplate.id);
      notify(`Template surat ${deletingTemplate.code} berhasil dihapus.`, 'success');
      setDeletingTemplate(null);
    } catch (err: any) {
      notify(err.message ?? 'Gagal menghapus template surat.', 'error');
    }
  };

  const getDocTypeBadge = (type: string) => {
    switch (type) {
      case 'SURAT_JALAN':
        return <span className="chip" style={{ background: '#e0e7ff', color: '#4338ca' }}>Surat Jalan</span>;
      case 'BAST':
        return <span className="chip" style={{ background: '#fef3c7', color: '#b45309' }}>BAST</span>;
      case 'SPK':
        return <span className="chip" style={{ background: '#dcfce7', color: '#15803d' }}>SPK Produksi</span>;
      case 'INVOICE':
        return <span className="chip" style={{ background: '#fce7f3', color: '#be185d' }}>Kwitansi / Invoice</span>;
      default:
        return <span className="chip">{type}</span>;
    }
  };

  return (
    <>
      <PageHeader
        eyebrow="Master Data"
        title="Template Surat Resmi"
        description="Kelola template format cetak dokumen operasional seperti Surat Jalan (Delivery Order), Berita Acara Serah Terima (BAST), dan SPK."
        actions={
          <div style={{ display: 'flex', gap: 8 }}>
            <Button icon={<RefreshCw size={15} />} onClick={() => templatesQuery.refetch()}>
              Muat Ulang
            </Button>
            {canManage && (
              <Button
                variant="primary"
                icon={<Plus size={15} />}
                onClick={() => setModalState({ open: true, mode: 'edit', data: null })}
              >
                Tambah Template
              </Button>
            )}
          </div>
        }
      />

      <section className="card">
        <div className="toolbar" style={{ marginBottom: 16 }}>
          <SearchBar value={search} onChange={setSearch} placeholder="Cari kode, nama template surat, atau jenis dokumen..." />
        </div>

        {templatesQuery.isLoading ? (
          <LoadingState label="Memuat data template surat..." />
        ) : filteredTemplates.length === 0 ? (
          <EmptyState
            icon={<FileText size={36} />}
            title="Tidak ada template surat"
            description="Belum ada template surat yang dibuat atau sesuai pencarian."
            action={
              canManage ? (
                <Button
                  variant="primary"
                  icon={<Plus size={15} />}
                  onClick={() => setModalState({ open: true, mode: 'edit', data: null })}
                >
                  Tambah Template
                </Button>
              ) : undefined
            }
          />
        ) : (
          <div className="table-wrap">
            <table className="table">
              <thead>
                <tr>
                  <th style={{ width: '160px' }}>Kode</th>
                  <th>Nama Template</th>
                  <th style={{ width: '160px' }}>Jenis Dokumen</th>
                  <th style={{ width: '90px' }}>Versi</th>
                  <th style={{ width: '110px' }}>Status</th>
                  <th style={{ width: '120px', textAlign: 'center' }}>Aksi</th>
                </tr>
              </thead>
              <tbody>
                {filteredTemplates.map((tpl) => (
                  <tr key={tpl.id}>
                    <td>
                      <code>{tpl.code}</code>
                    </td>
                    <td>
                      <div style={{ fontWeight: 600, color: 'var(--color-text-primary, #34466f)' }}>{tpl.name}</div>
                    </td>
                    <td>{getDocTypeBadge(tpl.document_type)}</td>
                    <td>
                      <span className="chip">v{tpl.version}</span>
                    </td>
                    <td>
                      <StatusBadge status={tpl.is_active ? 'ACTIVE' : 'INACTIVE'} />
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      <div style={{ display: 'inline-flex', gap: 4, alignItems: 'center' }}>
                        <Button
                          size="sm"
                          variant="ghost"
                          iconOnly
                          icon={<Eye size={15} />}
                          onClick={() => setModalState({ open: true, mode: 'preview', data: tpl })}
                          aria-label={`Preview template ${tpl.name}`}
                        />
                        {canManage && (
                          <>
                            <Button
                              size="sm"
                              variant="ghost"
                              iconOnly
                              icon={<Edit2 size={15} />}
                              onClick={() => setModalState({ open: true, mode: 'edit', data: tpl })}
                              aria-label={`Edit template ${tpl.name}`}
                            />
                            <Button
                              size="sm"
                              variant="ghost"
                              iconOnly
                              icon={<Trash2 size={15} color="var(--color-danger, #d96a36)" />}
                              onClick={() => setDeletingTemplate(tpl)}
                              aria-label={`Hapus template ${tpl.name}`}
                            />
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {/* Modal Preview / Edit Template */}
      {modalState.open && (
        <DocumentTemplateModal
          initialData={modalState.data}
          mode={modalState.mode}
          onClose={() => setModalState({ open: false, mode: 'preview' })}
          onSuccess={() => templatesQuery.refetch()}
        />
      )}

      {/* Dialog Konfirmasi Hapus */}
      <ConfirmationDialog
        open={Boolean(deletingTemplate)}
        title="Hapus Template Dokumen"
        message={`Apakah Anda yakin ingin menghapus template "${deletingTemplate?.name}" (${deletingTemplate?.code})?`}
        confirmLabel="Hapus Template"
        danger
        loading={deleteTemplate.isPending}
        onConfirm={handleDelete}
        onCancel={() => setDeletingTemplate(null)}
      />
    </>
  );
}
