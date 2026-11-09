<?php

namespace App\Http\Resources\Administration;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class UserResource extends JsonResource
{
    private bool $includePermissions = false;

    public function withPermissions(): self
    {
        $this->includePermissions = true;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => (bool) $this->is_active,
            'last_login_at' => (isset($this->last_login_at) ? $this->last_login_at?->toIso8601String() : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'code' => $role->code,
                'name' => $role->name,
            ])->values()),
            'permissions' => $this->when($this->includePermissions, fn () => $this->permissionCodes()),
        ];
    }
}
