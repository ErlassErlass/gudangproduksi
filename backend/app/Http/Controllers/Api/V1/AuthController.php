<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Administration\Actions\AuthenticateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Administration\UserResource;
use App\Models\User;
use App\Shared\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

final class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthenticateUserAction $authenticate): JsonResponse
    {
        $result = $authenticate->execute(
            (string) $request->string('email'),
            (string) $request->string('password'),
            (string) $request->string('device_name', 'web'),
        );

        return response()->json([
            'data' => [
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'user' => (new UserResource($result['user']))->withPermissions(),
            ],
        ]);
    }

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('roles');

        return (new UserResource($user))->withPermissions();
    }

    public function updateProfile(Request $request, AuditLogger $audit): JsonResponse
    {
        /** @var User $user */
        $user = User::query()->with('roles')->findOrFail($request->user()->id);

        $validated = $request->validate([
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (!empty($validated['password'])) {
            if (!empty($validated['current_password']) && !Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Password saat ini (lama) tidak sesuai.',
                    'errors' => ['current_password' => ['Password saat ini (lama) tidak sesuai.']],
                ], 422);
            }
            $user->password = Hash::make($validated['password']);
        }

        if (!empty($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (isset($validated['name']) && $validated['name'] !== '') {
            $user->name = $validated['name'];
        }

        $user->save();
        $user->loadMissing('roles');

        $audit->record('auth.profile_updated', $user, null, ['email' => $user->email], $user);

        return response()->json([
            'data' => (new UserResource($user))->withPermissions(),
            'message' => 'Profil dan kredensial akun berhasil diperbarui.',
        ]);
    }

    public function logout(Request $request, AuditLogger $audit): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()?->delete();
        $audit->record('auth.logout', $user, null, null, $user);

        return response()->json(['data' => ['logged_out' => true]]);
    }
}
