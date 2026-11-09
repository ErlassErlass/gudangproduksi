import { useState, useMemo } from 'react';
import {
  Edit2,
  Plus,
  RefreshCw,
  Trash2,
  Users,
} from 'lucide-react';
import { Button } from '@/components/Button';
import { ConfirmationDialog } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { SearchBar } from '@/components/SearchBar';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import {
  useCustomers,
  useDeleteCustomer,
  type CustomerItem,
} from './api';
import { CustomerFormModal } from './CustomerFormModal';

export function CustomersPage() {
  const { can } = useAuth();
  const canManage = can('master_data.manage');
  const { notify } = useToast();

  const customersQuery = useCustomers();
  const deleteCustomer = useDeleteCustomer();

  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState<string>('ALL');
  const [modalState, setModalState] = useState<{ open: boolean; data?: CustomerItem | null }>({
    open: false,
    data: null,
  });
  const [deletingCustomer, setDeletingCustomer] = useState<CustomerItem | null>(null);

  const filteredCustomers = useMemo(() => {
    return (customersQuery.data ?? []).filter((c) => {
      if (typeFilter !== 'ALL' && c.customer_type !== typeFilter) return false;
      if (search.trim()) {
        const q = search.toLowerCase();
        const codeMatch = c.code.toLowerCase().includes(q);
        const nameMatch = c.name.toLowerCase().includes(q);
        const salesMatch = c.sales_name?.toLowerCase().includes(q) ?? false;
        const schoolMatch = c.school_name?.toLowerCase().includes(q) ?? false;
        const picMatch = c.contact_name?.toLowerCase().includes(q) ?? false;
        return codeMatch || nameMatch || salesMatch || schoolMatch || picMatch;
      }
      return true;
    });
  }, [customersQuery.data, typeFilter, search]);

  const handleDelete = async () => {
    if (!deletingCustomer) return;
    try {
      await deleteCustomer.mutateAsync(deletingCustomer.id);
      notify(`Customer ${deletingCustomer.code} berhasil dihapus.`, 'success');
      setDeletingCustomer(null);
    } catch (err: any) {
      notify(err.message ?? 'Gagal menghapus customer.', 'error');
    }
  };

  const getTypeBadge = (type?: string) => {
    switch (type) {
      case 'SCHOOL':
        return <span className="chip" style={{ background: '#dbeafe', color: '#1d4ed8' }}>Sekolah</span>;
      case 'ORGANIZATION':
        return <span className="chip" style={{ background: '#f3e8ff', color: '#7e22ce' }}>Organisasi</span>;
      default:
        return <span className="chip">Pribadi</span>;
    }
  };

  return (
    <>
      <PageHeader
        eyebrow="Master Data"
        title="Manajemen Customer"
        description="Kelola data pelanggan, sekolah mitra, yayasan pendidikan, dan instansi pemesan atau penyewa peralatan."
        actions={
          <div style={{ display: 'flex', gap: 8 }}>
            <Button icon={<RefreshCw size={15} />} onClick={() => customersQuery.refetch()}>
              Muat Ulang
            </Button>
            {canManage && (
              <Button
                variant="primary"
                icon={<Plus size={15} />}
                onClick={() => setModalState({ open: true, data: null })}
              >
                Tambah Customer
              </Button>
            )}
          </div>
        }
      />

      <section className="card">
        <div className="toolbar" style={{ marginBottom: 16 }}>
          <SearchBar value={search} onChange={setSearch} placeholder="Cari kode, nama customer, sekolah, PIC..." />
          <select
            className="select"
            style={{ width: 220 }}
            value={typeFilter}
            aria-label="Filter tipe pelanggan"
            onChange={(e) => setTypeFilter(e.target.value)}
          >
            <option value="ALL">Semua Tipe Pelanggan</option>
            <option value="SCHOOL">Sekolah (Education)</option>
            <option value="ORGANIZATION">Organisasi / Perusahaan</option>
            <option value="INDIVIDUAL">Pribadi / Perorangan</option>
          </select>
        </div>

        {customersQuery.isLoading ? (
          <LoadingState label="Memuat data customer..." />
        ) : filteredCustomers.length === 0 ? (
          <EmptyState
            icon={<Users size={36} />}
            title="Tidak ada data customer"
            description="Belum ada customer terdaftar atau sesuai kriteria pencarian."
            action={
              canManage ? (
                <Button
                  variant="primary"
                  icon={<Plus size={15} />}
                  onClick={() => setModalState({ open: true, data: null })}
                >
                  Tambah Customer
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
                  <th style={{ width: '140px' }}>Tipe</th>
                  <th>Nama</th>
                  <th>Sales</th>
                  {canManage && <th style={{ width: '90px', textAlign: 'center' }}>Aksi</th>}
                </tr>
              </thead>
              <tbody>
                {filteredCustomers.map((c) => (
                  <tr key={c.id}>
                    <td>
                      <code>{c.code}</code>
                    </td>
                    <td>{getTypeBadge(c.customer_type)}</td>
                    <td>
                      <div style={{ fontWeight: 600, color: 'var(--color-text-primary, #34466f)' }}>{c.name}</div>
                      {c.school_name && c.school_name !== c.name && (
                        <div className="table__muted" style={{ fontSize: '0.75rem' }}>{c.school_name}</div>
                      )}
                    </td>
                    <td>
                      {c.sales_name ? (
                        <div style={{ fontWeight: 500, color: 'var(--color-primary-dark, #263f7d)' }}>
                          {c.sales_name}
                        </div>
                      ) : (
                        <span className="table__muted">—</span>
                      )}
                    </td>
                    {canManage && (
                      <td style={{ textAlign: 'center' }}>
                        <div style={{ display: 'inline-flex', gap: 4, alignItems: 'center' }}>
                          <Button
                            size="sm"
                            variant="ghost"
                            iconOnly
                            icon={<Edit2 size={15} />}
                            onClick={() => setModalState({ open: true, data: c })}
                            aria-label={`Ubah data ${c.name}`}
                          />
                          <Button
                            size="sm"
                            variant="ghost"
                            iconOnly
                            icon={<Trash2 size={15} color="var(--color-danger, #d96a36)" />}
                            onClick={() => setDeletingCustomer(c)}
                            aria-label={`Hapus ${c.name}`}
                          />
                        </div>
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      {/* Modal Form Customer */}
      {modalState.open && (
        <CustomerFormModal
          initialData={modalState.data}
          onClose={() => setModalState({ open: false, data: null })}
          onSuccess={() => customersQuery.refetch()}
        />
      )}

      {/* Dialog Konfirmasi Hapus */}
      <ConfirmationDialog
        open={Boolean(deletingCustomer)}
        title="Hapus Data Customer"
        message={`Apakah Anda yakin ingin menghapus data customer "${deletingCustomer?.name}" (${deletingCustomer?.code})?`}
        confirmLabel="Hapus Customer"
        danger
        loading={deleteCustomer.isPending}
        onConfirm={handleDelete}
        onCancel={() => setDeletingCustomer(null)}
      />
    </>
  );
}
