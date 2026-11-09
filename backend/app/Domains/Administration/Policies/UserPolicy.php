<?php

namespace App\Domains\Administration\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('administration.user.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->hasPermission('administration.user.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('administration.user.manage');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->hasPermission('administration.user.manage');
    }
}
