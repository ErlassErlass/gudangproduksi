<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Administration\Actions\UpsertRoleAction;
use App\Domains\Administration\Models\Permission;
use App\Domains\Administration\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StoreRoleRequest;
use App\Http\Requests\Administration\UpdateRoleRequest;
use App\Http\Resources\Administration\PermissionResource;
use App\Http\Resources\Administration\RoleResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class RoleController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Role::class);

        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($perPage);

        return RoleResource::collection($roles);
    }

    public function store(StoreRoleRequest $request, UpsertRoleAction $action): JsonResponse
    {
        $this->authorize('create', Role::class);
        $role = $action->execute(null, $request->validated());

        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    public function update(UpdateRoleRequest $request, Role $role, UpsertRoleAction $action): RoleResource
    {
        $this->authorize('update', $role);

        return new RoleResource($action->execute($role, $request->validated()));
    }

    public function permissions(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Role::class);

        return PermissionResource::collection(
            Permission::query()->orderBy('module')->orderBy('code')->get(),
        );
    }
}
