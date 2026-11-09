<?php

namespace App\Domains\Administration\Actions;

use App\Models\User;
use App\Shared\Audit\AuditLogger;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class UpsertUserAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{name: string, email: string, password?: string, is_active: bool, role_ids?: list<int>}  $data
     */
    public function execute(?User $user, array $data, ?User $actor = null): User
    {
        if ($user !== null && $actor !== null && $user->is($actor) && $data['is_active'] === false) {
            throw new DomainException(
                'Anda tidak dapat menonaktifkan akun Anda sendiri.',
                'USER_CANNOT_DEACTIVATE_SELF',
                409,
            );
        }

        return DB::transaction(function () use ($user, $data): User {
            $roleIds = array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])));
            $attributes = Arr::only($data, ['name', 'email', 'is_active']);
            if (! empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }

            if ($user === null) {
                $created = User::query()->create($attributes);
                $created->roles()->sync($roleIds);
                $created->load('roles');

                $this->audit->record('administration.user.created', $created, null, [
                    ...Arr::only($created->toArray(), ['name', 'email', 'is_active']),
                    'role_ids' => $roleIds,
                ]);

                return $created;
            }

            $before = [
                ...Arr::only($user->toArray(), ['name', 'email', 'is_active']),
                'role_ids' => $user->roles()->pluck('roles.id')->all(),
            ];

            $user->fill($attributes)->save();
            $user->roles()->sync($roleIds);
            $user->flushPermissionCache();

            if (! $user->is_active) {
                $user->tokens()->delete();
            }

            $user->load('roles');
            $after = [
                ...Arr::only($user->toArray(), ['name', 'email', 'is_active']),
                'role_ids' => $roleIds,
            ];

            if ($before !== $after || ! empty($data['password'])) {
                $this->audit->record('administration.user.updated', $user, $before, [
                    ...$after,
                    ...(empty($data['password']) ? [] : ['password' => '[REDACTED]']),
                ]);
            }

            return $user;
        });
    }
}
