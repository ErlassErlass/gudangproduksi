<?php

namespace App\Http\Resources\Administration;

use App\Domains\Administration\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Role */
final class RoleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'is_system' => (bool) $this->is_system,
            'users_count' => $this->whenCounted('users'),
            'permission_codes' => $this->whenLoaded(
                'permissions',
                fn () => $this->permissions->pluck('code')->sort()->values()->all(),
            ),
        ];
    }
}
