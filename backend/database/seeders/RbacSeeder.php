<?php

namespace Database\Seeders;

use App\Domains\Administration\Models\Permission;
use App\Domains\Administration\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the baseline permission catalog and the 12 default roles defined in
 * `agent_docs/09_RBAC_MATRIX.md`. Safe to run repeatedly (idempotent).
 */
final class RbacSeeder extends Seeder
{
    /**
     * Granular permission catalog across all roadmap domains.
     *
     * @return list<array{code: string, name: string, module: string}>
     */
    public static function permissionCatalog(): array
    {
        return [
            ['code' => 'dashboard.view', 'name' => 'Lihat dashboard', 'module' => 'dashboard'],

            ['code' => 'master_data.view', 'name' => 'Lihat master data', 'module' => 'master_data'],
            ['code' => 'master_data.manage', 'name' => 'Kelola master data', 'module' => 'master_data'],

            ['code' => 'bom.view', 'name' => 'Lihat BOM', 'module' => 'bom'],
            ['code' => 'bom.manage', 'name' => 'Buat & ubah BOM', 'module' => 'bom'],
            ['code' => 'bom.approve', 'name' => 'Setujui & aktifkan versi BOM', 'module' => 'bom'],

            ['code' => 'production.plan.view', 'name' => 'Lihat rencana produksi', 'module' => 'production'],
            ['code' => 'production.plan.create', 'name' => 'Buat rencana produksi & MRP', 'module' => 'production'],
            ['code' => 'production.plan.approve', 'name' => 'Setujui rencana produksi', 'module' => 'production'],
            ['code' => 'production.work_order.view', 'name' => 'Lihat produksi WIP / work order', 'module' => 'production'],
            ['code' => 'production.work_order.execute', 'name' => 'Eksekusi work order & catat hasil', 'module' => 'production'],

            ['code' => 'procurement.view', 'name' => 'Lihat pengadaan (PR/PO/GRN)', 'module' => 'procurement'],
            ['code' => 'procurement.po.create', 'name' => 'Buat PR & PO', 'module' => 'procurement'],
            ['code' => 'procurement.po.approve', 'name' => 'Setujui PR & PO', 'module' => 'procurement'],

            ['code' => 'inventory.view', 'name' => 'Lihat saldo & mutasi inventaris', 'module' => 'inventory'],
            ['code' => 'inventory.transaction.post', 'name' => 'Post transaksi inventaris', 'module' => 'inventory'],
            ['code' => 'inventory.transaction.reverse', 'name' => 'Reverse transaksi inventaris', 'module' => 'inventory'],
            ['code' => 'inventory.adjustment.approve', 'name' => 'Setujui penyesuaian stok & opname', 'module' => 'inventory'],
            ['code' => 'warehouse.view', 'name' => 'Lihat lokasi & kartu stok gudang', 'module' => 'warehouse'],

            ['code' => 'qc.view', 'name' => 'Lihat inspeksi QC', 'module' => 'qc'],
            ['code' => 'qc.perform', 'name' => 'Lakukan inspeksi QC', 'module' => 'qc'],

            ['code' => 'vendor.view', 'name' => 'Lihat vendor processing / GAP', 'module' => 'vendor'],
            ['code' => 'vendor.manage', 'name' => 'Kelola pengiriman & penerimaan GAP', 'module' => 'vendor'],

            ['code' => 'rental.view', 'name' => 'Lihat rental & peminjaman', 'module' => 'rental'],
            ['code' => 'rental.manage', 'name' => 'Kelola pesanan rental & pengembalian', 'module' => 'rental'],
            ['code' => 'rental.return.inspect', 'name' => 'Inspeksi pengembalian rental', 'module' => 'rental'],

            ['code' => 'distribution.view', 'name' => 'Lihat pengiriman / DO', 'module' => 'distribution'],
            ['code' => 'distribution.dispatch', 'name' => 'Dispatch pengiriman / DO', 'module' => 'distribution'],

            ['code' => 'material_cost.view', 'name' => 'Lihat biaya material produksi', 'module' => 'material_cost'],
            ['code' => 'reports.view', 'name' => 'Lihat & ekspor laporan', 'module' => 'reports'],

            ['code' => 'administration.user.view', 'name' => 'Lihat pengguna', 'module' => 'administration'],
            ['code' => 'administration.user.manage', 'name' => 'Kelola pengguna', 'module' => 'administration'],
            ['code' => 'administration.role.view', 'name' => 'Lihat role & izin', 'module' => 'administration'],
            ['code' => 'administration.role.manage', 'name' => 'Kelola role & izin', 'module' => 'administration'],
            ['code' => 'administration.settings.manage', 'name' => 'Kelola pengaturan & penomoran', 'module' => 'administration'],
            ['code' => 'audit.view', 'name' => 'Lihat audit log', 'module' => 'administration'],
        ];
    }

    /**
     * Default role → permission mapping from `agent_docs/09_RBAC_MATRIX.md`.
     * Note: Auditor is strictly read-only and never receives create/manage/post/approve codes.
     *
     * @return array<string, array{name: string, description: string, permissions: list<string>|'*'}>
     */
    public static function roleMatrix(): array
    {
        return [
            'superadmin' => [
                'name' => 'Superadmin',
                'description' => 'Akses penuh ke seluruh modul dan administrasi sistem.',
                'permissions' => '*',
            ],
            'pic_gudang' => [
                'name' => 'PIC Gudang',
                'description' => 'Kontrol persediaan gudang, mutasi stok, QC kelengkapan, penerimaan vendor, sewa/peminjaman, dan distribusi.',
                'permissions' => [
                    'dashboard.view', 'master_data.view', 'master_data.manage',
                    'inventory.view', 'inventory.transaction.post', 'inventory.transaction.reverse', 'inventory.adjustment.approve',
                    'warehouse.view', 'warehouse.manage',
                    'procurement.view', 'procurement.po.create',
                    'qc.view', 'qc.perform',
                    'vendor.view', 'vendor.manage',
                    'rental.view', 'rental.manage', 'rental.return.inspect',
                    'distribution.view', 'distribution.dispatch',
                    'material_cost.view', 'reports.view', 'audit.view',
                ],
            ],
            'kepala_produksi' => [
                'name' => 'Kepala Produksi',
                'description' => 'Perencanaan produksi, persetujuan rencana, pengelolaan formula BOM, dan eksekusi perakitan.',
                'permissions' => [
                    'dashboard.view', 'master_data.view',
                    'bom.view', 'bom.manage', 'bom.approve',
                    'production.plan.view', 'production.plan.create', 'production.plan.approve',
                    'production.work_order.view', 'production.work_order.execute',
                    'procurement.view', 'inventory.view', 'warehouse.view',
                    'qc.view', 'vendor.view', 'vendor.manage',
                    'rental.view', 'distribution.view',
                    'material_cost.view', 'reports.view', 'audit.view',
                ],
            ],
            'staff' => [
                'name' => 'Staff',
                'description' => 'Operasional perakitan harian, pelaksana tugas gudang dan produksi.',
                'permissions' => [
                    'dashboard.view', 'master_data.view',
                    'bom.view',
                    'production.work_order.view', 'production.work_order.execute',
                    'inventory.view', 'inventory.transaction.post',
                    'warehouse.view',
                    'qc.view', 'rental.view', 'distribution.view',
                ],
            ],
            // Alias backwards-compatibility for existing tests
            'super_admin' => [
                'name' => 'Super Admin',
                'description' => 'Alias Superadmin.',
                'permissions' => '*',
            ],
            'warehouse_manager' => [
                'name' => 'Warehouse Manager',
                'description' => 'Alias PIC Gudang.',
                'permissions' => [
                    'dashboard.view', 'master_data.view', 'master_data.manage',
                    'inventory.view', 'inventory.transaction.post', 'inventory.transaction.reverse', 'inventory.adjustment.approve',
                    'warehouse.view', 'warehouse.manage',
                    'procurement.view', 'procurement.po.create',
                    'qc.view', 'qc.perform',
                    'vendor.view', 'vendor.manage',
                    'rental.view', 'rental.manage', 'rental.return.inspect',
                    'distribution.view', 'distribution.dispatch',
                    'material_cost.view', 'reports.view', 'audit.view',
                ],
            ],
            'production_staff' => [
                'name' => 'Production Staff',
                'description' => 'Alias Staff Produksi.',
                'permissions' => [
                    'dashboard.view', 'master_data.view', 'bom.view',
                    'production.work_order.view', 'production.work_order.execute',
                ],
            ],
            'warehouse_staff' => [
                'name' => 'Warehouse Staff',
                'description' => 'Alias Staff Gudang.',
                'permissions' => [
                    'dashboard.view', 'master_data.view',
                    'inventory.view', 'inventory.transaction.post', 'warehouse.view',
                    'vendor.view', 'vendor.manage',
                    'distribution.view', 'distribution.dispatch',
                ],
            ],
            'rental_staff' => [
                'name' => 'Rental Staff',
                'description' => 'Alias Staff Rental.',
                'permissions' => [
                    'dashboard.view', 'master_data.view',
                    'rental.view', 'rental.manage', 'rental.return.inspect',
                    'qc.view', 'qc.perform',
                    'distribution.view', 'distribution.dispatch', 'reports.view',
                ],
            ],
            'auditor' => [
                'name' => 'Auditor',
                'description' => 'Akses baca ke seluruh modul dan audit log tanpa hak ubah/post/setujui.',
                'permissions' => [
                    'dashboard.view', 'master_data.view', 'bom.view',
                    'production.plan.view', 'production.work_order.view',
                    'procurement.view', 'inventory.view', 'warehouse.view',
                    'qc.view', 'vendor.view', 'rental.view', 'distribution.view',
                    'material_cost.view', 'reports.view',
                    'administration.user.view', 'administration.role.view', 'audit.view',
                ],
            ],
        ];
    }

    public function run(): void
    {
        DB::transaction(function (): void {
            $permissionIdsByCode = [];
            foreach (self::permissionCatalog() as $perm) {
                $model = Permission::query()->updateOrCreate(
                    ['code' => $perm['code']],
                    ['name' => $perm['name'], 'module' => $perm['module']],
                );
                $permissionIdsByCode[$model->code] = $model->id;
            }

            $allPermissionIds = array_values($permissionIdsByCode);

            foreach (self::roleMatrix() as $code => $definition) {
                $role = Role::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'],
                        'is_system' => true,
                    ],
                );

                $ids = $definition['permissions'] === '*'
                    ? $allPermissionIds
                    : array_values(array_intersect_key($permissionIdsByCode, array_flip($definition['permissions'])));

                $role->permissions()->sync($ids);
            }
        });
    }
}
