<?php

namespace App\Domains\Administration\Policies;

use App\Models\User;
use App\Shared\Audit\AuditLog;

/** Audit logs are strictly read-only (agent_docs/09_RBAC_MATRIX.md). */
final class AuditLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('audit.view');
    }

    public function view(User $actor, AuditLog $log): bool
    {
        return $actor->hasPermission('audit.view');
    }
}
