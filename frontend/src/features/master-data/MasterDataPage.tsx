import { useState, useMemo } from 'react';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import {
  Building2,
  Edit2,
  Eye,
  FileText,
  KeyRound,
  Layers,
  MapPin,
  Plus,
  RefreshCw,
  Trash2,
  Users,
  Warehouse,
} from 'lucide-react';
import { Button } from '@/components/Button';
import { ConfirmationDialog } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { SearchBar } from '@/components/SearchBar';
import { StatusBadge } from '@/components/StatusBadge';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { UsersPage } from '@/features/administration/UsersPage';
import { useAuth } from '@/features/auth/AuthContext';
import {
  useCustomers,
  useDeleteCustomer,
  useDeleteLocation,
  useDeleteWarehouse,
  useDocumentTemplates,
  useDeleteDocumentTemplate,
  useLocations,
  useWarehouses,
  type CustomerItem,
  type DocumentTemplateItem,
  type LocationItem,
  type WarehouseItem,
} from './api';
import { CustomerFormModal } from './CustomerFormModal';
import { DocumentTemplateModal } from './DocumentTemplateModal';
import { LocationFormModal } from './LocationFormModal';
import { WarehouseFormModal } from './WarehouseFormModal';

export type MasterDataTab = 'locations' | 'customers' | 'templates' | 'accounts';

interface MasterDataPageProps {
  initialTab?: MasterDataTab;
}

export function MasterDataPage({ initialTab }: MasterDataPageProps) {
  const { can } = useAuth();
  const canManage = can('master_data.manage');
  const location = useLocation();
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();

  // Determine active tab from URL path, search params, or initialTab prop
  const currentTab: MasterDataTab = useMemo(() => {
    if (initialTab) return initialTab;
    if (location.pathname.includes('/locations')) return 'locations';
    if (location.pathname.includes('/customers')) return 'customers';
    if (location.pathname.includes('/templates')) return 'templates';
    if (location.pathname.includes('/accounts')) return 'accounts';
    const tabParam = searchParams.get('tab') as MasterDataTab;
    if (['locations', 'customers', 'templates', 'accounts'].includes(tabParam)) {
      return tabParam;
    }
    return 'locations';
  }, [initialTab, location.pathname, searchParams]);

  const handleTabChange = (tab: MasterDataTab) => {
    // If we're on a direct sub-path like /master-data/customers, navigate directly
    if (location.pathname.startsWith('/master-data/')) {
      navigate(`/master-data/${tab}`);
    } else {
      setSearchParams({ tab });
    }
  };

  return (
    <>
      <PageHeader
        eyebrow="Master Data"
        title="Master Data Operasional & Organisasi"
        description="Kelola data lokasi berjenjang (Gedung, Lantai, Ruangan, Rak), data pelanggan/sekolah, template dokumen resmi, dan akun pengguna."
      />

      {/* Tab Navigation */}
      <div style={{ display: 'flex', gap: 8, marginBottom: 20, flexWrap: 'wrap' }}>
        <Button
          variant={currentTab === 'locations' ? 'primary' : 'secondary'}
          icon={<Warehouse size={16} />}
          onClick={() => handleTabChange('locations')}
        >
          Data Lokasi (Gedung, Lantai, Ruang, Rak)
        </Button>
        <Button
          variant={currentTab === 'customers' ? 'primary' : 'secondary'}
          icon={<Users size={16} />}
          onClick={() => handleTabChange('customers')}
        >
          Manajemen Customer
        </Button>
        <Button
          variant={currentTab === 'templates' ? 'primary' : 'secondary'}
          icon={<FileText size={16} />}
          onClick={() => handleTabChange('templates')}
        >
          Template Surat
        </Button>
        <Button
          variant={currentTab === 'accounts' ? 'primary' : 'secondary'}
          icon={<KeyRound size={16} />}
          onClick={() => handleTabChange('accounts')}
        >
          Akun Pengguna
        </Button>
      </div>

      {currentTab === 'locations' && <LocationsTab canManage={canManage} />}
      {currentTab === 'customers' && <CustomersTab canManage={canManage} />}
      {currentTab === 'templates' && <DocumentTemplatesTab canManage={canManage} />}
      {currentTab === 'accounts' && <AccountsTab />}
    </>
  );
}

// ==========================================
// 1. DATA LOKASI (Gedung, Lantai, Ruang, Rak)
// ==========================================
function LocationsTab({ canManage }: { canManage: boolean }) {
  const { notify } = useToast();
  const warehousesQuery = useWarehouses();
  const locationsQuery = useLocations();

  const [selectedWarehouseId, setSelectedWarehouseId] = useState<number | 'ALL'>('ALL');
  const [selectedType, setSelectedType] = useState<string>('ALL');
  const [search, setSearch] = useState('');

  // Modals state
  const [locationModal, setLocationModal] = useState<{
    open: boolean;
    data?: LocationItem | null;
    defaultWarehouseId?: number;
    defaultParentId?: number | null;
    defaultType?: string;
  }>({ open: false });

  const [warehouseModal, setWarehouseModal] = useState<{
    open: boolean;
    data?: WarehouseItem | null;
  }>({ open: false });

  const [deletingLocation, setDeletingLocation] = useState<LocationItem | null>(null);
  const [deletingWarehouse, setDeletingWarehouse] = useState<WarehouseItem | null>(null);

  const deleteLocation = useDeleteLocation();
  const deleteWarehouse = useDeleteWarehouse();

  // Filter locations
  const filteredLocations = useMemo(() => {
    return (locationsQuery.data ?? []).filter((loc) => {
      if (selectedWarehouseId !== 'ALL' && loc.warehouse_id !== selectedWarehouseId) {
        return false;
      }
      if (selectedType !== 'ALL' && loc.location_type !== selectedType) {
        return false;
      }
      if (search.trim()) {
        const q = search.toLowerCase();
        const codeMatch = loc.code.toLowerCase().includes(q);
        const nameMatch = loc.name.toLowerCase().includes(q);
        const barcodeMatch = loc.barcode?.toLowerCase().includes(q) ?? false;
        return codeMatch || nameMatch || barcodeMatch;
      }
      return true;
    });
  }, [locationsQuery.data, selectedWarehouseId, selectedType, search]);

  const handleDeleteLocation = async () => {
    if (!deletingLocation) return;
    try {
      await deleteLocation.mutateAsync(deletingLocation.id);
      notify(`Lokasi ${deletingLocation.code} berhasil dihapus.`, 'success');
      setDeletingLocation(null);
    } catch (err: any) {
      notify(err.message ?? 'Gagal menghapus lokasi. Pastikan tidak ada sub-lokasi atau saldo stok.', 'error');
    }
  };

  const handleDeleteWarehouse = async () => {
    if (!deletingWarehouse) return;
    try {
      await deleteWarehouse.mutateAsync(deletingWarehouse.id);
      notify(`Gedung ${deletingWarehouse.code} berhasil dihapus.`, 'success');
      setDeletingWarehouse(null);
    } catch (err: any) {
      notify(err.message ?? 'Gagal menghapus gedung. Pastikan gedung tidak memiliki data lokasi.', 'error');
    }
  };

  const getLocationTypeBadge = (type: string) => {
    switch (type) {
      case 'BUILDING':
        return <span className="chip" style={{ background: '#f3e8ff', color: '#6b21a8' }}>Gedung</span>;
      case 'FLOOR':
        return <span className="chip" style={{ background: '#e0f2fe', color: '#0369a1' }}>Lantai</span>;
      case 'ROOM':
        return <span className="chip" style={{ background: '#dcfce7', color: '#15803d' }}>Ruangan</span>;
      case 'RACK':
        return <span className="chip" style={{ background: '#fef3c7', color: '#b45309' }}>Rak</span>;
      case 'BIN':
        return <span className="chip" style={{ background: '#fce7f3', color: '#be185d' }}>Bin / Kompartemen</span>;
      default:
        return <span className="chip">{type}</span>;
    }
  };

  return (
    <section className="card">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16, flexWrap: 'wrap', gap: 12 }}>
        <div>
          <h3 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 600 }}>Struktur Lokasi Fisik Gudang</h3>
          <p className="table__muted" style={{ margin: '4px 0 0 0', fontSize: '0.875rem' }}>
            Tingkatan hierarkis dinamis: <strong>Gedung &rarr; Lantai &rarr; Ruangan &rarr; Rak / Bin</strong>
          </p>
        </div>
        <div style={{ display: 'flex', gap: 8 }}>
          <Button icon={<RefreshCw size={15} />} onClick={() => { locationsQuery.refetch(); warehousesQuery.refetch(); }}>
            Muat Ulang
          </Button>
          {canManage && (
            <>
              <Button
                variant="secondary"
                icon={<Building2 size={15} />}
                onClick={() => setWarehouseModal({ open: true, data: null })}
              >
                + Tambah Gedung
              </Button>
              <Button
                variant="primary"
                icon={<Plus size={15} />}
                onClick={() => setLocationModal({ open: true, data: null })}
              >
                + Tambah Lokasi
              </Button>
            </>
          )}
        </div>
      </div>

      {/* Filter toolbar */}
      <div className="toolbar" style={{ marginBottom: 16 }}>
        <SearchBar value={search} onChange={setSearch} placeholder="Cari kode lokasi, barcode, atau nama..." />
        
        <select
          className="select"
          style={{ width: 220 }}
          value={selectedWarehouseId}
          aria-label="Filter gedung"
          onChange={(e) => setSelectedWarehouseId(e.target.value === 'ALL' ? 'ALL' : Number(e.target.value))}
        >
          <option value="ALL">Semua Gedung / Warehouse</option>
          {warehousesQuery.data?.map((wh) => (
            <option key={wh.id} value={wh.id}>
              {wh.code} — {wh.name}
            </option>
          ))}
        </select>

        <select
          className="select"
          style={{ width: 180 }}
          value={selectedType}
          aria-label="Filter tingkat lokasi"
          onChange={(e) => setSelectedType(e.target.value)}
        >
          <option value="ALL">Semua Tingkatan</option>
          <option value="FLOOR">Lantai (Floor)</option>
          <option value="ROOM">Ruangan (Room)</option>
          <option value="RACK">Rak (Rack)</option>
          <option value="BIN">Bin / Kompartemen</option>
          <option value="BUILDING">Gedung (Building)</option>
        </select>
      </div>

      {/* Daftar Gedung Accordion/Summary Card jika sedang pilih gedung */}
      {warehousesQuery.data && warehousesQuery.data.length > 0 && (
        <div style={{ background: 'var(--color-bg-subtle, #f8fafc)', padding: '12px 16px', borderRadius: 6, marginBottom: 16, border: '1px solid var(--color-border, #e2e8f0)' }}>
          <div style={{ fontSize: '0.8rem', fontWeight: 600, color: 'var(--color-text-muted, #64748b)', textTransform: 'uppercase', marginBottom: 8 }}>
            Daftar Gedung Terdaftar:
          </div>
          <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
            {warehousesQuery.data.map((wh) => (
              <div
                key={wh.id}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 8,
                  padding: '6px 12px',
                  background: '#fff',
                  borderRadius: 6,
                  border: '1px solid #cbd5e1',
                  fontSize: '0.85rem',
                }}
              >
                <Building2 size={16} color="#475569" />
                <div>
                  <strong>{wh.code}</strong> — {wh.name}
                  {wh.address && <span className="table__muted" style={{ marginLeft: 6 }}>({wh.address})</span>}
                </div>
                {canManage && (
                  <div style={{ display: 'flex', gap: 4, marginLeft: 8 }}>
                    <Button
                      size="sm"
                      variant="ghost"
                      icon={<Edit2 size={13} />}
                      onClick={() => setWarehouseModal({ open: true, data: wh })}
                      aria-label="Edit Gedung"
                    />
                    <Button
                      size="sm"
                      variant="ghost"
                      icon={<Trash2 size={13} color="#ef4444" />}
                      onClick={() => setDeletingWarehouse(wh)}
                      aria-label="Hapus Gedung"
                    />
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Tabel Lokasi */}
      {locationsQuery.isLoading ? (
        <LoadingState message="Memuat hierarki data lokasi..." />
      ) : filteredLocations.length === 0 ? (
        <EmptyState
          icon={<MapPin size={36} />}
          title="Tidak ada data lokasi"
          message="Belum ada data lokasi yang sesuai dengan filter. Tambahkan lokasi baru sekarang."
          action={
            canManage ? (
              <Button variant="primary" icon={<Plus size={16} />} onClick={() => setLocationModal({ open: true, data: null })}>
                Tambah Lokasi
              </Button>
            ) : undefined
          }
        />
      ) : (
        <div className="table-wrap">
          <table className="table">
            <thead>
              <tr>
                <th style={{ width: '180px' }}>Kode & Barcode</th>
                <th>Nama Lokasi</th>
                <th style={{ width: '130px' }}>Tingkat</th>
                <th style={{ width: '160px' }}>Gedung</th>
                <th>Induk (Parent)</th>
                <th style={{ width: '90px' }}>Status</th>
                {canManage && <th style={{ width: '190px', textAlign: 'center' }}>Aksi</th>}
              </tr>
            </thead>
            <tbody>
              {filteredLocations.map((loc) => {
                const nextType =
                  loc.location_type === 'BUILDING'
                    ? 'FLOOR'
                    : loc.location_type === 'FLOOR'
                    ? 'ROOM'
                    : loc.location_type === 'ROOM'
                    ? 'RACK'
                    : 'BIN';

                return (
                  <tr key={loc.id}>
                    <td>
                      <div>
                        <code>{loc.code}</code>
                        {loc.barcode && <div className="table__muted" style={{ fontSize: '0.75rem' }}>Barcode: {loc.barcode}</div>}
                      </div>
                    </td>
                    <td>
                      <div style={{ fontWeight: 500 }}>{loc.name}</div>
                    </td>
                    <td>{getLocationTypeBadge(loc.location_type)}</td>
                    <td>
                      <span className="table__muted">{loc.warehouse?.name ?? loc.warehouse?.code ?? '—'}</span>
                    </td>
                    <td>
                      {loc.parent ? (
                        <span>
                          <code>{loc.parent.code}</code> <span className="table__muted">({loc.parent.name})</span>
                        </span>
                      ) : (
                        <span className="table__muted">—</span>
                      )}
                    </td>
                    <td>
                      <StatusBadge status={loc.is_active !== false ? 'ACTIVE' : 'INACTIVE'} />
                    </td>
                    {canManage && (
                      <td style={{ textAlign: 'center' }}>
                        <div style={{ display: 'inline-flex', gap: 6, alignItems: 'center' }}>
                          <Button
                            size="sm"
                            variant="secondary"
                            icon={<Layers size={13} />}
                            onClick={() =>
                              setLocationModal({
                                open: true,
                                data: null,
                                defaultWarehouseId: loc.warehouse_id,
                                defaultParentId: loc.id,
                                defaultType: nextType,
                              })
                            }
                            title={`Tambah sub-lokasi di bawah ${loc.code}`}
                          >
                            + Sub
                          </Button>
                          <Button
                            size="sm"
                            variant="secondary"
                            icon={<Edit2 size={13} />}
                            onClick={() => setLocationModal({ open: true, data: loc })}
                            title="Edit lokasi ini"
                          >
                            Edit
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            icon={<Trash2 size={13} color="#ef4444" />}
                            onClick={() => setDeletingLocation(loc)}
                            title="Hapus lokasi"
                          />
                        </div>
                      </td>
                    )}
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      {/* Modal Tambah/Edit Lokasi */}
      {locationModal.open && (
        <LocationFormModal
          initialData={locationModal.data}
          defaultWarehouseId={locationModal.defaultWarehouseId}
          defaultParentId={locationModal.defaultParentId}
          defaultType={locationModal.defaultType}
          onClose={() => setLocationModal({ open: false })}
          onSuccess={() => {
            locationsQuery.refetch();
          }}
        />
      )}

      {/* Modal Tambah/Edit Gedung */}
      {warehouseModal.open && (
        <WarehouseFormModal
          initialData={warehouseModal.data}
          onClose={() => setWarehouseModal({ open: false })}
          onSuccess={() => {
            warehousesQuery.refetch();
            locationsQuery.refetch();
          }}
        />
      )}

      {/* Konfirmasi Hapus Lokasi */}
      <ConfirmationDialog
        isOpen={Boolean(deletingLocation)}
        title="Hapus Lokasi"
        description={`Apakah Anda yakin ingin menghapus lokasi "${deletingLocation?.code} - ${deletingLocation?.name}"? Lokasi hanya dapat dihapus jika tidak memiliki anak/sub-lokasi dan tidak ada saldo fisik on-hand.`}
        confirmText="Hapus Lokasi"
        confirmVariant="danger"
        loading={deleteLocation.isPending}
        onConfirm={handleDeleteLocation}
        onCancel={() => setDeletingLocation(null)}
      />

      {/* Konfirmasi Hapus Gedung */}
      <ConfirmationDialog
        isOpen={Boolean(deletingWarehouse)}
        title="Hapus Gedung / Warehouse"
        description={`Apakah Anda yakin ingin menghapus gedung "${deletingWarehouse?.code} - ${deletingWarehouse?.name}"? Gedung hanya dapat dihapus jika tidak memiliki data lantai/ruangan/rak di dalamnya.`}
        confirmText="Hapus Gedung"
        confirmVariant="danger"
        loading={deleteWarehouse.isPending}
        onConfirm={handleDeleteWarehouse}
        onCancel={() => setDeletingWarehouse(null)}
      />
    </section>
  );
}

// ==========================================
// 2. MANAJEMEN CUSTOMER
// ==========================================
function CustomersTab({ canManage }: { canManage: boolean }) {
  const { notify } = useToast();
  const customersQuery = useCustomers();
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState<string>('ALL');

  const [formModal, setFormModal] = useState<{ open: boolean; data?: CustomerItem | null }>({ open: false });
  const [deletingCustomer, setDeletingCustomer] = useState<CustomerItem | null>(null);
  const deleteCustomer = useDeleteCustomer();

  const filteredCustomers = useMemo(() => {
    return (customersQuery.data ?? []).filter((c) => {
      if (typeFilter !== 'ALL' && c.customer_type !== typeFilter) return false;
      if (search.trim()) {
        const q = search.toLowerCase();
        const codeMatch = c.code.toLowerCase().includes(q);
        const nameMatch = c.name.toLowerCase().includes(q);
        const schoolMatch = c.school_name?.toLowerCase().includes(q) ?? false;
        const picMatch = c.contact_name?.toLowerCase().includes(q) ?? false;
        return codeMatch || nameMatch || schoolMatch || picMatch;
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
    <section className="card">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16, flexWrap: 'wrap', gap: 12 }}>
        <div>
          <h3 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 600 }}>Daftar Customer / Sekolah Mitra</h3>
          <p className="table__muted" style={{ margin: '4px 0 0 0', fontSize: '0.875rem' }}>
            Data pelanggan, yayasan sekolah, atau organisasi penyewa dan pemesan program.
          </p>
        </div>
        <div style={{ display: 'flex', gap: 8 }}>
          <Button icon={<RefreshCw size={15} />} onClick={() => customersQuery.refetch()}>
            Muat Ulang
          </Button>
          {canManage && (
            <Button
              variant="primary"
              icon={<Plus size={15} />}
              onClick={() => setFormModal({ open: true, data: null })}
            >
              + Tambah Customer
            </Button>
          )}
        </div>
      </div>

      <div className="toolbar" style={{ marginBottom: 16 }}>
        <SearchBar value={search} onChange={setSearch} placeholder="Cari kode, nama customer, sekolah, PIC..." />
        <select
          className="select"
          style={{ width: 200 }}
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
        <LoadingState message="Memuat data customer..." />
      ) : filteredCustomers.length === 0 ? (
        <EmptyState
          icon={<Users size={36} />}
          title="Tidak ada data customer"
          message="Belum ada customer terdaftar atau sesuai kriteria pencarian."
          action={
            canManage ? (
              <Button variant="primary" icon={<Plus size={16} />} onClick={() => setFormModal({ open: true, data: null })}>
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
                <th style={{ width: '140px' }}>Kode</th>
                <th>Nama Customer / Lembaga</th>
                <th style={{ width: '130px' }}>Tipe</th>
                <th>Sekolah Resmi</th>
                <th>Kontak PIC</th>
                <th>Telepon & Email</th>
                {canManage && <th style={{ width: '130px', textAlign: 'center' }}>Aksi</th>}
              </tr>
            </thead>
            <tbody>
              {filteredCustomers.map((c) => (
                <tr key={c.id}>
                  <td>
                    <code>{c.code}</code>
                  </td>
                  <td>
                    <div style={{ fontWeight: 500 }}>{c.name}</div>
                  </td>
                  <td>{getTypeBadge(c.customer_type)}</td>
                  <td>{c.school_name ?? <span className="table__muted">—</span>}</td>
                  <td>{c.contact_name ?? <span className="table__muted">—</span>}</td>
                  <td>
                    <div>{c.phone ?? '—'}</div>
                    {c.email && <div className="table__muted" style={{ fontSize: '0.75rem' }}>{c.email}</div>}
                  </td>
                  {canManage && (
                    <td style={{ textAlign: 'center' }}>
                      <div style={{ display: 'inline-flex', gap: 6, alignItems: 'center' }}>
                        <Button
                          size="sm"
                          variant="secondary"
                          icon={<Edit2 size={13} />}
                          onClick={() => setFormModal({ open: true, data: c })}
                          title="Edit Customer"
                        >
                          Edit
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          icon={<Trash2 size={13} color="#ef4444" />}
                          onClick={() => setDeletingCustomer(c)}
                          title="Hapus Customer"
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

      {/* Modal Form Customer */}
      {formModal.open && (
        <CustomerFormModal
          initialData={formModal.data}
          onClose={() => setFormModal({ open: false })}
          onSuccess={() => customersQuery.refetch()}
        />
      )}

      {/* Dialog Hapus Customer */}
      <ConfirmationDialog
        isOpen={Boolean(deletingCustomer)}
        title="Hapus Data Customer"
        description={`Apakah Anda yakin ingin menghapus data customer "${deletingCustomer?.name}" (${deletingCustomer?.code})? Data customer yang memiliki riwayat rental aktif tidak dapat dihapus.`}
        confirmText="Hapus Customer"
        confirmVariant="danger"
        loading={deleteCustomer.isPending}
        onConfirm={handleDelete}
        onCancel={() => setDeletingCustomer(null)}
      />
    </section>
  );
}

// ==========================================
// 3. TEMPLATE SURAT
// ==========================================
function DocumentTemplatesTab({ canManage }: { canManage: boolean }) {
  const { notify } = useToast();
  const templatesQuery = useDocumentTemplates();
  const [search, setSearch] = useState('');
  const [modalState, setModalState] = useState<{
    open: boolean;
    mode: 'edit' | 'preview';
    data?: DocumentTemplateItem | null;
  }>({ open: false, mode: 'preview' });

  const [deletingTemplate, setDeletingTemplate] = useState<DocumentTemplateItem | null>(null);
  const deleteTemplate = useDeleteDocumentTemplate();

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
      notify(err.message ?? 'Gagal menghapus template.', 'error');
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
    <section className="card">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16, flexWrap: 'wrap', gap: 12 }}>
        <div>
          <h3 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 600 }}>Daftar Template Surat Resmi</h3>
          <p className="table__muted" style={{ margin: '4px 0 0 0', fontSize: '0.875rem' }}>
            Format template cetak dinamis untuk Surat Jalan, Berita Acara Serah Terima (BAST), dan SPK.
          </p>
        </div>
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
              + Tambah Template
            </Button>
          )}
        </div>
      </div>

      <div className="toolbar" style={{ marginBottom: 16 }}>
        <SearchBar value={search} onChange={setSearch} placeholder="Cari kode, nama template surat, atau tipe dokumen..." />
      </div>

      {templatesQuery.isLoading ? (
        <LoadingState message="Memuat template surat..." />
      ) : filteredTemplates.length === 0 ? (
        <EmptyState
          icon={<FileText size={36} />}
          title="Tidak ada template surat"
          message="Belum ada template surat yang dibuat."
          action={
            canManage ? (
              <Button
                variant="primary"
                icon={<Plus size={16} />}
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
                <th style={{ width: '150px' }}>Kode</th>
                <th>Nama Template</th>
                <th style={{ width: '150px' }}>Jenis Dokumen</th>
                <th style={{ width: '80px' }}>Versi</th>
                <th style={{ width: '100px' }}>Status</th>
                <th style={{ width: '180px', textAlign: 'center' }}>Aksi</th>
              </tr>
            </thead>
            <tbody>
              {filteredTemplates.map((tpl) => (
                <tr key={tpl.id}>
                  <td>
                    <code>{tpl.code}</code>
                  </td>
                  <td>
                    <div style={{ fontWeight: 500 }}>{tpl.name}</div>
                  </td>
                  <td>{getDocTypeBadge(tpl.document_type)}</td>
                  <td>
                    <span className="chip">v{tpl.version}</span>
                  </td>
                  <td>
                    <StatusBadge status={tpl.is_active ? 'ACTIVE' : 'INACTIVE'} />
                  </td>
                  <td style={{ textAlign: 'center' }}>
                    <div style={{ display: 'inline-flex', gap: 6, alignItems: 'center' }}>
                      <Button
                        size="sm"
                        variant="secondary"
                        icon={<Eye size={13} />}
                        onClick={() => setModalState({ open: true, mode: 'preview', data: tpl })}
                        title="Lihat isi template"
                      >
                        Preview
                      </Button>
                      {canManage && (
                        <>
                          <Button
                            size="sm"
                            variant="secondary"
                            icon={<Edit2 size={13} />}
                            onClick={() => setModalState({ open: true, mode: 'edit', data: tpl })}
                            title="Edit template surat"
                          >
                            Edit
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            icon={<Trash2 size={13} color="#ef4444" />}
                            onClick={() => setDeletingTemplate(tpl)}
                            title="Hapus template"
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

      {/* Modal Preview / Edit Template */}
      {modalState.open && (
        <DocumentTemplateModal
          initialData={modalState.data}
          mode={modalState.mode}
          onClose={() => setModalState({ open: false, mode: 'preview' })}
          onSuccess={() => templatesQuery.refetch()}
        />
      )}

      {/* Dialog Hapus Template */}
      <ConfirmationDialog
        isOpen={Boolean(deletingTemplate)}
        title="Hapus Template Dokumen"
        description={`Apakah Anda yakin ingin menghapus template "${deletingTemplate?.name}" (${deletingTemplate?.code})?`}
        confirmText="Hapus Template"
        confirmVariant="danger"
        loading={deleteTemplate.isPending}
        onConfirm={handleDelete}
        onCancel={() => setDeletingTemplate(null)}
      />
    </section>
  );
}

// ==========================================
// 4. AKUN PENGGUNA
// ==========================================
function AccountsTab() {
  return (
    <div style={{ marginTop: 8 }}>
      <UsersPage hideHeader />
    </div>
  );
}
