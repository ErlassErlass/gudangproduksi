import { useState, type FormEvent } from 'react';
import { Pencil, Plus } from 'lucide-react';
import { ApiError } from '@/api/client';
import { Button } from '@/components/Button';
import { DataTable, type Column } from '@/components/DataTable';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { SearchBar } from '@/components/SearchBar';
import { StatusBadge } from '@/components/StatusBadge';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import { useListParams } from '@/hooks/useListParams';
import { formatDateTime } from '@/lib/format';
import type { User } from '@/types/models';
import { useRoles, useSaveUser, useUsers, type UserPayload } from './api';

export function UsersPage({ hideHeader = false }: { hideHeader?: boolean } = {}) {
  const { can } = useAuth();
  const canManage = can('administration.user.manage');
  const { params, setSearch, setSort, setPage, setFilter } = useListParams({ sort: 'name' });
  const { data, isLoading, error, refetch } = useUsers(params);
  const [editing, setEditing] = useState<User | 'new' | null>(null);

  const columns: Column<User>[] = [
    {
      key: 'name',
      header: 'Nama',
      sortField: 'name',
      render: (u) => (
        <div>
          <div>{u.name}</div>
          <div className="table__muted">{u.email}</div>
        </div>
      ),
    },
    {
      key: 'roles',
      header: 'Role',
      render: (u) =>
        u.roles.length ? u.roles.map((r) => <span key={r.id} className="chip">{r.name}</span>) : <span className="table__muted">—</span>,
    },
    {
      key: 'status',
      header: 'Status',
      render: (u) => <StatusBadge status={u.is_active ? 'ACTIVE' : 'INACTIVE'} />,
    },
    {
      key: 'last_login_at',
      header: 'Login Terakhir',
      sortField: 'last_login_at',
      render: (u) => <span className="table__muted">{formatDateTime(u.last_login_at)}</span>,
    },
    ...(canManage
      ? [
          {
            key: 'actions',
            header: 'Aksi',
            width: '80px',
            render: (u: User) => (
              <Button iconOnly variant="ghost" aria-label={`Ubah ${u.name}`} icon={<Pencil size={16} />} onClick={() => setEditing(u)} />
            ),
          },
        ]
      : []),
  ];

  return (
    <>
      {!hideHeader && (
        <PageHeader
          eyebrow="Administrasi"
          title="Pengguna"
          description="Kelola akun pengguna, status aktivasi, dan penetapan role."
          actions={
            canManage && (
              <Button variant="primary" icon={<Plus size={16} />} onClick={() => setEditing('new')}>
                Tambah Pengguna
              </Button>
            )
          }
        />
      )}

      <section className="card">
        <div className="toolbar">
          <SearchBar value={params.search ?? ''} onChange={setSearch} placeholder="Cari nama atau email" />
          <select
            className="select"
            style={{ width: 180 }}
            aria-label="Filter status"
            value={params.is_active === undefined ? '' : String(params.is_active)}
            onChange={(e) => setFilter('is_active', e.target.value)}
          >
            <option value="">Semua status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
        <DataTable
          columns={columns}
          rowKey={(u) => u.id}
          data={data}
          isLoading={isLoading}
          error={error}
          onRetry={() => refetch()}
          sort={params.sort}
          onSortChange={setSort}
          onPageChange={setPage}
          emptyTitle="Pengguna tidak ditemukan"
          emptyDescription="Ubah kata kunci pencarian atau filter."
        />
      </section>

      {editing && <UserFormModal user={editing === 'new' ? null : editing} onClose={() => setEditing(null)} />}
    </>
  );
}

function UserFormModal({ user, onClose }: { user: User | null; onClose: () => void }) {
  const { notify } = useToast();
  const roles = useRoles();
  const save = useSaveUser();
  const [form, setForm] = useState<UserPayload>({
    name: user?.name ?? '',
    email: user?.email ?? '',
    password: '',
    is_active: user?.is_active ?? true,
    role_ids: user?.roles.map((r) => r.id) ?? [],
  });
  const apiError = save.error instanceof ApiError ? save.error : null;

  const toggleRole = (id: number) =>
    setForm((f) => ({
      ...f,
      role_ids: f.role_ids.includes(id) ? f.role_ids.filter((r) => r !== id) : [...f.role_ids, id],
    }));

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    const payload = { ...form };
    if (user && !payload.password) delete payload.password;
    try {
      await save.mutateAsync({ id: user?.id, payload });
      notify(user ? 'Pengguna berhasil diperbarui.' : 'Pengguna berhasil ditambahkan.', 'success');
      onClose();
    } catch (err) {
      if (!(err instanceof ApiError && err.isValidation)) {
        notify(err instanceof Error ? err.message : 'Gagal menyimpan pengguna.', 'error');
      }
    }
  };

  return (
    <Modal
      open
      title={user ? 'Ubah Pengguna' : 'Tambah Pengguna'}
      onClose={onClose}
      footer={
        <>
          <Button onClick={onClose}>Batal</Button>
          <Button variant="primary" type="submit" form="user-form" loading={save.isPending}>
            Simpan
          </Button>
        </>
      }
    >
      <form id="user-form" onSubmit={handleSubmit} noValidate>
        <FormField label="Nama" htmlFor="user-name" required error={apiError?.fieldError('name')}>
          <input id="user-name" className="input" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
        </FormField>
        <FormField label="Email" htmlFor="user-email" required error={apiError?.fieldError('email')}>
          <input
            id="user-email"
            type="email"
            className="input"
            value={form.email}
            onChange={(e) => setForm({ ...form, email: e.target.value })}
          />
        </FormField>
        <FormField
          label="Kata sandi"
          htmlFor="user-password"
          required={!user}
          hint={user ? 'Kosongkan jika tidak ingin mengubah kata sandi.' : 'Minimal 8 karakter.'}
          error={apiError?.fieldError('password')}
        >
          <input
            id="user-password"
            type="password"
            autoComplete="new-password"
            className="input"
            value={form.password}
            onChange={(e) => setForm({ ...form, password: e.target.value })}
          />
        </FormField>
        <div className="field">
          <span className="field__label">Role</span>
          {roles.data?.data.map((role) => (
            <label key={role.id} className="checkbox-row">
              <input type="checkbox" checked={form.role_ids.includes(role.id)} onChange={() => toggleRole(role.id)} />
              {role.name}
            </label>
          ))}
          {apiError?.fieldError('role_ids') && <span className="field__error">{apiError.fieldError('role_ids')}</span>}
        </div>
        <label className="checkbox-row">
          <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} />
          Akun aktif
        </label>
      </form>
    </Modal>
  );
}
