<?php

namespace App\Domains\Administration\Actions;

use App\Domains\Administration\Models\Permission;
use App\Domains\Administration\Models\Role;
use App\Shared\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

final readonly class UpsertRoleAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{code?: string, name: string, description?: string|null, permission_codes?: list<string>}  $data
     */
    public function execute(?Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $codes = array_values(array_unique($data['permission_codes'] ?? []));
            $permissionIds = $codes === []
                ? []
                : Permission::query()->whereIn('code', $codes)->pluck('id')->all();

            if ($role === null) {
                $created = Role::query()->create([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'is_system' => false,
                ]);
                $created->permissions()->sync($permissionIds);
                $created->load('permissions')->loadCount('users');

                $this->audit->record('administration.role.created', $created, null, [
                    'code' => $created->code,
                    'name' => $created->name,
                    'permission_codes' => $codes,
                ]);

                return $created;
            }

            $before = [
                'name' => $role->name,
                'description' => $role->description,
                'permission_codes' => $role->permissions()->orderBy('code')->pluck('code')->all(),
            ];

            $role->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);
            $role->permissions()->sync($permissionIds);
            $role->load('permissions')->loadCount('users');

            $this->audit->record('administration.role.updated', $role, $before, [
                'name' => $role->name,
                'description' => $role->description,
                'permission_codes' => $codes,
            ]);

            return $role;
        });
    }
}
