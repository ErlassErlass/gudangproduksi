<?php

namespace App\Domains\Administration\Policies;

use App\Domains\Administration\Models\Role;
use App\Models\User;

final class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('administration.role.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('administration.role.manage');
    }

    public function update(User $actor, Role $role): bool
    {
        return $actor->hasPermission('administration.role.manage');
    }
}
