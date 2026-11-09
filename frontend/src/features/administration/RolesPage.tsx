import { useMemo, useState, type FormEvent } from 'react';
import { Pencil } from 'lucide-react';
import { ApiError } from '@/api/client';
import { Button } from '@/components/Button';
import { DataTable, type Column } from '@/components/DataTable';
import { FormField } from '@/components/FormField';
import { Modal } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { SearchBar } from '@/components/SearchBar';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import { useListParams } from '@/hooks/useListParams';
import type { Permission, Role } from '@/types/models';
import { usePermissions, useRoles, useSaveRole, type RolePayload } from './api';

export function RolesPage() {
  const { can } = useAuth();
  const canManage = can('administration.role.manage');
  const { params, setSearch, setPage } = useListParams({ per_page: 20 });
  const { data, isLoading, error, refetch } = useRoles(params);
  const [editing, setEditing] = useState<Role | 'new' | null>(null);

  const filteredData = useMemo(() => {
    if (!data) return data;
    const allowed = ['superadmin', 'pic_gudang', 'kepala_produksi', 'staff'];
    const filteredItems = data.data.filter((r) => allowed.includes(r.code));
    return {
      ...data,
      data: filteredItems,
      total: filteredItems.length,
    };
  }, [data]);

  const columns: Column<Role>[] = [
    { key: 'code', header: 'Kode', render: (r) => <code>{r.code}</code> },
    {
      key: 'name',
      header: 'Nama Role',
      render: (r) => (
        <div>
          <div className="font-semibold text-[var(--color-primary-dark, #263f7d)]">{r.name}</div>
          {r.description && <div className="table__muted">{r.description}</div>}
        </div>
      ),
    },
    { key: 'permissions', header: 'Jumlah Izin', render: (r) => r.permission_codes?.length ?? 0 },
    { key: 'users', header: 'Pengguna', render: (r) => r.users_count ?? 0 },
    ...(canManage
      ? [
          {
            key: 'actions',
            header: 'Aksi',
            width: '80px',
            render: (r: Role) => (
              <Button iconOnly variant="ghost" aria-label={`Ubah ${r.name}`} icon={<Pencil size={16} />} onClick={() => setEditing(r)} />
            ),
          },
        ]
      : []),
  ];

  return (
    <>
      <PageHeader
        eyebrow="Administrasi"
        title="Role & Hak Akses"
        description="Sistem menggunakan 4 role operasional standar: Superadmin, PIC Gudang, Kepala Produksi, dan Staff."
      />
      <section className="card">
        <div className="toolbar">
          <SearchBar value={params.search ?? ''} onChange={setSearch} placeholder="Cari role" />
        </div>
        <DataTable
          columns={columns}
          rowKey={(r) => r.id}
          data={filteredData}
          isLoading={isLoading}
          error={error}
          onRetry={() => refetch()}
          onPageChange={setPage}
        />
      </section>
      {editing && <RoleFormModal role={editing === 'new' ? null : editing} onClose={() => setEditing(null)} />}
    </>
  );
}

function RoleFormModal({ role, onClose }: { role: Role | null; onClose: () => void }) {
  const { notify } = useToast();
  const permissions = usePermissions();
  const save = useSaveRole();
  const [form, setForm] = useState<RolePayload>({
    code: role?.code ?? '',
    name: role?.name ?? '',
    description: role?.description ?? '',
    permission_codes: role?.permission_codes ?? [],
  });
  const apiError = save.error instanceof ApiError ? save.error : null;

  const grouped = useMemo(() => {
    const map = new Map<string, Permission[]>();
    permissions.data?.forEach((p) => map.set(p.module, [...(map.get(p.module) ?? []), p]));
    return [...map.entries()];
  }, [permissions.data]);

  const toggle = (code: string) =>
    setForm((f) => ({
      ...f,
      permission_codes: f.permission_codes.includes(code)
        ? f.permission_codes.filter((c) => c !== code)
        : [...f.permission_codes, code],
    }));

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    const payload = { ...form };
    if (role) delete payload.code;
    try {
      await save.mutateAsync({ id: role?.id, payload });
      notify('Role berhasil disimpan.', 'success');
      onClose();
    } catch (err) {
      if (!(err instanceof ApiError && err.isValidation)) {
        notify(err instanceof Error ? err.message : 'Gagal menyimpan role.', 'error');
      }
    }
  };

  return (
    <Modal
      open
      title={role ? `Ubah Role: ${role.name}` : 'Tambah Role'}
      onClose={onClose}
      footer={
        <>
          <Button onClick={onClose}>Batal</Button>
          <Button variant="primary" type="submit" form="role-form" loading={save.isPending}>
            Simpan
          </Button>
        </>
      }
    >
      <form id="role-form" onSubmit={handleSubmit} noValidate>
        {!role && (
          <FormField label="Kode" htmlFor="role-code" required hint="Huruf kecil dan garis bawah, mis. warehouse_lead." error={apiError?.fieldError('code')}>
            <input id="role-code" className="input" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value })} />
          </FormField>
        )}
        <FormField label="Nama" htmlFor="role-name" required error={apiError?.fieldError('name')}>
          <input id="role-name" className="input" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
        </FormField>
        <FormField label="Deskripsi" htmlFor="role-desc" error={apiError?.fieldError('description')}>
          <input
            id="role-desc"
            className="input"
            value={form.description ?? ''}
            onChange={(e) => setForm({ ...form, description: e.target.value })}
          />
        </FormField>
        <div className="field">
          <span className="field__label">Izin</span>
          {grouped.map(([module, perms]) => (
            <fieldset key={module} style={{ border: '1px solid var(--color-border)', borderRadius: 10, marginBottom: 8 }}>
              <legend className="page-header__eyebrow" style={{ padding: '0 6px', margin: 0 }}>
                {module}
              </legend>
              {perms.map((p) => (
                <label key={p.code} className="checkbox-row">
                  <input type="checkbox" checked={form.permission_codes.includes(p.code)} onChange={() => toggle(p.code)} />
                  <span>
                    {p.name} <code className="table__muted">{p.code}</code>
                  </span>
                </label>
              ))}
            </fieldset>
          ))}
          {apiError?.fieldError('permission_codes') && <span className="field__error">{apiError.fieldError('permission_codes')}</span>}
        </div>
      </form>
    </Modal>
  );
}
