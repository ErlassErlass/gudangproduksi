<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Administration\Actions\UpsertUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use App\Http\Resources\Administration\UserResource;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class UserController extends Controller
{
    use AuthorizesRequests;

    /** @var list<string> */
    private const SORTABLE = ['name', 'email', 'last_login_at', 'created_at'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'name');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        if (! in_array($column, self::SORTABLE, true)) {
            $column = 'name';
        }

        $query = User::query()
            ->with('roles')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->has('is_active') && $request->query('is_active') !== '', function ($q) use ($request) {
                $q->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy($column, $direction);

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        return UserResource::collection($query->paginate($perPage));
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);
        $user->load('roles');

        return new UserResource($user);
    }

    public function store(StoreUserRequest $request, UpsertUserAction $action): JsonResponse
    {
        $this->authorize('create', User::class);
        $user = $action->execute(null, $request->validated(), $request->user());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, User $user, UpsertUserAction $action): UserResource
    {
        $this->authorize('update', $user);

        return new UserResource($action->execute($user, $request->validated(), $request->user()));
    }
}
